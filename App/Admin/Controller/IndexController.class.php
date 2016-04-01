<?php
namespace Admin\Controller;


class IndexController extends CommonController {


    public function index(){
        $this->display('index');
    }

    //修改密码
    public function pwd(){
        if(isset($_POST['submit'])){
            $pwd = I('post.pwd');
            $newpwd = I('post.newpwd');
            $repwd = I('post.repwd');
            if((!$pwd || !$newpwd || !$repwd)) $this->error('请把表单填写完整');
            if($pwd == $newpwd) $this->error('新旧密码不能相同');
            if($newpwd != $repwd)   $this->error('两次新密码不同');
            $M = M('Admin');
            $map['aid'] = $this->aid;
            $map['password'] = md5($pwd);
            $id = $M->where($map)->getField('aid');
            if(!$id) $this->error('原密码错误');
            $data['aid'] = $this->aid;
            $data['password'] = md5($newpwd);
            if($M->save($data)){
                $this->success('修改成功',U('index'));
            }else{
                $this->error('修改失败');
            }
        }else{
            $this->display('Admin/pwd');
        }
    }

    /**
     * 查看自己的店铺信息
     */
    public function store(){
        $this->checkRole(2);
        $M = D('Admin');
        if(isset($_POST['submit'])){
            $data = $_POST;
            $data['aid'] = $this->aid;
            if($M->create($data)){
                //判断是否有文件上传
                if($_FILES['file']['error']==0){
                    //处理图片
                    $upload = new \Think\Upload(C('UploadConfig'));
                    $info   =   $upload->upload();
                    if($info) {
                        $data['headimgurl'] = $info['file']['savepath'].$info['file']['savename'];
                        $image = new \Think\Image();
                        $image->open('./upload/'.$data['headimgurl']);
                        $image->thumb(150,150,2)->save('./upload/'.$data['headimgurl']);
                    }else{
                        $this->error($upload->getError());
                    }
                }
                if($M->save($data)){
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }else{
                $this->error($M->getError());
            }
        }else{
            $info = $M->find($this->aid);
            $this->assign('info',$info);
            $this->display('store');
        }
    }


    //查看自己的财务
    public function money(){
        $this->checkRole(2);
        $map = array();
        $mid = I('get.mid','','number_int');
        $this->assign('mid',$mid);
        if($mid){
            $map['mid'] = $mid;
        }
        $map['aid'] = $this->aid;
        $M = M('adminmoney');

        $type = I('get.type',0,'number_int');
        if($type){
            $map['type'] = $type;
        }
        $this->assign('type',$type);
        $this->assign('MoneyType',C('AdminMoneyType'));
        $order = 'mid desc';
        $this->getData($M,$map,$order);
        $this->display('money');
    }



}