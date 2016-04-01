<?php
/**
 * Author: huanglele
 * Date: 2016/1/27
 * Time: 17:00
 * Description:
 */

namespace Admin\Model;
use Think\Model;

class AdminModel extends Model
{
    protected $_validate = array(
//        array(验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间]),
        array('username','require','请填写用户名'), //默认情况下用正则进行验证
        array('storename','require','请填写用户名'), //默认情况下用正则进行验证
        array('email','require','请填写邮箱'), //默认情况下用正则进行验证
        array('password','require','请填写密码'), //默认情况下用正则进行验证
        array('username','','用户名已经存在！',0,'unique',1),
        array('storename','','用户名已经存在！',0,'unique',1),
        array('email','','邮箱已经存在！',0,'unique',1),
        array('email','email','邮箱格式不正确！'),

        array('password2','password','两次密码不一致',0,'confirm'),
    );
}