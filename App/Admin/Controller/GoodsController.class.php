<?php
/**
 * Author: huanglele
 * Date: 2016/1/27
 * Time: 19:30
 * Description:
 */

namespace Admin\Controller;
use Admin\Controller;

class GoodsController extends CommonController
{
    /**
     * 列出所有商品
     */
    public function index(){
        $map = array();
        if($this->role != 1){
            $map['aid'] = $this->aid;
        }
        $gid = I('get.gid','','number_int');
        if($gid){
            $map['gid'] = $gid;
        }
        $this->assign('gid',$gid);

        $name = I('get.name');
        if($name){
            $map['name'] = array('like','%'.$name.'%');
        }
        $this->assign('name',$name);

        $status = I('get.status',0,'number_int');
        if($status){
            $map['status'] = $status;
        }
        $this->assign('status',$status);

        $M = M('goods');
        $count = $M->where($map)->count();
        $Page = new \Think\Page($count,25);
        $show = $Page->show();
        $list = $M->where($map)->field('gid,name,status,aid,left_num,sold_num')->order('gid desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);
        $this->assign('GoodsStatus',C('GoodsStatus'));
        $this->assign('list',$list);
        $this->display('index');
    }

    /**
     * 添加
     */
    public function add(){
        $this->checkRole(2);
        $this->assign('GoodsType',json_decode(readConf('goodsType'),true));
        $this->assign('rate',M('admin')->where(array('aid'=>$this->aid))->getField('rate'));
        $this->display();
    }

    /**
     * 管理员查看一个商品【管】
     */
    public function view(){
        $this->checkRole(1);
        $id = I('get.id');
        $info = M('goods')->find($id);
        if($info){
            $info['imgs'] = json_decode($info['imgs'],true);
            $this->assign('info',$info);
            $this->assign('GoodsType',json_decode(readConf('goodsType'),true));
            $this->assign('GoodsStatus',C('GoodsStatus'));
            $this->display();
        }else{
            $this->error('参数错误',U('index'));
        }
    }

    /**
     * 管理员修改商品状态【管】
    public function handle(){
        $this->checkRole(1);
        if(isset($_POST['submit'])){
            $gid = I('post.gid');
            $status = I('post.status');
            if(M('goods')->where(array('gid'=>$gid))->setField('status',$status)){
                sendTaskTempMsg($gid,'goods');        //发送商家任务结束模板消息
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $this->error('参数错误');
        }
    }
     *
     */

    /**
     * 修改一个商品【商家】
     */
    public function editor(){
        $this->checkRole(2);
        $id = I('get.id');
        $info = M('goods')->find($id);
        if($info){
            if($info['aid']!=$this->aid) $this->error('页面不存在');
            $this->assign('info',$info);
            $this->assign('rate',M('admin')->where(array('aid'=>$this->aid))->getField('rate'));
            $this->assign('GoodsType',json_decode(readConf('goodsType'),true));
            $this->assign('GoodsStatus',C('GoodsStatus'));
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
            $M = D('Goods');
            if(!$M->create($data))  $this->error($M->getError());

            //判断是否有文件上传
            if($_FILES['file']['error']==0){
                //处理图片
                $upload = new \Think\Upload(C('UploadConfig'));
                $info   =   $upload->upload();
                if($info) {
                    $data['img'] = $info['file']['savepath'].$info['file']['savename'];
                    $image = new \Think\Image();
                    $image->open('./upload/'.$data['img']);
                    $image->thumb(200,200,2)->save('./upload/'.$data['img']);
                }else{
                    $this->error($upload->getError());
                }
            }

            if($ac == 'add'){
                $this->checkRole(2);    //只有商家才能添加商品
                if($data['status']==4)  $this->error('非法操作');
                $data['aid'] = $this->aid;
                //查找该商家的平台分成
                $data['rate'] = M('admin')->where(array('aid'=>$this->aid))->getField('rate');
                if($M->add($data)){
                    $this->success('添加成功',U('index'));
                }else{
                    $this->error('添加失败请重试');
                }
            }elseif($ac == 'update'){
                $gid = I('post.gid');
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