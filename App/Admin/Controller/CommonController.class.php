<?php
/**
 * Author: huanglele
 * Date: 2016/3/29
 * Time: 上午 09:31
 * Description:
 */

namespace Admin\Controller;
use Think\Controller;

class CommonController extends Controller
{

    public function _initialize(){
        header("Content-type:text/html;charset=utf-8");
        $acFuns = array('login','register');
        $action = strtolower(ACTION_NAME);
        if(in_array($action,$acFuns)){
            //不需要登录的操作
        }else{
            $aid = session('aid');
            if(!$aid){
                layout(false);
                $this->display('Public/login');
                exit;
            }else{
                $this->aid = $aid;
                $this->role = session('role');
                $this->assign('role',$this->role);
            }
        }
    }

    public function _empty(){
        $this->index();
    }

    /**
     * @param $role 角色
     * @return bool
     */
    public function checkRole($role){
        if($this->role==$role){
            return true;
        }else{
            $this->error('无此操作权限');die;
        }
    }

    /**
     * 管理员登录
     */
    public final function login(){
        layout(false);
        if(isset($_POST['submit'])){
            $user = I('post.user');
            $pw = I('post.pw','');
            $map['username'] = $user;
            $map['password'] = md5($pw);
            $info = M('Admin')->field('username,status,aid,role')->where($map)->find();
            if($info){
                if($info['role']==0){
                    session('aid',$info['aid']);
                    session('user',$info['user']);
                    session('role',$info['role']);
                    $this->success('欢迎回来管理员',U('index/index'));
                }else{
                    if($info['status']==1){
                        $this->error('用户等待审核');
                    }elseif($info['status']==2){
                        session('aid',$info['aid']);
                        session('user',$info['user']);
                        session('role',$info['role']);
                        $this->success('登录成功',U('index/index'));
                    }elseif($info['status']==3){
                        $this->error('该账户已被封，请联系管理员',U('index/index'));
                    }
                }
            }else{
                $this->error('用户名不存在或者密码错误');
            }
        }else {
            $this->display('Public/login');
        }
    }

    /**
     * 商家注册
     */
    public final function register(){
        layout(false);
        if(!readConf('openshangRegister')){
            $this->error('网站暂时关闭了商家注册',U('common/login'));
        }
        if(isset($_POST['submit'])){
            $data = $_POST;
            $M = D('Admin');
            if($M->create($data)){
                $data['password'] = md5($data['password']);
                $data['time'] = time();
                $data['role'] = 2;
//                $data['status'] = readConf('adminDefaultStatus');
                $data['status'] = 2;
                $data['rate'] = readConf('adminDefaultRate')?readConf('adminDefaultRate'):5;

                $uplaod = new \Think\Upload(C('UploadConfig'));
                $file = $uplaod->upload();
                if(!$file){
                    $this->error($uplaod->getError());
                }
                if(!isset($file['img'])){
                    $this->error('请上传店铺图片');
                }else{
                    $data['headimgurl'] = $file['img']['savepath'].$file['img']['savename'];
                }

                if($M->add($data)){
//                    sendAdminEmail('reg');
                    $this->success('注册成功',U('login'));
                }else{
                    $this->error('注册失败');
                }
            }else{
                $this->error($M->getError());
            }
        }else{
            $this->display('Public/register');
        }
    }

    /**
     * 退出登录
     */
    public final function logout(){
        cookie('name',null);
        cookie('code',null);
        $this->success('成功退出登录',U('Index/login'));
    }

    /**
     * 查询数据库的数据
     * @param $M    数据库
     * @param $map  条件
     * @param $order 排序
     */
    protected function getData($M,$map,$order,$field=false){
        $count = $M->where($map)->count();
        $Page = new\Think\Page($count,25);
        $show = $Page->show();
        if($field){
            $list = $M->where($map)->field($field)->order($order)->limit($Page->firstRow,$Page->listRows)->select();
        }else{
            $list = $M->where($map)->order($order)->limit($Page->firstRow,$Page->listRows)->select();
        }
        $this->assign('list',$list);
        $this->assign('page',$show);
        return $list;
    }

    /**
     * @param $M    需要查询的数据库
     * @param $map  查询条件
     * @return mixed 返回一共数据
     */
    protected function getCount($table,$map){
        return M($table)->where($map)->count();
    }

}