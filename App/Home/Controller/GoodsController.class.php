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
        $this->display('product');
    }

    public function buyProduct(){
        $uid = session('uid');
        if(!$uid) $this->error('请先登录',U('user/login'));
        $aid = I('aid',0,'number_int');
        if(isset($_POST['submit'])){
            $pid = I('post.pid');
            $from = I('post.from');

            //获取产品的信息
            $gInfo = M('produce')->find($pid);
            if(!$gInfo || $gInfo['status']!=1){$this->error('产品已下架');die;}


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