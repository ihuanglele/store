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

}