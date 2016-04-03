<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{

    /**
     * 显示首页
     */
    public function index()
    {
        $this->display('index');
    }



    /**
     * 获取购物车里面的数量
     */
    public function getCartNum(){

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