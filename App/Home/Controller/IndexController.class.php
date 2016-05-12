<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{

    /**
     * 显示首页
     */
    public function index(){
        $Tool = A('Tool');
        $this->assign('title','首页');
        $map['role'] = 2;
        $map['status'] = 2;
        $Tool->getData(M('admin'),$map,'aid desc','aid,storename,headimgurl as img');
        $this->display('index');
    }



    /**
     * 获取购物车里面的数量
     */
    public function getCartNum(){
        $cart = session('cart');
        $num = 0;
        if(is_array($cart)){
            foreach($cart as $v){
                $num += $v;
            }
        }
        echo $num;
    }

    /**
     * 添加商品到购物车
     */
    public function addCart(){
        $id = I('get.id',0,'number_int');
        $num = I('get.num',0,'number_int');
        if($id==0 || $num==0){$this->error('参数错误');}
        $cart = session('cart');
        if(is_array($cart)){
            if(array_key_exists($id,$cart)){
                $cart[$id] += $num;
            }else{
                $cart[$id] = $num;
            }
        }else{
            $cart[$id] = $num;
        }
        session('cart',$cart);
        $this->success('添加成功');
    }


    /**
     * 本地自动登录
     */
    public function login(){
        $uid = 1;
        $openId = 'oqAACwZkLzSmZjrn_aTpQfY36-rg';
        session('uid',$uid);
        session('openid',$openId);
    }

    /**
     * 申请代理
     */
    public function applyAgent(){
        if(isset($_POST['submit'])){
            $data = $_POST;
            $M = D('Admin');
            if($M->create($data)){
                $data['password'] = md5($data['password']);
                $data['time'] = time();
                $data['role'] = 3;
//                $data['status'] = readConf('adminDefaultStatus');
                $data['status'] = 1;
                $data['create_time'] = time();
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
                    $image = new \Think\Image();
                    $image->open('./upload/'.$data['headimgurl']);
                    $image->thumb(150,150,2)->save('./upload/'.$data['headimgurl']);
                }

                if($M->add($data)){
//                    sendAdminEmail('reg');
                    $this->success('申请成功',U('index'));
                }else{
                    $this->error('申请失败');
                }
            }else{
                $this->error($M->getError());
            }
        }else {
            $this->assign('title','申请代理');
            $this->display('applyAgent');
        }
    }

    public function test(){
        echo THINK_PATH;
        var_dump($_SERVER);
    }

    public function game(){
        layout(false);
        C('SHOW_PAGE_TRACE',0);
        $WxJS = A('Wxjs');
        $this->assign('signPackage',$WxJS->GetSignPackage());   //js分享
        $this->display('game');
    }

}