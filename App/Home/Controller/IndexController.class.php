<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{

    /**
     * 显示首页
     */
    public function index(){
        $Tool = A('Tool');
        $map['role'] = 2;
        $map['status'] = 2;
        $Tool->getData(M('admin'),$map,'aid desc','aid,storename,headimgurl as img');
        $this->display('index');
    }



    /**
     * 获取购物车里面的数量
     */
    public function getCartNum(){
        $cart = session('cart');
        $num = 0;
        if(is_array($cart)){
            foreach($cart as $v){
                $num += $v;
            }
        }
        echo $num;
    }

    /**
     * 添加商品到购物车
     */
    public function addCart(){
        $id = I('get.id',0,'number_int');
        $num = I('get.num',0,'number_int');
        if($id==0 || $num==0){$this->error('参数错误');}
        $cart = session('cart');
        if(is_array($cart)){
            if(array_key_exists($id,$cart)){
                $cart[$id] += $num;
            }else{
                $cart[$id] = $num;
            }
        }else{
            $cart[$id] = $num;
        }
        session('cart',$cart);
        $this->success('添加成功');
    }


    /**
     * 本地自动登录
     */
    public function login(){
        $uid = 1;
        $openId = 'oqAACwZkLzSmZjrn_aTpQfY36-rg';
        session('uid',$uid);
        session('openid',$openId);
    }

}