<?php

/**
 * @param $k
 * @param bool|false $nocache
 * @return mixed
 */
function readConf($k,$nocache = false){
    $r = S($k);
    if(!($r) || $nocache){
        $r =  M('config')->where(array('key'=>$k))->getField('value');
        S($k,$r);
    }
    return $r;
}

function writeConf($k,$v){
    $M = M('config');
    S($k,$v);
    if($M->where(array('key'=>$k))->find()){
        $M->where(array('key'=>$k))->setField('value',$v);
    }else{
        $data['key'] = $k;
        $data['value'] = $v;
        $M->add($data);
    }
}

/**
 * @param string $timestr 需要格式化的时间戳
 * @return bool|string 格式化后时间字符串
 */
function Mydate($timestr=''){
    if(''==$timestr){
        $timestr = time();
    }
    if($timestr==0){
        return '';
    }else {
        return date('Y-m-d H:i', $timestr);
    }
}


/**
 * @param $openId
 */
function getWxUserInfo($openId){
    $access = getWxAccessToken();
    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access&openid=$openId&lang=zh_CN";
    $res = myCurl($url);
    $info = json_decode($res,true);
    return $info;
}


/**
 * @return mixed 微信凭证
 */
function getWxAccessToken(){
//    $token = S('Wx-access_token');
//    if(!$token){
//        $Wx = C('Wx');
//        $appId = $Wx['AppID'];
//        $appSec = $Wx['AppSecret'];
//        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSec";
//        $res = myCurl($url);
//        $data = json_decode($res,true);
//        $token = $data['access_token'];
//        S('Wx-access_token',$token,$data['expires_in']-1000);
//    }
    $Wx = C('Wx');
    $appId = $Wx['AppID'];
    $appSec = $Wx['AppSecret'];
    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSec";
    $res = myCurl($url);
    $data = json_decode($res,true);
    $token = $data['access_token'];
    return $token;
}


function myCurl($url,$data=false){
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
    if($data){
        curl_setopt_array($ch,$data);
    }
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    //运行curl，结果以jason形式返回
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

/**
 * @return string trade
 */
function creatTradeNum(){
    $trade = date('YmdHis').rand(0,9).rand(0,9).rand(0,9);
    if(M('Pay')->find($trade)){
        $trade = creatTradeNum();
    }
    return $trade;
}

function getNonceStr($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ )  {
        $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}

function createRedPackMoney(){
    $money = rand(1,100);
    return $money/100;
}

/**
 * @param  好友赠送微信提醒
 */
function sendAddUserTempMsg($data){
    $data['touser'] = $data['openid'];
    $data['template_id'] = 'Peu6c6BEFiK4zrdyl8hg9Cm-duQz1DV7XErIsk8hvpU';
    $data['url'] = U('user/myInvite','',true,true);
    $arr['first'] = array('value'=>'恭喜您，有新会员加入','color'=>'#173177');  //接收人
    $arr['keyword1'] = array('value'=>$data['nickname'],'color'=>'#173177');  //会员编号
    $arr['keyword2'] = array('value'=>date('Y-m-d H:i:s'),'color'=>'#173177');  //加入时间
    $arr['remark'] = array('value'=>'有新的会员加入你的团队','color'=>'#173177');
    $data['data'] = $arr;
    $post = json_encode($data,true);
    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.getWxAccessToken();
    $res = myCurl($url,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post));
    return $res;
}

/**
 *下线添加了订单
 */
function sendOrderTempMsg($data){
    $data['touser'] = $data['openid'];
    $data['template_id'] = 'zU4Z_xMmFIy8X_JeWt6qTsTjOJ6Q9GOGL5XBNtgqjNs';
    $data['url'] = U('user/myInvite','',true,true);
    $arr['first'] = array('value'=>'感谢您的推荐！','color'=>'#173177');  //接收人
    $arr['keyword1'] = array('value'=>$data['name'],'color'=>'#173177');  //客户名称
    $arr['keyword2'] = array('value'=>$data['money'],'color'=>'#173177');  //订单总金额
    $arr['remark'] = array('value'=>'请查收你的佣金','color'=>'#173177');
    $data['data'] = $arr;
    $post = json_encode($data,true);
    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.getWxAccessToken();
    $res = myCurl($url,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post));
    return $res;
}
