<?php
/**
 * Author: huanglele
 * Date: 2016/4/1
 * Time: 下午 09:36
 * Description:
 */

namespace Home\Controller;
use Think\Controller;

class GoodsController extends Controller
{

    /**
     * 所有商品
     */
    public function index(){
        $Tool = A('Tool');
        $map['status'] = 2;
        $name = I('post.name');
        if($name){
            $map['name'] = array('like','%'.$name.'%');
        }
        $this->assign('name',$name);
        $Tool->getData(M('goods'),$map,'gid desc','gid,name,img,market_price,buy_price');
        $this->display('index');
    }


    /**
     * 显示单个商品
     */
    public function item(){
        $id = I('get.id');
        $info = M('goods')->find($id);
        if(!$info){$this->error('页面不存在',U('goods/index'));}
        //获取评论
        $map['gid'] = $id;
        $map['common'] = array('neq','');
        $map['status'] = 2;
        $common = M('orders')->field('oid,trim(`common`) as common')->where($map)->order('oid desc')->limit(10)->select();
        $this->assign('common',$common);
        $this->assign('info',$info);
        $this->display();
    }

    /**
     * 列举自营产品
     */
    public function selfList(){
        layout(false);
        $aid = I('get.aid',0,'number_int');
        $this->assign('aid',$aid);
        $type = I('get.type',0,'number_int');
        $Tool = A('Tool');
        $map['status'] = 1;
        if($type){
            $map['type'] = $type;
            $Tool->getData(M('product'),$map,'pid desc','pid,name,img,price');
        }else{
            $map['type'] = 1;
            $list1 = $Tool->getData(M('product'),$map,'pid desc','pid,name,img,price');
            $this->assign('list1',$list1);

            $map['type'] = 2;
            $list2 = $Tool->getData(M('product'),$map,'pid desc','pid,name,img,price');
            $this->assign('list2',$list2);
        }
        $tpl = 'selfList.'.$type;
        $this->display($tpl);
    }

    /**
     * 购买商家机器
     */
    public function product(){
        $aid = I('get.aid',0,'number_int');
        $id = I('get.id',0,'number_int');
        $info = M('product')->find($id);
        $this->assign('aid',$aid);
        $this->assign('info',$info);
        $this->assign('title',$info['name']);
        $this->display('product');
    }

    public function buyProduct(){
        $uid = session('uid');
        if(!$uid) $this->error('请先登录',U('user/login'));
        $aid = I('aid',0,'number_int');
        if(isset($_POST['submit'])){
            $pid = I('post.pid');
            $from = I('post.from');

            //获取地址信息
            $addr = M('addr')->find(I('addressid'));
            if(!$addr){$this->error('位置信息错误');die;}

            //获取产品的信息
            $gInfo = M('product')->find($pid);
            if(!($gInfo && $gInfo['status']==1)){var_dump($gInfo);$this->error('产品已下架');die;}

            //判断余额
            $uInfo = M('user')->field('money')->find($uid);
            if($uInfo['money']<$gInfo['price']){$this->error('账户余额不足');die;}

            //判断订单来源
            if($aid){
                $aInfo = M('admin')->field('status')->find($aid);
                if($aid && $aInfo['status']>1){
                    $agent = 'agent'.$aid['status'];
                }else{
                    $aid = 0;
                }
            }


            $User = M('user');
            $User->startTrans();
            $mapUser['uid'] = $uid;
            //添加订单，用户扣钱，添加扣钱记录，[平台自销]|[平台给商家钱，商家添加前记录]|[平台给用户佣金|添加佣金记录]

            $userNeedMoney = $gInfo['price'];

            $order['gid'] = $pid;
            $order['buy_price'] = $userNeedMoney;
            $order['uid'] = $uid;
            $order['aid'] = $aid;
            $order['buy_name'] = $addr['name'];
            $order['buy_addr'] = $addr['addr'];
            $order['tel'] = $addr['tel'];
            $order['create_time'] = time();
            $order['status'] = 1;
            $r1 = M('orders')->add($order);

            $r2 = $User->where($mapUser)->setDec('money',$userNeedMoney);

            $da3['time'] = time();
            $da3['money'] = $userNeedMoney;
            $da3['note'] = '购买商品'.$gInfo['name'];
            $da3['type'] = '1';
            $da3['uid'] = $uid;
            $r3 = M('usermoney')->add($da3);

            if($aid){   //代理卖出
                $getMoney = $gInfo[$agent];
                if($getMoney){
                    //商家钱增多
                    $r4 = M('admin')->where(array('aid'=>$aid))->setInc('money',$getMoney);

                    //添加商家记录
                    $da5['time'] = time();
                    $da5['money'] = $getMoney;
                    $da5['note'] = '订单号'.$r1;
                    $da5['type'] = '1';
                    $da5['aid'] = $aid;
                    $r5 = M('adminmoney')->add($da5);
                }else{
                    $r4 = $r5 = 1;
                }
            }else{ //平台自销
                if($from){
                    //给推广员佣金
                    $getMoney = $userNeedMoney*0.2;
                    M('user')->where(array('uid'=>$from))->setInc('money',$getMoney);

                    //添加商家记录
                    $da5['time'] = time();
                    $da5['money'] = $getMoney;
                    $da5['note'] = '推广佣金';
                    $da5['type'] = '4';
                    $da5['uid'] = $from;
                    M('usermoney')->add($da5);
                }
                $r4 = $r5 = 1;
            }



            if($r1 && $r2 && $r3 && $r4 && $r5){
                $User->commit();
                $tip = '购买成功后的页面：感谢您对我们的支持！同时欢迎您加入“鲜米现磨”营销团队！只要您动动手，点击方鲜米套餐推广生成您的专属二维码推广给您的好友，只要您的好友消费，您就有20%（人民币236元）的利润自动进入您的红包，倡导健康，收获粮薪！还等什么，马上行动吧！';
                $this->success($tip,U('user/myOrder'));
            }else{
                $User->rollback();
                $this->success('下单失败');
            }

        }else{
            $this->assign('aid',$aid);
            $info = json_decode(readConf('AgentGoodsInfo'),true);
            $this->assign('Info',$info);
            $this->assign('aid',$aid);
            //获取收货地址
            $map['uid'] = $uid;
            $addr = M('addr')->where($map)->getField('id,name,tel,addr');

            $this->assign('addr',$addr);
            $this->assign('addrsJosn',json_encode($addr));
            $this->display();
        }

    }

}