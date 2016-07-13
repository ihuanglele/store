<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{


    /**
     * 显示首页
     */
    public function index()
    {
        $uid = session('uid');
        if(!$uid){
            session('jump',$_SERVER['REQUEST_URI']);
            $this->redirect('user/login');die;
        }
        $Tool = A('Tool');
        $this->assign('title', '首页');
        $map['role'] = 2;
        $map['status'] = 2;
        $Tool->getData(M('admin'), $map, 'aid desc', 'aid,storename,headimgurl as img');
        $info['title'] = '饭锅伴侣  鲜米现磨';
        $info['summary'] = '现磨现吃，打破传统，安全健康，福旺全家！';
        $info['img'] = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Public/images/share.png';
        $Wx = A('Wxjs');
        $this->assign('info', $info);
        $this->assign('signPackage', $Wx->GetSignPackage());
        $slides = readConf('carouselJson');
        $this->assign('slides',json_decode($slides,true));
        $this->display('index');
    }

    public function slides(){
        $info = readConf('carouselJson');
        var_dump(json_decode($info,true));
    }

    public function menu()
    {
        $m = '{"button":[{"type":"view","name":"大米商城","url":"http://fzstore.91yiso.com"}]}';
        $accsee = getWxAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $accsee;
        $data = array(
            CURLOPT_POSTFIELDS => $m
        );
        $r = myCurl($url, $data);
        var_dump($r);
    }

    public function hb()
    {
        $uid = I('get.uid');
        if($uid) $uid = session('uid');
        $info = M('user')->field('headimgurl,nickname')->find($uid);
        if($info){
            $this->assign('info', $info);
            $this->display('hb');
        }else{
            $this->redirect('user/login');
        }
    }

    public function img()
    {
        $url = 'http://wx.qlogo.cn/mmopen/TTjn2M4VCzJQGSlQ4uyVWdUpjKExCGptS3HDjG0CCibCNQZ8zjmAMDGZr2yiaeuxVbZHndV9HnHqaor8PrPVTIct8OhzPdFq5c/132';

        $file = myCurl($url);
        file_put_contents('img2.jpg', $file);
        $image = new \Think\Image();
        //$image->open('img1.jpg')->thumb(100,100)->save('img.jpg');
    }

    /**
     * 获取购物车里面的数量
     */
    public function getCartNum()
    {
        $cart = session('cart');
        $num = 0;
        if (is_array($cart)) {
            foreach ($cart as $v) {
                $num += $v;
            }
        }
        echo $num;
    }

    /**
     * 添加商品到购物车
     */
    public function addCart()
    {
        $id = I('get.id', 0, 'number_int');
        $num = I('get.num', 0, 'number_int');
        if ($id == 0 || $num == 0) {
            $this->error('参数错误');
        }
        $cart = session('cart');
        if (is_array($cart)) {
            if (array_key_exists($id, $cart)) {
                $cart[$id] += $num;
            } else {
                $cart[$id] = $num;
            }
        } else {
            $cart[$id] = $num;
        }
        session('cart', $cart);
        $this->success('添加成功');
    }

    /**
     * 添加商品到购物车
     */
    public function delCart()
    {
        $id = I('get.id', 0, 'number_int');
        if ($id == 0) {
            $this->error('参数错误');
        }
        $cart = session('cart');
        if (is_array($cart)) {
            if (array_key_exists($id, $cart)) {
                unset($cart[$id]);
            }
        }
        session('cart', $cart);
        $this->success('移除成功');
    }


    /**
     * 本地自动登录
     */
    public function login()
    {
        $uid = 8;
        $openId = 'ocFtMwKcWuWZQKVIjhAezFBqlg-8';
        session('uid', $uid);
        session('openid', $openId);
    }

    /**
     * 申请代理
     */
    public function applyAgent()
    {
        if (isset($_POST['submit'])) {
            $data = $_POST;
            $M = D('Admin');
            if ($M->create($data)) {
                $data['password'] = md5($data['password']);
                $data['time'] = time();
                $data['role'] = 3;
//                $data['status'] = readConf('adminDefaultStatus');
                $data['status'] = 1;
                $data['create_time'] = time();
                $data['rate'] = readConf('adminDefaultRate') ? readConf('adminDefaultRate') : 5;

                $uplaod = new \Think\Upload(C('UploadConfig'));
                $file = $uplaod->upload();
                if (!$file) {
                    $this->error($uplaod->getError());
                }
                if (!isset($file['img'])) {
                    $this->error('请上传店铺图片');
                } else {
                    $data['headimgurl'] = $file['img']['savepath'] . $file['img']['savename'];
                    $image = new \Think\Image();
                    $image->open('./upload/' . $data['headimgurl']);
                    $image->thumb(150, 150, 2)->save('./upload/' . $data['headimgurl']);
                }

                if ($M->add($data)) {
//                    sendAdminEmail('reg');
                    $this->success('申请成功', U('index'));
                } else {
                    $this->error('申请失败');
                }
            } else {
                $this->error($M->getError());
            }
        } else {
            $this->assign('title', '申请代理');
            $this->display('applyAgent');
        }
    }


    public function game()
    {
        layout(false);
        C('SHOW_PAGE_TRACE', 0);
        $WxJS = A('Wxjs');
        $this->assign('signPackage', $WxJS->GetSignPackage());   //js分享
        $this->display('game');
    }

}
