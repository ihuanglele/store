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
        $aid = I('get.aid',0,'number_int');
        $this->assign('aid',$aid);
        $type = I('get.type',0,'number_int');
        $Tool = A('Tool');
        $map['status'] = 1;
        if(!$type){
            $map['type'] = $type;
        }
        $this->assign('type',$type);
        $TitleArr = array('所有商品','糙米专区','套餐组合');
        $this->assign('Title',$TitleArr[$type]);
        $Tool->getData(M('product'),$map,'pid desc','pid,name,img,price');
        $this->display('selfList');
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