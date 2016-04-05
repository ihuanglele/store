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
        $id = I('get.id');
        $info = M('goods')->find($id);
        if(!$info){$this->error('页面不存在',U('goods/index'));}
        $this->assign('info',$info);
        $this->display();
    }

}