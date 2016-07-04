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


  private $uid = null;

  public function _initialize(){
      $uid = session('uid');
      if(!$uid){
          session('jump',$_SERVER['REQUEST_URI']);
          $this->redirect('user/login');die;
      }
    }
    /**
     * 店铺列表
     */
    public function index(){
        $Tool = A('Tool');
        $this->assign('title','大米商城');
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

        $Tool = A('Tool');
        $map['aid'] = $storeid;
        $map['status'] = 2;
        switch($sort){
            case 'sold_num':
                $order = $sort.' desc';
                $title = '热销宝贝';break;
            case 'gid':
                $order = 'gid asc';
                $title = '所有宝贝';break;
            case 'create_time':
                $order = 'gid desc';
                $title = '最新上架';break;
            case 'trends':
                $order = 'gid desc';
                $title = '最新订单';$map['gid'] = array('in',$this->getBuyRecord($storeid));break;
            default:$title = '热销宝贝';
        }

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

    /**
     * 获取一个店铺的最近销售的
     */
    private function getBuyRecord($aid){
        $arr[] = 0;
        $arr = M('orders')->where(array('aid'=>$aid))->getField('gid',true);
        if(!is_array($arr)){
            $arr = array('0');
        }
        return $arr;
    }

    /**
     *显示官方店铺（显示两个分类）
     */
    public function selfStore(){
        $aid = I('get.aid',0,'number_int');
        $listType1 = M('product')->where(array('status'=>1,'type'=>1))->order('pid desc')->field('pid,name,price,img')->select();
        $listType2 = M('product')->where(array('status'=>1,'type'=>2))->order('pid desc')->field('pid,name,price,img')->select();
        $this->assign('listType1',$listType1);
        $this->assign('listType2',$listType2);
        $this->assign('aid',$aid);
        $this->display('selfStore');
    }

}
