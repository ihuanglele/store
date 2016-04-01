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


    //查看自己的财务
    public function money(){
        $this->checkRole(1);
        $map = array();
        $mid = I('get.mid','','number_int');
        $this->assign('mid',$mid);
        if($mid){
            $map['mid'] = $mid;
        }
        $map['aid'] = $this->aid;
        $M = M('smoney');

        $type = I('get.type',0,'number_int');
        if($type){
            $map['type'] = $type;
        }
        $this->assign('type',$type);
        $this->assign('MoneyType',C('SMoneyType'));
        $order = 'mid desc';
        $this->getData($M,$map,$order);
        $this->display('money');
    }



}