<?php
/**
 * Author: huanglele
 * Date: 2016/4/18
 * Time: 下午 01:44
 * Description:
 */

namespace Admin\Controller;


class ProductController extends CommonController
{
    public function _initialize(){
        parent::_initialize();
        $this->checkRole(1);
    }

    /**
     * 显示所有的产品
     */
    public function index(){
        $map = array();

        $name = I('get.name');
        if($name){
            $map['name'] = array('like','%'.$name.'%');
        }
        $this->assign('name',$name);

        $type = I('get.type',0,'number_int');
        if($type){
            $map['type'] = $type;
        }
        $this->assign('type',$type);

        $status = I('get.status',0,'number_int');
        if($status){
            $map['status'] = $status;
        }
        $this->assign('status',$status);

        $M = M('product');
        $count = $M->where($map)->count();
        $Page = new \Think\Page($count,25);
        $show = $Page->show();
        $list = $M->where($map)->field('pid,price,name,status,type')->order('pid desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);
        $this->assign('ProductStatus',C('ProductStatus'));
        $this->assign('ProductType',C('ProductType'));
        $this->assign('list',$list);
        $this->display('index');
    }

    /**
     * 添加
     */
    public function add(){
        $this->checkRole(1);
        $this->assign('ProductType',C('ProductType'));
        $this->display();
    }

    /**
     * 修改一个商品【商家】
     */
    public function editor(){
        $id = I('get.id');
        $info = M('product')->find($id);
        if($info){
            $this->assign('info',$info);
            $this->assign('ProductType',C('ProductType'));
            $this->assign('ProductStatus',C('ProductStatus'));
            $this->display();
        }else{
            $this->error('参数错误',U('index'));
        }
    }

    /**
     * 添加商品 或者修改商品 处理表单
     */
    public function update(){
        if(isset($_POST['submit'])){
            $ac = I('post.submit');
            $data = $_POST;
            $M = D('Product');
            if(!$M->create($data))  $this->error($M->getError());

            //判断是否有文件上传
            if($_FILES['file']['error']==0){
                //处理图片
                $upload = new \Think\Upload(C('UploadConfig'));
                $info   =   $upload->upload();
                if($info) {
                    $data['img'] = $info['file']['savepath'].$info['file']['savename'];
                    //$image = new \Think\Image();
                    //$image->open('./upload/'.$data['img']);
                    //$image->thumb(200,200,2)->save('./upload/'.$data['img']);
                }else{
                    $this->error($upload->getError());
                }
            }

            if($ac == 'add'){
                if($M->add($data)){
                    $this->success('添加成功',U('index'));
                }else{
                    $this->error('添加失败请重试');
                }
            }elseif($ac == 'update'){
                $gid = I('post.pid');
                if(!$gid)   $this->error('参数错误',U('index'));
                if($M->save($data)){
                    $this->success('更新成功',U('index'));
                }else{
                    $this->error('更新失败请重试');
                }
            }
        }else{
            $this->error('页面不存在',U('index'));
        }
    }



}