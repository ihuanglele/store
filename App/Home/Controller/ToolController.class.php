<?php
/**
 * Author: huanglele
 * Date: 2016/4/3
 * Time: 下午 03:18
 * Description:
 */

namespace Home\Controller;
use Think\Controller;

class ToolController extends Controller
{

    /**
     * 登录跳转
     */
    public static function loginReferer(){
        $referer = $_SERVER['HTTP_REFERER'];
        $host = $_SERVER['HTTP_HOST'];
        $patten = "/^http:\/\/$host(\/index.php)?(.*)$/i";
        if(preg_match($patten,$referer,$arr)){
            $uri = $arr[2];
            if(!preg_match('/^user\/login',$uri)){
                session('referer',$referer);
            }
        }
    }

    /**
     * @param $M 数据库模型
     * @param $map 查询限制条件
     * @param $order 排序
     * @param bool|false $field 需要查询的字段
     * @return array 返回查询到的数据数组
     */
    public function getData($M,$map,$order,$field=false){
        $count = $M->where($map)->count();
        $Page = new\Think\Page($count,10);
        $show = $Page->show();
        if($field){
            $list = $M->where($map)->field($field)->order($order)->limit($Page->firstRow,$Page->listRows)->select();
        }else{
            $list = $M->where($map)->order($order)->limit($Page->firstRow,$Page->listRows)->select();
        }
        $this->assign('list',$list);
        $this->assign('page',$show);
        return $list;
    }

    /**
     * @desc 用户钱数发送变化
     * @param $data
     * @return boolean
     */
    public static function changeMoney($data){
        $r1 = M('user')->where(array('uid'=>$data['uid']))->setInc('money',$data['money']);
        $data['money'] = abs($data['money']);
        $r2 = M('usermoney')->add($data);
        if(($r1!==false) && $r2){
            return true;
        }else{
            return false;
        }
    }

    //微信支付异步通知
    public function notify(){
        C('SHOW_PAGE_TRACE',false);
        $Handle = A('WechatNotify');
        $Handle->handle(false);
    }

    //微信统一下单
    public function pay($openId,$body,$attach,$trade_no,$money,$tag){
        //①、获取用户openid
        $tools = new \Org\Wxpay\JsApi();

        //②、统一下单
        $input = new \Org\Wxpay\WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($attach);
        $input->SetOut_trade_no($trade_no);
        $input->SetTotal_fee($money);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($tag);
        $input->SetNotify_url(C('Wx.notify_url'));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \Org\Wxpay\Wxpay::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        $this->assign('jsApiParamerers',$jsApiParameters);
        //获取共享收货地址js函数参数
        //$editAddress = $tools->GetEditAddressParameters();
        //$this->assign('address',$editAddress);
        return $order;
    }



}