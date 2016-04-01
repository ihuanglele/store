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
        $this->display();
    }


    /**
     * 显示单个商品
     */
    public function item(){
        $this->display();
    }

}