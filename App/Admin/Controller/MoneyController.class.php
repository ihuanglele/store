<?php
/**
 * Author: huanglele
 * Date: 2016/1/28
 * Time: 20:43
 * Description:
 */

namespace Admin\Controller;
use Admin\Controller;

class MoneyController extends CommonController
{
    public function _initialize(){
        parent::_initialize();
        $this->checkRole(1);
    }

    /**
     * 用户财务
     */
    public function index(){
        $this->assign('MoneyType',C('UMoneyType'));
        $map = array();
        $id = I('get.id','','number_int');
        $this->assign('id',$id);
        if($id){
            $map['uid'] = $id;
        }
        $M = M('usermoney');
        $order = 'mid desc';
        $list = $this->getData($M,$map,$order);
        //获取用户的昵称和图像
        $uids[] = 0;
        foreach($list as $v){
            $uids[] = $v['uid'];
        }
        $users = M('user')->where(array('uid'=>array('in',$uids)))->getField('uid,nickname,headimgurl');
//        var_dump($users);
        $this->assign('Users',$users);
        $this->display('index');
    }

    public function shang(){
        $this->assign('MoneyType',C('SMoneyType'));
        $map = array();
        $id = I('get.id','','number_int');
        $this->assign('id',$id);
        if($id){
            $map['aid'] = $id;
        }
        $M = M('smoney');
        $order = 'mid desc';
        $this->getData($M,$map,$order);
        $this->display('shang');
    }


    /**
     * 微信账单
     */
    public function wxpay(){
        $map = array();
        $uid = I('get.uid');
        $this->assign('uid',$uid);
        if($uid){
            $map['uid'] = $uid;
        }

        $pid = I('get.pid');
        $this->assign('pid',$pid);
        if($pid){
            $map['pid'] = $pid;
        }

        $trade = I('get.trade');
        $this->assign('trade',$trade);
        if($trade){
            $map['trade'] = $trade;
        }

        $status = I('get.status',-1,'number_int');
        $this->assign('status',$status);
        if($status>=0){
            $map['status'] = $status;
        }

        $this->getData(M('pay'),$map,'pid desc');
        $this->assign('PayStatus',C('PayStatus'));
        $this->display('wxpay');
    }

}