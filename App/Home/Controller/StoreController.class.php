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
        $Tool = A('Tool');
        $map['role'] = 2;
        $map['status'] = 2;
        $Tool->getData(M('admin'),$map,'aid desc','aid,storename,headimgurl as img');
        $this->display('index');
    }

    /**
     *显示某个店铺
     */
    public function store(){
        $id = I('get.id');
        $info = M('admin')->find($id);
        if(!$info || $info['role']!=2)$this->error('页面不存在',U('index'));
        if($info['status']!=2) $this->error('页面不存在',U('index'));

        $this->assign('info',$info);
        $this->display('store');
    }

    /**
     * Ajax获取商品列表
     */
    public function getGoods(){
        $storeid = I('get.storeid');
        $sort = I('get.sort');
        $p = I('get.p',1,'number_int');
        switch($sort){
            case 'sold_num':$title = '热销宝贝';break;
            case 'gid':$title = '所有宝贝';break;
            default:$title = '热销宝贝';
        }
        $order = $sort.' desc';
        //获取热销商品
        $Tool = A('Tool');
        $map['aid'] = $storeid;
        $map['status'] = 2;
        $list = $Tool->getData(M('goods'),$map,$order,'gid,name,img,market_price,buy_price');
        $num = count($list);
        $data['title'] = $title;
        $data['num'] = $num;
        if($num==10) $p++;
        $data['p'] = $p;
        $data['list'] = $list;
        $data['status'] = 1;
        $this->ajaxReturn($data);
    }

}