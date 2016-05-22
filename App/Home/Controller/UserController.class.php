<?php
/**
 * Author: huanglele
 * Date: 2016/4/1
 * Time: 下午 10:01
 * Description:
 */

namespace Home\Controller;
use Think\Controller;

class UserController extends Controller
{

    /**
     * @var null 用户的uid
     */
    private $uid = null;

    public function _initialize(){
        $uid = session('uid');
        if(strtolower(ACTION_NAME)!='login'){  //不是访问login
            if($uid){
                $this->uid = $uid;
            }else{
                $this->login();
                die;
            }
        }
        $this->assign('title','会员中心');

    }

    /**
     * 显示用户的个人中心
     */
    public function index(){
        $map['uid'] = $this->uid;
        $info = M('user')->where($map)->field('nickname,headimgurl,money,favorites')->find();

        //获取订单数量
        $ordersNum = M('orders')->where($map)->count();
        $info['ordersNum'] = $ordersNum;

        //获取个人收藏的信息
        $favArr = json_decode($info['favorites'],true);
        $info['favNum'] = count($favArr);

        //获取红包数量
        $packetsNum = M('packets')->where($map)->count();
        $info['packetsNum'] = $packetsNum;

        $this->assign('info',$info);
        $this->display('index');
    }

    /**
     * 显示我的订单
     */
    public function myOrder(){
        $map['uid'] = $this->uid;
        $Tool = A('Tool');
        $field = 'oid,gid,create_time as time,status,buy_price as money,buy_name';
        $list = $Tool->getData(M('orders'),$map,'oid desc',$field);
        $gidIds[] = 0;
        foreach($list as $v){
            $gidIds[] = $v['gid'];
        }
        $GoodsInfo = M('goods')->where(array('gid'=>array('in',$gidIds)))->getField('gid,name,img');
        $this->assign('GoodsInfo',$GoodsInfo);
        $this->assign('OrdersStatus',C('OrdersStatus'));
        $this->display('myOrder');
    }

    /**
     * 红包
     */
    public function myPacket(){
        $map['uid'] = $this->uid;
        $Tool = A('Tool');
        $Tool->getData(M('packets'),$map,'pid desc','create_time,money,status');
        $left_money = M('user')->where($map)->getField('money');
        $this->assign('left_money',$left_money);
        $this->assign('PacketStatus',C('PacketStatus'));
        $this->display('myPacket');
    }

    /**
     * 红包提现
     */
    public function askPacket(){
        if(isset($_POST['submit'])){
            $map['uid'] = $this->uid;
            $left_money = M('user')->where($map)->getField('money');
            $tx = I('post.money',0,'float');
            if($tx<1){$this->error('红包金额不能小于1元');die;}
            if($tx<$left_money) $this->error('余额不足',U('index'));
            //发送红包
        }else{
            $this->error('参数错误',U('index'));
        }
    }

    /**
     * 我的推荐好友
     */
    public function myInvite(){
        $map['invite_uid'] = $this->uid;
        $Tool = A('Tool');
        $Tool->getData(M('user'),$map,'uid desc','nickname,headimgurl as img');
        $this->display('myInvite');
    }

    /**
     * 订单详情
     */
    public function orderDetail(){
        $id = I('get.id',0,'number_int');
        $info = M('orders')->find($id);
        if(!$info || $info['uid']!=$this->uid){
            $this->error('页面不存在',U('index'));die;
        }
        $GoodsInfo = M('goods')->field('name,market_price,img')->find($info['gid']);
        $this->assign('info',$info);
        $this->assign('GoodsInfo',$GoodsInfo);
        $this->assign('OrdersStatus',C('OrdersStatus'));
        $this->display('orderDetail');
    }

    /**
     * 添加取消收藏
     */
    public function addFav(){
        $id = I('get.id',0,'number_int');
        $ac = I('get.ac');
        $M = M('User');
        $str = $M->where(array('uid'=>$this->uid))->getField('favorites');
        $arr = explode(',',$str);
        if($ac == 'undo'){
            foreach($arr as $k=>$v){
                if($v==$id){
                    unset($arr[$k]);break;
                }
            }
        }else{
            $arr[] = $id;
            $arr = array_unique($arr);
        }
        $newStr = '';
        foreach($arr as $v){
            $newStr .= $v.',';
        }
        if($str!=$newStr){
            $M->where(array('uid'=>$this->uid))->setField('favorites',$newStr);
        }
        $this->success('操作成功');
    }

    /**
     * 我的收藏
     */
    public function myFav(){
        $ids = M('user')->where(array('uid'=>$this->uid))->getField('favorites');
        $idsArr = explode(',',$ids);
        if(empty($idsArr)){
            $list = array();
        }else{
            $list = M('Goods')->where(array('gid'=>array('in',$idsArr)))->select();
        }
        $this->assign('list',$list);
        $this->display('myFav');

    }

    /**
     * 用户登录，如果之前有，直接读取用户名信息，如果没有添加一个新用户
     */
    public function login(){
        $tools = new \Org\Wxpay\UserApi();
        $openId = $tools->GetOpenid();
        $wxInfo = $tools->getInfo();
        if(!$wxInfo || isset($wxInfo['errcode'])){
            $this->error('微信授权出错',U('index/index'));
        }
        $info = getWxUserInfo($openId);
        if(!$info || isset($info['errcode'])){
            var_dump($info);die;
            $this->error('登录出了点状况',U('index/index'));
        }

        //判断之前是否存储过用户资料
        $M = M('user');
        $data = array_merge($info,$wxInfo);

        $data['last_time'] = time();
        if(isset($data['headimgurl'])){
            $headimg = trim($data['headimgurl'],'0').'132';
            $data['headimgurl'] = trim($data['headimgurl'],'0').'64';
        }
        $uInfo = $M->where(array('openid'=>$openId))->field('uid,last_time')->find();
        $uid = $uInfo['uid'];

        if($uid){
            $data['uid'] = $uid;
            $res = $M->save($data);
            if($res===false){
                //更新出错
                $r = false;
            }else{
                $r = true;
            }
        }else{
            //第一次登录 添加到用户表里面
            $data['money'] = 0;
            //判断是否有上限
            $data['invite_uid'] = $this->setUserInviteUid();
            $r = $M->add($data);
            $uid = $r;
        }
        if($r){
            session('uid',$uid);
            session('openid',$openId);

            //保存图像
            if(isset($headimg)){
                $pic = myCurl($headimg);
                $filePath = THINK_PATH.'../headerImg/'.$uid.'.jpg';
                file_put_contents($filePath,$pic);
            }
            session('referer',null);
            $this->redirect('User/index');
        }else{
            $this->error('登录失败');
        }
    }

    /**
     * 退出登录
     */
    public function logout(){
        session('uid',null);
        session('openid',null);
        $this->success('您已安全退出',U('index/index'));
    }

    /**
     * 判断新用户注册是否来自别人的邀请
     * @return int 0或者邀请者的id
     */
    private function setUserInviteUid(){
        $from = I('get.invite',0,'number_int');
        $User = M('user');
        $UserInfo = $User->field('uid,money,openid')->find();
        if($UserInfo){
            //来自合法的邀请
            $da['money'] = readConf('InviteReward')?readConf('InviteReward'):C('InviteReward');
            $da['type'] = 4;
            $da['note'] = '邀请用户注册';
            $da['time'] = time();
            $da['uid'] = $from;
            $Tool = A('Tool');
            $Tool->changeMoney($da);
            return $from;
        }else{
            return 0;
        }
    }

    /**
     * 显示我的收货地址
     */
    public function myAddr(){
        $Tool = A('Tool');
        $map['uid'] = $this->uid;
        $order = 'id desc';
        $Tool->getData(M('addr'),$map,$order);
        $this->display('myAddr');
    }

    /**
     * 添加收货地址
     */
    public function addAddress(){
        if (isset($_POST['submit'])) {
            $data = $_POST;
            $data['uid'] = $this->uid;
            $id = I('post.id', 0, 'number_int');
            if ($id) {
                $data['id'] = $id;
                $r1 = M('addr')->save($data);
            } else {
                $r1 = M('addr')->add($data);
            }
            if ($r1) {
                $this->success('操作成功', U('myAddr'));
            } else {
                $this->error('操作失败');
            }
        } else {
            $id = I('get.id');
            if ($id) {
                $info = M('addr')->find($id);
            } else {
                $info = array();
            }
            $this->assign('info', $info);
            $this->display('addAddress');
        }
    }

    /**
     * 删除收货地址
     */
    public function delAddress(){
        $id = I('get.id');
        $map['id'] = $id;
        $map['uid'] = $this->uid;
        if(M('addr')->where($map)->delete()){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    /**
     * 显示我的购物车
     */
    public function myCart(){
        $cart = session('cart');
        $info = array();
        $goodsInfo = array();
        if(is_array($cart)){
            $goodsIds = array(0);
            foreach($cart as $k=>$v){
                $goodsIds[] = $k;
            }
            $goodsInfo = M('goods')->where(array('gid'=>array('in',$goodsIds)))->getField('gid,name,left_num,status,buy_price,img,yunfei');
        }else{
            $cart = array();
        }

        //获取收货地址
        $map['uid'] = $this->uid;
        $addr = M('addr')->where($map)->getField('id,name,tel,addr');

        $this->assign('goodsInfo',$goodsInfo);
        $this->assign('GoodsStatus',C('GoodsStatus'));
        $this->assign('addr',$addr);
        $this->assign('addrsJosn',json_encode($addr));
        $this->assign('cart',$cart);
        $this->display('myCart');
    }

    /**
     * 显示我的关注推广链接
     */
    public function myLink(){
        layout(false);
        C('SHOW_PAGE_TRACE',false);
        $qrImgPath = THINK_PATH.'../qrCodeImg/'.$this->uid.'.jpg';
        $headerImgPath = THINK_PATH.'../headerImg/'.$this->uid.'.jpg';
        $bgImgPath = THINK_PATH.'../Public/images/myLinkBg.jpg';
        if(!is_file($qrImgPath)){
            //没有自己的推广二维码
            if(!$this->getQrCode()){
                die('服务器出错');
            }
        }
        if(!is_file($headerImgPath)){
            //没有获取到头像
            $headerImgPath = THINK_PATH.'../headerImg/default.jpg';
        }

        header('Content-Type: image/jpeg');
        $image = imagecreatefromjpeg($bgImgPath);
        $qr = imagecreatefromjpeg($qrImgPath);
        $header = imagecreatefromjpeg($headerImgPath);
        imagecopy($image,$qr,265,792,10,10,140,140);
        imagecopy($image,$header,120,792,0,0,132,132);
        imagedestroy($qr);
        imagedestroy($header);
        $r = imagejpeg($image);
        imagedestroy($image);
    }

    public function storeLink(){
        layout(false);
        C('SHOW_PAGE_TRACE',false);
        $qrImgPath = THINK_PATH.'../storeCodeImg/'.$this->uid.'.png';
        $headerImgPath = THINK_PATH.'../headerImg/'.$this->uid.'.jpg';
        $bgImgPath = THINK_PATH.'../Public/images/storeLinkBg.jpg';
        if(!is_file($qrImgPath)){
            //没有自己的推广二维码
            if(!$this->storeCodeImg()){
                die('服务器出错');
            }
        }
        if(!is_file($headerImgPath)){
            //没有获取到头像
            $headerImgPath = THINK_PATH.'../headerImg/default.jpg';
        }

        header('Content-Type: image/jpeg');
        $image = imagecreatefromjpeg($bgImgPath);
        $qr = imagecreatefrompng($qrImgPath);
        $header = imagecreatefromjpeg($headerImgPath);
        imagecopy($image,$qr,265,792,10,10,140,140);
        imagecopy($image,$header,120,792,0,0,132,132);
        imagedestroy($qr);
        imagedestroy($header);
        $r = imagejpeg($image);
        imagedestroy($image);
    }

    /**
     * 购物
     */
    public function buy(){
        if(isset($_POST)){
            $ids = I('post.ids');
            $nums = I('post.nums');
            $n = count($ids);
            if($n){
                //获取地址信息
                $addr = M('addr')->find(I('addressid'));
                if(!$addr){$this->error('位置信息错误');die;}
                $User = M('user');
                $mapUser['uid'] = $this->uid;
                $userLeftMoney = $User->where($mapUser)->getField('money');
                $goodInfo = M('goods')->where(array('gid'=>array('in',$ids)))->getField('gid,buy_price,left_num,sold_num,status,aid,rate,status,yunfei');
                $needMoney = 0;
                for($i=0;$i<$n;$i++){
                    $needMoney += $goodInfo[$ids[$i]]['buy_price']*$nums[$i] + $goodInfo[$ids[$i]]['yunfei'];
                }
                if($needMoney<$userLeftMoney){
                    for($i=0;$i<$n;$i++){
                        $order['gid'] = $ids[$i];
                        $order['buy_name'] = $addr['name'];
                        $order['buy_addr'] = $addr['addr'];
                        $order['tel'] = $addr['tel'];
                        $this->handleBuy($ids[$i],$nums[$i],$goodInfo[$ids[$i]],$order);
                    }
                    session('cart',null);
                    $this->success('下单成功',U('user/myOrder'));
                }else{
                    $this->error('余额不足',U('user/pay'));
                }

            }else{
                $this->error('购物车为没有东西');
            }
        }
    }

    public function handleBuy($id,$num,$gInfo,$order){
        if($gInfo['status']==2 && $gInfo['left_num']>=$num){
            $User = M('user');
            $User->startTrans();
            $mapUser['uid'] = $this->uid;
            //添加订单，用户扣钱，添加扣钱记录，商品减数，商家增钱，商家增钱记录

            $userNeedMoney = $gInfo['buy_price']*$num+$gInfo['yunfei'];
            $getMoney = $gInfo['buy_price']*$num*(100-$gInfo['rate'])/100+$gInfo['yunfei'];

            $order['buy_price'] = $gInfo['buy_price'];
            $order['money'] = $getMoney;
            $order['buy_num'] = $num;
            $order['uid'] = $this->uid;
            $order['create_time'] = time();
            $order['aid'] = $gInfo['aid'];
            $order['status'] = 1;
            $r1 = M('orders')->add($order);

            $r2 = $User->where($mapUser)->setDec('money',$userNeedMoney);

            $da3['time'] = time();
            $da3['money'] = $userNeedMoney;
            $da3['note'] = '购买商品，订单号'.$r1;
            $da3['type'] = '1';
            $da3['uid'] = $this->uid;
            $r3 = M('usermoney')->add($da3);

            //商品数量减数
            $da4['left_num'] = $gInfo['left_num']-$num;
            $da4['sold_num'] = $gInfo['sold_num']+$num;
            if($da4['left_num']<1)  $da4['status'] = 3;
            $r4 = M('Goods')->where(array('gid'=>$id))->save($da4);

            //商家钱增多
            $r5 = M('admin')->where(array('aid'=>$gInfo['aid']))->setInc('money',$getMoney);

            //添加商家记录
            $da6['time'] = time();
            $da6['money'] = $getMoney;
            $da6['note'] = '订单号'.$r1;
            $da6['type'] = '1';
            $da6['aid'] = $gInfo['aid'];
            $r6 = M('adminmoney')->add($da6);

            if($r1 && $r2 && $r3 && $r4 && $r5 && $r6){
                $User->commit();return true;
            }else{
                $User->rollback();return false;
            }

        }
    }

    public function pay(){
        if(isset($_POST['money'])){
            $money = I('post.money',0,'number_float');
            if($money>0){
                $body = '充值';
                $attach = '充值';
                $tag = $this->uid;
                $trade_no = creatTradeNum();
                $openId = session('openid');
                $Pay = A('Wechat');
                $order = $Pay->pay($openId,$body,$attach,$trade_no,$money,$tag);
                if($order['result_code']=='SUCCESS'){//生成订单信息成功
                    $data['uid'] = $this->uid;
                    $data['create_time'] = time();
                    $data['money'] = $money;
                    $data['paytrade'] = $trade_no;
                    $data['status'] = 1;
                    $data['pay_time'] = 0;
                    if(M('pay')->add($data)){
                        $this->assign('money',$money);
                        $this->display('paySub');die;
                    }else{
                        $this->error('操作失败请重试');die;
                    }
                }else{
                    $this->error('操作失败请重试');die;
                }
            }else{
                $this->error('输入金额有误');
            }
        }else{
            $this->display('pay');
        }
    }

    //店铺推广二维码
    private function storeCodeImg(){
        $url = U('goods/selfList',array('from'=>$this->uid),true,true);
        $qrUrl = 'http://qr.liantu.com/api.php?w=150&m=10&text='.urldecode($url);
        $pic = myCurl($qrUrl);
        $filePath = THINK_PATH.'../storeCodeImg/'.$this->uid.'.png';
        file_put_contents($filePath,$pic);
        $image = new \Think\Image();
        $image->open($filePath)->thumb(150,150)->save($filePath);
        return true;
    }

    /**
     * 返回个人微信推广二维码地址
     */
    private function getQrCode(){
        $ticket = $this->getTicke();
        if($ticket){
            $qrUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urldecode($ticket);
            $pic = myCurl($qrUrl);
            $filePath = THINK_PATH.'../qrCodeImg/'.$this->uid.'.jpg';
            file_put_contents($filePath,$pic);
            $image = new \Think\Image();
            $image->open($filePath)->thumb(150,150)->save($filePath);
            return true;
        }else{
            die('没有获取到了ticket');
            return false;
        }
    }

    /**
     * http请求方式: POST
     *   URL: https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=TOKENPOST数据格式：json
     *   POST数据例子：{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": 123}}}
     * 或者也可以使用以下POST数据创建字符串形式的二维码参数：
     * {"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "123"}}}
     */
    private function getTicke(){
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.getWxAccessToken();
        $data = '{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$this->uid.'"}}}';
        $curlArr = array(CURLOPT_POSTFIELDS=>$data);
        $res = json_decode(myCurl($url,$curlArr),true);
        if(isset($res['ticket'])){
            return $res['ticket'];
        }else{
            var_dump($res);
            die();
            return false;
        }
    }


}