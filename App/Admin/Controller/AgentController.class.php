<?php
/**
 * Author: huanglele
 * Date: 2016/4/14
 * Time: 下午 09:59
 * Description:
 */

namespace Admin\Controller;


class AgentController extends CommonController
{
    /**
     * 列出所有的代理
     */
    public function index(){
        $this->checkRole(1);
        $map = array();
        $map['role'] = 3;
        $aid = I('get.aid');
        $this->assign('aid',$aid);
        if($aid){
            $map['aid'] = $aid;
        }

        $storename = I('get.storename');
        if($storename){
            $map['storename'] = array('like','%'.$storename.'%');
        }
        $this->assign('storename',$storename);

        $username = I('get.username');
        if($username){
            $map['username'] = array('like','%'.$username.'%');
        }
        $this->assign('username',$username);

        $status = I('get.status',-1,'number_int');
        if($status>=0){
            $map['status'] = $status;
        }
        $this->assign('status',$status);

        $M = M('admin');
        $order = 'aid desc';
        $this->getData($M,$map,$order);
        $this->assign('Status',C('AdminStatus'));

        $this->display('index');
    }


    /**
     * 设置商品属性
     */
    public function goods(){
        $this->checkRole(1);
        if(isset($_POST['submit'])){
            $data = $_POST;
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
            $data = json_encode($data);
            writeConf('AgentGoodsInfo',$data);
            $this->success('更新成功');
        }else{
            $data = json_decode(readConf('AgentGoodsInfo'),true);
        }
        $this->assign('info',$data);
        $this->display('AgentGoodsInfo');
    }


    public function info(){
        $this->checkRole(1);
        $id = I('get.id');
        $info = M('admin')->find($id);
        if(!$info) $this->error('用户不存在',U('shang'));
        $this->assign('AdminStatus',C('AdminStatus'));
        $this->assign('info',$info);
        $this->display('info');
    }

    /**
     * 添加推手自定义财务
     */
    public function smoney(){
        $this->checkRole(1);
        $status = I('post.status',0,'number_int');
        $amount = I('post.amount',0,'float');
        $aid = I('post.aid',0,'number_int');
        $note = I('post.note','');
        if(!in_array($status,array(4,3)) || $amount==0 || $aid==0)  $this->error('参数错误');

        //扣钱 添加财务记录
        $User = M('Admin');
        $User->startTrans();

        if($status==3){     //扣除
            $r1 = $User->where('aid='.$aid)->setDec('money',$amount);
        }else if($status==4){     //添加
            $r1 = $User->where('aid='.$aid)->setInc('money',$amount);
        }

        $da2['time'] = time();
        $da2['aid'] = $aid;
        $da2['note'] = $note;
        $da2['money'] = $amount;
        $da2['type'] = $status;
        $r2 = M("adminmoney")->add($da2);

        if($r1 && $r2){
            $User->commit();
            $this->success('添加成功');
        }else{
            $User->rollback();
            $this->error('添加失败');
        }
    }


    /**
     * 订单列表
     */
    public function orders(){
        $map = array();
        if($this->role==3){
            $map['aid'] = $this->aid;
        }
        $oid = I('get.oid');
        if($oid){
            $map['oid'] = $oid;
        }
        $this->assign('oid',$oid);

        $status = I('get.status',0,'number_int');
        if($status){
            $map['status'] = $status;
        }
        $this->assign('status',$status);

        $tel = I('get.tel');
        if($tel){
            $map['buy_tel'] = $tel;
        }
        $this->assign('tel',$tel);

        $name = I('get.name');
        if($name){
            $map['buy_name'] = array('like','%'.$name.'%');
        }
        $this->assign('name',$name);


        $M = M('machine');
        $field = 'oid,create_time,status,uid,aid';
        $list = $this->getData($M,$map,'oid desc',$field);
        $uidArr = array('0');
        $aidArr = array('0');
        foreach($list as $v){
            $uidArr[] = $v['uid'];
            $aidArr[] = $v['aid'];
        }

        $uidInfo = M('user')->where(array('uid'=>array('in',$uidArr)))->getField('uid,nickname,headimgurl');
        $aidInfo = M('admin')->where(array('aid'=>array('in',$aidArr)))->getField('aid,storename,username');

        $data = array();
        foreach($list as $v){
            $data[] = array_merge($v,$uidInfo[$v['uid']],$aidInfo[$v['aid']]);
        }

        $this->assign('list',$data);
        $this->assign('OrdersStatus',C('OrdersStatus'));
        $this->assign('GoodsStatus',C('GoodsStatus'));
        $this->display('orders');
    }


    /**
     * 更新代理状态
     */
    public function agentUpdate(){
        $this->checkRole(1);
        if(isset($_POST['submit'])){
            $M = M('admin');
            $aid = I('post.aid');
            $data['status'] = I('post.status');
            $data['aid'] = $aid;
            if($M->save($data)){
                $this->success('更新成功');
            }else{
                $this->error('更新失败');
            }
        }else{
            $this->error('参数错误');
        }

    }


    /**
     * 订单详情
     */
    public function orderDetail(){
        $id = I('get.id');
        $M = M('orders');
        $info = $M->find($id);
        if(!$info) $this->error('页面不存在',U('index'));
        $user = M('user')->field('nickname')->find($info['uid']);
        $goods = M('goods')->field('name as tname')->find($info['gid']);

        $this->assign('info',array_merge($user,$goods,$info));
        $this->assign('OrdersStatus',C('OrdersStatus'));
        $this->display('detail');
    }

    /**
     * 处理兑奖
     */
    public function orderUpdate(){
        if(isset($_POST['submit'])){
            $data = $_POST;
            $data['ex_time'] = time();
            $M = M('orders');
            if($M->save($data)){
                $this->success('处理成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $this->error('参数错误',U('index'));
        }
    }

    /**
     * 显示我的二维码
     */
    public function qrCode(){
        $url = U('store/selfStore',array('aid'=>$this->aid),true,true);
        $url = str_replace('admin.php','index.php',$url);
        $this->assign('url',$url);
        $this->display('qrCode');
    }

}