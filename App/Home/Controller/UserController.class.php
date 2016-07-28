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
        session('from',I('get.from'));
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
        $map['status'] = array('gt',0);
        $ordersNum = M('orders')->where($map)->count();
        $info['ordersNum'] = $ordersNum;
        unset($map['status']);

        //获取个人收藏的信息
        $favArr = json_decode($info['favorites'],true);
        $info['favNum'] = count($favArr);

        //获取红包数量
        $map['type'] = 4;
        $packetsNum = M('usermoney')->where($map)->count();
        $info['packetsNum'] = $packetsNum;

        $this->assign('info',$info);
        $this->display('index');
    }

    public function shareSuccess(){
        $last = S($this->uid.'share');
        if($last<date('Y-m-d 0:0:0')){
            $data['uid'] = $this->uid;
            $data['type'] = 4;
            $last = $data['time'] = time();
            $data['money'] = createRedPackMoney();
            $data['note'] = '分享送红包';
            $Tool = A('Tool');
            $Tool->changeMoney($data);
            S($this->uid.'share',$last);
            die($data['money']);
        }else{
            die(0);
        }
    }

    /**
     * 显示我的订单
     */
    public function myOrder(){
        $map['uid'] = $this->uid;
        $map['status'] = array('gt',0);
        $Tool = A('Tool');
        $field = 'oid,gid,create_time as time,buy_num as num,status,buy_price as money,buy_name,type';
        $list = $Tool->getData(M('orders'),$map,'oid desc',$field);
        $gidIds[] = 0;
        $pidIds[] = 0;
        foreach($list as $v){
            if($v['type']==1){
                $gidIds[] = $v['gid'];
            }elseif($v['type']==2){
                $pidIds[] = $v['gid'];
            }
        }
        $GoodsInfo = M('goods')->where(array('gid'=>array('in',$gidIds)))->getField('gid,name,img');
        $pInfo = M('product')->where(array('pid'=>array('in',$pidIds)))->getField('pid as gid,name,img');
        $this->assign('GoodsInfo',$GoodsInfo);
        $this->assign('pInfo',$pInfo);
        $this->assign('OrdersStatus',C('OrdersStatus'));
        $this->display('myOrder');
    }

    /**
     * 红包
     */
    public function myPacket(){
        $map['uid'] = $this->uid;
        $Tool = A('Tool');
        $list = $Tool->getData(M('usermoney'),$map,'mid desc','time,money,type,note');
        $left_money = M('user')->where($map)->getField('money');
        $this->assign('left_money',$left_money);
        $this->assign('MoneyType',C('UserMoneyType'));
        $this->display('myPacket');
    }

    /**
     * 红包提现
     */
    public function askPacket(){
        if(isset($_POST['submit'])){
            $map['uid'] = $this->uid;
            $uInfo = M('user')->where($map)->field('money,uid,openid')->find();
            $tx = I('post.money',0,'float');
            if($tx<1){$this->error('红包金额不能小于1元');die;}
            if($tx>$uInfo['money']) $this->error('余额不足',U('index'));
            //发送红包
            $Wechat = A('Wechat');
            $res = $Wechat->sendRedPack($tx,$uInfo['openid']);
            if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
                $data['paytrade'] = $res['mch_billno'];
                $data['create_time'] = time();
                $data['uid'] = $this->uid;
                $data['money'] = $tx;
                $data['status'] = 1;
                M('packets')->add($data);
                $this->success('发生成功，请及时领取');
            }else{
                $this->error('发生失败，请重试');
            }
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
        if($info['type']==1){
            $GoodsInfo = M('goods')->field('name,market_price,img')->find($info['gid']);
        }else{
            $GoodsInfo = M('product')->field('name,price as market_price,img')->find($info['gid']);
        }
        $this->assign('info',$info);
        $this->assign('GoodsInfo',$GoodsInfo);
        $this->assign('OrdersStatus',C('OrdersStatus'));
        $this->display('orderDetail');
    }

    public function goodCommon(){
        $common = I('post.common');
        $oid = I('post.oid');
        $map['oid'] = $oid;
        $M = M('orders');
        $info = $M->where($map)->find();
        if($info['commmon']=='' && $info['uid']==session('uid')){
            $M->where($map)->setField('common',$common);
            $this->success('评价成功');
        }else{
            $this->error('当前不允许评价');
        }
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
            $list = M('Goods')->where(array('gid'=>array('in',$idsArr)))->field('name,gid,img')->select();
        }
        $this->assign('list',$list);
        $this->display('myFav');

    }

    /**
     * 用户登录，如果之前有，直接读取用户名信息，如果没有添加一个新用户
     */
    public function login(){
        //$this->checkJump();
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
            session('nickname',$data['nickname']);
            //判断是否有上限
            $data['invite_uid'] = $this->setUserInviteUid();
            $r = $M->add($data);
            $uid = $r;
        }
        if($r){

            $jump = session('jump');
            if(!$jump){
                $jump = U('index/index');
            }

            session('jump',null);
            session('uid',$uid);
            session('openid',$openId);

            //保存图像
            if(isset($headimg)){
                $pic = myCurl($headimg);
                $filePath = THINK_PATH.'../headerImg/'.$uid.'.jpg';
                file_put_contents($filePath,$pic);
            }
            session('referer',null);
            header("Location:$jump");
        }else{
            $this->error('登录失败');
        }
    }



    public function checkJump(){
        $jump = I('jump');
        $code = I('code');
        if(!$jump && !$code){
            $jump = U('index/index');
        }
        session('jump',urldecode($jump));
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
        if(!$from){
            $from = session('from');
        }
        if($from==0) {
            return 0;
        }
        $User = M('user');
        $UserInfo = $User->where(array('uid'=>$from))->field('uid,money,openid')->find();
        if($UserInfo){
            //来自合法的邀请
            $da['money'] = readConf('InviteReward')?readConf('InviteReward'):C('InviteReward');
            $da['type'] = 4;
            $da['note'] = '邀请'.session('nickname').'注册';
            $da['time'] = time();
            $da['uid'] = $from;
            $Tool = A('Tool');
            $Tool->changeMoney($da);
            $temp['openid'] = $UserInfo['openid'];
            $temp['nickname'] = session('nickname');
            sendAddUserTempMsg($temp);
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
            $jump = I('post.jump');
            if(!$jump){
                $jump = U('myAddr');
            }
            if ($r1) {
                $this->success('操作成功',$jump);
            } else {
                $this->error('操作失败');
            }
        } else {
            $refefer = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
            if($refefer && strpos($refefer,'goods')){
                if(!strpos($refefer,'buy')){
                    $refefer .= '#buy';
                }
                $this->assign('jump',$refefer);
            }else{
                $this->assign('jump','');
            }
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
        $map['uid'] = $this->uid;
        $map['type'] = 1;
        $count = M('orders')->where($map)->find();
        if($count){
            $buy = 1;
        }else{
            $buy = 0;
        }
        $this->assign('buy',$buy);
        $this->display('myLink');
    }

    /**
     * 生成我的关注推广链接
     */
    public function myLinkPic(){
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

        $name = M('user')->where(array('uid'=>$this->uid))->getField('nickname');
        $color = imagecolorallocate($image, 41, 163, 238);
        $font = THINK_PATH.'Library/Think/Verify/ttfs/msyh.ttf';
        imagettftext($image,14,0,480,815,$color,$font,$name);

        imagedestroy($qr);
        imagedestroy($header);
        $r = imagejpeg($image);
        imagedestroy($image);
    }

    /**
     * 鲜米店铺二维码
     */
    public function storeLink(){
        $map['uid'] = $this->uid;
        $map['type'] = 2;
        if(M('orders')->where($map)->find()){
            $buy = 1;
        }else{
            $buy = 0;
        }
        $this->assign('buy',$buy);
        $this->display('storeLink');
    }
    public function storeLinkPic(){
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

        $name = M('user')->where(array('uid'=>$this->uid))->getField('nickname');
        $color = imagecolorallocate($image, 41, 163, 238);
        $font = THINK_PATH.'Library/Think/Verify/ttfs/msyh.ttf';
        imagettftext($image,14,0,480,815,$color,$font,$name);

        imagedestroy($qr);
        imagedestroy($header);
        $r = imagejpeg($image);
        imagedestroy($image);
    }

    public function linkPic(){
        $type = I('type');
        $map['uid'] = $this->uid;
        $map['type'] = $type;
        if(M('orders')->where($map)->find()){
            $info['title'] = '饭锅伴侣  鲜米现磨';
            $info['summary'] = '现磨现吃，打破传统，安全健康，福旺全家！';
            $info['img'] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Public/images/share.png';
            $info['title2'] = $info['title'];
            $info['summary2'] = $info['summary'];
            $info['img2'] = $info['img'];
            $Wx = A('Wxjs');
            $this->assign('info', $info);
            $this->assign('signPackage', $Wx->GetSignPackage());
            if($type==1){
                $url = U('user/myLinkPic');
                $title = '方正大米推广';
            }else if($type==2){
                $url = U('user/storeLinkPic');
                $title = '鲜米套餐推广！';
            }else{
                $url = '';
                $title = '';
            }
            $this->assign('title',$title);
            $this->assign('url',$url);
            $this->display('linkPic');
        }else{
            $this->error('没有权限');
        }
    }

    /**
     * 购物
     */
    public function buy(){
        if(isset($_POST)){
            $type = I('post.type');
            $gid = I('post.gid');
            $num = I('post.num');
            if(!$num){$this->error('数量格式不对');die;}
            //获取地址信息
            $addr = M('addr')->find(I('addressid'));
            if(!$addr){$this->error('位置信息错误');die;}

            $User = M('user');
            $mapUser['uid'] = $this->uid;
            $userLeftMoney = $User->where($mapUser)->getField('money');
            $goodInfo = M('goods')->where(array('gid'=>$gid))->field('gid,buy_price,left_num,sold_num,status,aid,rate,status,yunfei')->find();
            $needMoney = $goodInfo['buy_price']*$num+ $goodInfo['yunfei'];

            if($type=='wx'){
                $gInfo = M('goods')->where(array('gid'=>$gid))->field('gid,buy_price,left_num,sold_num,status,aid,rate,status,yunfei')->find();
                $getMoney = $gInfo['buy_price']*$num*(100-$gInfo['rate'])/100+$gInfo['yunfei'];

                $order['gid'] = $gid;
                $order['buy_name'] = $addr['name'];
                $order['buy_addr'] = $addr['addr'];
                $order['buy_tel'] = $addr['tel'];
                $order['buy_price'] = $gInfo['buy_price'];
                $order['money'] = $getMoney;
                $order['buy_num'] = $num;
                $order['uid'] = $this->uid;
                $order['create_time'] = time();
                $order['aid'] = $gInfo['aid'];
                $order['status'] = 0;
                $order['type'] = 1;
                $order['needMoney'] = $needMoney;

                $this->pay($order);
            }else if($type=='money'){

                if($needMoney<$userLeftMoney){
                    $order['gid'] = $gid;
                    $order['buy_name'] = $addr['name'];
                    $order['buy_addr'] = $addr['addr'];
                    $order['buy_tel'] = $addr['tel'];
                    $order['type'] = 1;
                    $this->handleBuy($gid,$num,$goodInfo,$order);
                    session('cart',null);
                    $tip = '购买成功后的页面：感谢您对我们的支持！同时欢迎您加入“鲜米现磨”营销团队！只要您动动手，点击方正大米推广生成您的专属二维码推广给您的好友，只要您的好友消费，您就有20%（人民币236元）的利润自动进入您的红包，倡导健康，收获粮薪！还等什么，马上行动吧！';
                    $this->success($tip,U('user/myOrder'));
                }else{
                    $this->error('余额不足',U('user/pay'));
                }

            }
        }
    }

    public function buyProduct(){
        if(isset($_POST['submit'])){
            $uid = session('uid');
            $type = I('post.type');
            $aid = I('aid',0,'number_int');
            $num = I('post.num');

            $pid = I('post.pid');
            $from = M('user')->where(array('uid'=>$uid))->getField('invite_uid');

            if(!$num){$this->error('数量格式不对');die;}
            //获取地址信息
            $addr = M('addr')->find(I('addressid'));
            if(!$addr){$this->error('位置信息错误');die;}

            //获取产品的信息
            $gInfo = M('product')->find($pid);
            if(!($gInfo && $gInfo['status']==1)){$this->error('产品已下架');die;}

            if($type=='wx'){    //微信支付
                $userNeedMoney = $gInfo['price']*$num;

                $order['gid'] = $pid;
                $order['buy_price'] = $gInfo['price'];
                $order['buy_num'] = $num;
                $order['uid'] = $uid;
                $order['aid'] = $aid;
                $order['buy_name'] = $addr['name'];
                $order['buy_addr'] = $addr['addr'];
                $order['buy_tel'] = $addr['tel'];
                $order['create_time'] = time();
                $order['status'] = 0;
                $order['type'] = 2;
                $order['needMoney'] = $userNeedMoney;
                $order['from'] = $from;
                $this->pay($order);
                die;
            }elseif($type=='money'){    //直接付款
                //判断余额
                $userNeedMoney = $gInfo['price']*$num;
                $uInfo = M('user')->field('money')->find($uid);
                if($uInfo['money']<$userNeedMoney){$this->error('账户余额不足');die;}

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

                $order['gid'] = $pid;
                $order['buy_price'] = $userNeedMoney;
                $order['uid'] = $uid;
                $order['aid'] = $aid;
                $order['buy_name'] = $addr['name'];
                $order['buy_addr'] = $addr['addr'];
                $order['buy_tel'] = $addr['tel'];
                $order['create_time'] = time();
                $order['status'] = 1;
                $order['type'] = 2;
                $order['from'] = $from;
                $r1 = M('orders')->add($order);

                $r2 = $User->where($mapUser)->setDec('money',$userNeedMoney);

                $da3['time'] = time();
                $da3['money'] = $userNeedMoney;
                $da3['note'] = '购买商品'.$gInfo['name'];
                $da3['type'] = '1';
                $da3['uid'] = $uid;
                $r3 = M('usermoney')->add($da3);

                if($aid){   //代理卖出
                    $getMoney = $gInfo[$agent]*$num;
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
                        $tjUser = M('user')->where(array('uid'=>$from))->field('rate,openid')->find();
                        $rate = $tjUser['rate'];
                        if($rate){  //获取到了推广员的佣金比例
                            $getMoney = $userNeedMoney*$rate/100;
                            M('user')->where(array('uid'=>$from))->setInc('money',$getMoney);

                            //添加商家记录
                            $da5['time'] = time();
                            $da5['money'] = $getMoney;
                            $da5['note'] = '推广佣金';
                            $da5['type'] = '4';
                            $da5['uid'] = $from;
                            M('usermoney')->add($da5);
                            $temp['openid'] = $tjUser['openid'];
                            $temp['name'] = M('user')->where(array('uid'=>$order['uid']))->getField('nickname');
                            $temp['money'] = $userNeedMoney;
                            sendOrderTempMsg($temp);
                        }
                    }
                    $r4 = $r5 = 1;
                }

                if($r1 && $r2 && $r3 && $r4 && $r5){
                    $User->commit();
                    $this->success('购买成功',U('user/checkFirstBuyJump'));
                }else{
                    $User->rollback();
                    $this->success('下单失败');
                }
            }

        }else{
            $this->error('页面不存在');
        }

    }

    public function buy1(){
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
                    $tip = '购买成功后的页面：感谢您对我们的支持！同时欢迎您加入“鲜米现磨”营销团队！只要您动动手，点击方正大米推广生成您的专属二维码推广给您的好友，只要您的好友消费，您就有20%（人民币236元）的利润自动进入您的红包，倡导健康，收获粮薪！还等什么，马上行动吧！';
                    $this->success($tip,U('user/myOrder'));
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

    public function pay($order=false){
        if($order){
            $oid = M('orders')->add($order);
            $money = $order['needMoney'];
            $type = $order['type'];
            $body = '支付';
            $attach = '充值';
            $tag = $this->uid;
            $trade_no = creatTradeNum();
            $openId = session('openid');
            $Pay = A('Wechat');
            $order = $Pay->pay($openId,$body,$attach,$trade_no,$money*100,$tag);
            if($order['result_code']=='SUCCESS'){//生成订单信息成功
                $data['uid'] = $this->uid;
                $data['create_time'] = time();
                $data['money'] = $money;
                $data['paytrade'] = $trade_no;
                $data['status'] = 1;
                $data['pay_time'] = 0;
                $data['oid'] = $oid;
                if(M('pay')->add($data)){
                    $this->assign('money',$money);
                    $this->assign('type',$type);
                    $this->display('paySub');die;
                }else{
                    $this->error('操作失败请重试');die;
                }
            }else{
                $this->error('操作失败请重试');die;
            }
        }else{
            if(isset($_POST['money'])){
                $money = I('post.money',0);
                if($money>0){
                    $body = '充值';
                    $attach = '充值';
                    $tag = $this->uid;
                    $trade_no = creatTradeNum();
                    $openId = session('openid');
                    $Pay = A('Wechat');
                    $order = $Pay->pay($openId,$body,$attach,$trade_no,$money*100,$tag);
                    if($order['result_code']=='SUCCESS'){//生成订单信息成功
                        $data['uid'] = $this->uid;
                        $data['create_time'] = time();
                        $data['money'] = $money;
                        $data['paytrade'] = $trade_no;
                        $data['status'] = 1;
                        $data['pay_time'] = 0;
                        $data['oid'] = 0;
                        if(M('pay')->add($data)){
                            $this->assign('money',$money);
                            $this->assign('type',0);
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
    }

    /**
     * 跳转链接，判断是否是有生成代理的权限
     */
    public function checkFirstBuyJump(){
        $map['uid'] = $this->uid;
        $map['type'] = 2;
        $map['status'] = array('gt',0);
        $num = M('orders')->where($map)->count();
        if($num==1){
            layout(false);
            $this->display('checkFirstBuyJump');die;
        }else{
            $this->myOrder();die;
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
