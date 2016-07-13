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

    private $uid = null;

    public function _initialize()
    {
        $uid = session('uid');
        if (!$uid) {
            session('jump', $_SERVER['REQUEST_URI']);
            $this->redirect('user/login');
            die;
        }

        $info['title'] = '饭锅伴侣  鲜米现磨';
        $info['summary'] = '现磨现吃，打破传统，安全健康，福旺全家！';
        $info['img'] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Public/images/share.png';
        $info['title2'] = '关注方正大米，每天分享天天赚红包！';
        $info['summary2'] = '关注方正大米，每天分享天天赚红包！';
        $info['img2'] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Public/images/logo.png';

        $Wx = A('Wxjs');
        $this->assign('info', $info);
        $this->assign('signPackage', $Wx->GetSignPackage());
    }

    /**
     * 所有商品
     */
    public function index(){
        $Tool = A('Tool');
        $map['status'] = 2;
        $name = I('get.name');
        if($name){
            $map['name'] = array('like','%'.$name.'%');
        }
        $this->assign('name',$name);
        $list = $Tool->getData(M('goods'),$map,'gid desc','gid,aid,name,img,market_price,buy_price');
        $aidArr[] = 0;
        foreach($list as $v) $aidArr[] = $v['aid'];
        $StoreName = M('admin')->where(array('aid'=>array('in',$aidArr)))->getField('aid,storename');
        $this->assign('StoreName',$StoreName);
        $this->display('index');
    }


    /**
     * 显示单个商品
     */
    public function item(){
        layout(false);
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

        //获取收货地址
        $mapA['uid'] = session('uid');
        $addr = M('addr')->where($mapA)->getField('id,name,tel,addr');
        $this->assign('addr',$addr);
        $this->assign('addrsJosn',json_encode($addr));
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
            $Tool->getData(M('product'),$map,'price asc','pid,name,img,price,tip');
        }else{
            $map['type'] = 1;
            $list1 = $Tool->getData(M('product'),$map,'price asc','pid,name,img,price,tip');
            $this->assign('list1',$list1);

            $map['type'] = 2;
            $list2 = $Tool->getData(M('product'),$map,'price asc','pid,name,img,price,tip');
            $this->assign('list2',$list2);
        }
        $tpl = 'selfList.'.$type;
        $this->display($tpl);
    }

    /**
     * 购买商家机器
     */
    public function product(){
        layout(false);
        $aid = I('get.aid',0,'number_int');
        $id = I('get.id',0,'number_int');
        $info = M('product')->find($id);
        $this->assign('aid',$aid);
        $this->assign('info',$info);
        $this->assign('title',$info['name']);

        //获取收货地址
        $mapA['uid'] = session('uid');
        $addr = M('addr')->where($mapA)->getField('id,name,tel,addr');
        $this->assign('addr',$addr);
        $this->assign('addrsJosn',json_encode($addr));
        $this->display('product');
    }



}
