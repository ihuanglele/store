<?php
/**
 * Author: huanglele
 * Date: 2016/4/1
 * Time: 下午 09:49
 * Description:
 */

namespace Home\Controller;
use Think\Controller;

class StoreController extends Controller
{

    /**
     * 店铺列表
     */
    public function index(){

    }

    /**
     *显示某个店铺
     */
    public function store(){
        $this->display('store');
    }

}