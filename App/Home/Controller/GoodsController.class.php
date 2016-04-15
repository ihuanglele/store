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
        $Tool->getData(M('goods'),'','gid desc','gid,name,img,market_price,buy_price');
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
     * 购买商家机器
     */
    public function machine(){
        $aid = I('get.aid',0,'number_int');
        $info = json_decode(readConf('AgentGoodsInfo'),true);
        $this->assign('aid',$aid);
        $this->assign('info',$info);
        $this->display('machine');
    }

    public function buyMachine(){
        $uid = session('uid');
        if(!$uid) $this->error('请先登录',U('user/login'));
        $aid = I('aid',0,'number_int');
        if(isset($_POST['submit'])){
            var_dump($_POST);
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