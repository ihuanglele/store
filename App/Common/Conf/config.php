<?php

return array(
    //加载网站设置配置文件
    'LOAD_EXT_CONFIG' => 'site',
    //显示页面调试TRACE
    'SHOW_PAGE_TRACE' => TRUE,
    'URL_CASE_INSENSITIVE' => true,

    //默认访问控制器
    'DEFAULT_CONTROLLER' => 'Index',

    //数据库连接信息
    'DB_HOST' => '127.0.0.1',
    'DB_TYPE' => 'mysql',
    'DB_USER' => 'fzstore',
    'DB_PWD' => 'fzstore123',
    'DB_PORT' => '3306',
    'DB_NAME' => 'fzstore',
    'DB_PREFIX' => 'fz_',

    //设置模板标识符
    'TMPL_L_DELIM' => '<{',
    'TMPL_R_DELIM' => '}>',

    //图片路径
    'imgHost' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '/../upload/',

    //商品分类
    'GoodsType' => array(
        '1' => '手机数码',
        '2' => '家电护理',
        '3' => '潮流时尚',
        '4' => '电脑办公',
        '5' => '金银首饰',
        '6' => '汽车',
        '7' => '其他',
    ),


    //文件上传配置
    'UploadConfig' => array(
        'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
        'exts' => array('jpg', 'gif', 'png', 'jpeg'),// 设置附件上传类型
        'autoSub' => true, //自动子目录保存文件
        'subName' => array('date', 'Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath' => './upload/', //保存根路径
        'savePath' => '', //保存路径
        'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt' => '', //文件保存后缀，空则使用原后缀
        'replace' => false, //存在同名是否覆盖
        'hash' => true, //是否生成hash编码
        'callback' => false, //检测文件是否存在回调，如果存在返回文件信息数组
        'driver' => '', // 文件上传驱动
    ),

    //跳转模板
    'TMPL_ACTION_SUCCESS' => 'Public:dispatch_jump',
    'TMPL_ACTION_ERROR' => 'Public:dispatch_jump',

    //商家状态
    'AdminStatus' => array(
        '1' => '待审核',
        '2' => '正常',
        '3' => '限制',
    ),
    //后台角色
    'AdminRole' => array(
        '1' => '管理员',
        '2' => '商家',
    ),

    //商品的状态
    'GoodsStatus' => array(
        '1' => '上架',
        '2' => '下架',
        '3' => '禁售',
    ),

    //订单状态
    'OrdersStatus' => array(
        '1' => '待发货',
        '2' => '已发货',
        '3' => '已取消',
    ),

    //用户财务记录类型
    'UserMoneyType' => array(
        '1' => '支出',
        '2' => '退款',
        '3' => '充值',
        '4' => '奖励',
        '5' => '提现',
    ),
    //商家财务记录类型
    'AdminMoneyType' => array(
        '1' => '收入',
        '2' => '退款',
        '3' => '提现',
    ),


);