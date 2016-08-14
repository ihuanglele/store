<?php
/**
 * Author: huanglele
 * Date: 2016/1/11
 * Time: 23:52
 * Description:
 * 微信支付回调处理逻辑
 */

namespace Home\Controller;
use Org\Wxpay;

require_once LIB_PATH."Org/Wxpay/Include.function.php";

class WechatNotifyController extends \Org\Wxpay\WxPayNotify
{
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new \Org\Wxpay\WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \Org\Wxpay\WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        $notfiyOutput = array();
        S('notify',$data);
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
        //查询订单状态
        $Pay = M('pay');
        $payInfo = $Pay->where(array('paytrade'=>$data['out_trade_no']))->field('pid,money,status,uid,oid')->find();
        if($payInfo['status']==1){
            if($payInfo['oid']){    //直接购买
                //更新支付订单，修改订单，{商品减数，商家增钱，商家增钱记录 | }
                $Pay->startTrans();
                $da1['status'] = 2;
                $da1['pay_time'] = time();
                $r1 = $Pay->where(array('pid'=>$payInfo['pid']))->save($da1);

                $oInfo = M('orders')->field('gid,buy_num as num,aid,money,type,from')->find($payInfo['oid']);   //订单信息

                $r2 =  M('orders')->where(array('oid'=>$payInfo['oid']))->setField('status',1);

                $da3['time'] = time();
                $da3['money'] = $payInfo['money'];
                $da3['note'] = '购买商品，订单号'.$payInfo['oid'];
                $da3['type'] = '1';
                $da3['uid'] = $payInfo['uid'];
                $r3 = M('usermoney')->add($da3);

                if($oInfo['type']==1){      //商城订单

                    //商品数量减数
                    $gInfo = M('goods')->field('buy_price,left_num,sold_num,rate,yunfei,aid')->find($oInfo['gid']);
                    $da4['left_num'] = $gInfo['left_num']-$oInfo['num'];
                    $da4['sold_num'] = $gInfo['sold_num']+$oInfo['num'];
                    if($da4['left_num']<1)  $da4['status'] = 3;
                    $r4 = M('Goods')->where(array('gid'=>$oInfo['gid']))->save($da4);

                    //商家钱增多
                    $getMoney = $gInfo['buy_price']*$oInfo['num']*(100-$gInfo['rate'])/100+$gInfo['yunfei'];
                    $r5 = M('admin')->where(array('aid'=>$gInfo['aid']))->setInc('money',$getMoney);

                    //添加商家记录
                    $da6['time'] = time();
                    $da6['money'] = $getMoney;
                    $da6['note'] = '订单号'.$r1;
                    $da6['type'] = '1';
                    $da6['aid'] = $gInfo['aid'];
                    $r6 = M('adminmoney')->add($da6);

                    if($r1 && $r2 && $r3 && $r4 && $r5 && $r6){
                        $Pay->commit();return true;
                    }else{
                        $Pay->rollback();return false;
                    }
                }else if($oInfo['type']==2){    //自营订单
                    $aid = $oInfo['aid'];
                    $aInfo = M('admin')->field('status')->find($aid);
                    if($aid && $aInfo['status']>1){
                        $agent = 'agent'.$aid['status'];
                    }else{
                        $aid = 0;
                    }
                    $gInfo = M('product')->find($oInfo['gid']);
                    if($aid){   //代理卖出
                        $getMoney = $gInfo[$agent]*$oInfo['num'];
                        if($getMoney){
                            //商家钱增多
                            $r4 = M('admin')->where(array('aid'=>$aid))->setInc('money',$getMoney);

                            //添加商家记录
                            $da5['time'] = time();
                            $da5['money'] = $getMoney;
                            $da5['note'] = '订单号'.$payInfo['oid'];
                            $da5['type'] = '1';
                            $da5['aid'] = $aid;
                            $r5 = M('adminmoney')->add($da5);
                        }else{
                            $r4 = $r5 = 1;
                        }
                    }else{ //平台自销
                        $from = $oInfo['from'];
                        if($from){
                            //给推广员佣金
                            $tip = '推广佣金';
                            $tjUser = M('user')->where(array('uid'=>$from))->field('uid,rate,openid,income_uid')->find();
                            if($tjUser['income_uid']){
                                $tjUser = M('user')->where(array('uid'=>$tjUser['income_uid']))->field('uid,rate,openid')->find();
                                $tip = '来自'.$from.'的推广佣金';
                            }
                            $rate = $tjUser['rate'];
                            if($rate){  //获取到了推广员的佣金比例
                                $userNeedMoney = $gInfo['price']*$oInfo['num'];
                                $getMoney = $userNeedMoney*$rate/100;
                                M('user')->where(array('uid'=>$tjUser['uid']))->setInc('money',$getMoney);

                                //添加商家记录
                                $da5['time'] = time();
                                $da5['money'] = $getMoney;
                                $da5['note'] = $tip;
                                $da5['type'] = '4';
                                $da5['uid'] = $tjUser['uid'];
                                M('usermoney')->add($da5);

                                $temp['openid'] = $tjUser['openid'];
                                $temp['name'] = M('user')->where(array('uid'=>$payInfo['uid']))->getField('nickname');
                                $temp['money'] = $userNeedMoney;
                                sendOrderTempMsg($temp);
                            }
                        }
                        $r4 = $r5 = 1;
                    }
                    if($r1 && $r2 && $r3 && $r4 && $r5){
                        $Pay->commit();return true;
                    }else{
                        $Pay->rollback();return false;
                    }
                }

            }else{  //充值
                //处理
                //pay(status),user(money),umoney
                $Pay->startTrans();
                $da1['status'] = 2;
                $da1['pay_time'] = time();
                $r1 = $Pay->where(array('pid'=>$payInfo['pid']))->save($da1);

                $r2 = M('user')->where(array('uid'=>$payInfo['uid']))->setInc('money',$payInfo['money']);

                $da3['money'] = $payInfo['money'];
                $da3['uid'] = $payInfo['uid'];
                $da3['time'] = time();
                $da3['type'] = 3;
                $da3['note'] = '微信充值，订单号：'.$payInfo['paytrade'];
                $r3 = M('usermoney')->add($da3);

                if($r1 && $r2 && $r3){
                    $Pay->commit();
                    return true;
                }else{
                    $Pay->rollback();
                    $msg = '处理失败';
                    return false;
                }
            }
        }else{ //订单已经处理过
            $msg = "订单已处理";
            return false;
        }
        return true;
    }

}
