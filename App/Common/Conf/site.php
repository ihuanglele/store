<?php

return array(
    //网站名字
    'SITE_NAME' => '掉馅饼',

    //微信支付配置项
    'Wx' => array(
        'AppID' => 'wxe409dbb21ee56d87',        //微信APPID
        'AppSecret' => 'c553169976c19b13b511896141dadc2c',  //微信APPsecret
        'key' => 'WeixingTianxia043187956777wxtx00',
        'mch_id' => '1312824001', //商户号
        'notify_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/index.php/wechat/notify',
        'SSLCERT_PATH' => LIB_PATH . "Org/Wxpay/apiclient_cert.pem",
        'SSLKEY_PATH' => LIB_PATH . "Org/Wxpay/apiclient_key.pem",
        'CURL_PROXY_HOST' => "0.0.0.0",
        'CURL_PROXY_PORT' => 0,
        'REPORT_LEVENL' => 1,
    ),
    //微信公众号
    'Wechat' => array(
        'name' => '联合推客',
        'num' => '',
    ),


    //电话
    'TelNum' => '010-6668888',
    //公司名字
    'IncName' => '联合推客信息技术有限公司',

    //邮件配置
    'THINK_EMAIL' => array(
        'SMTP_HOST' => 'smtp.qq.com', //SMTP服务器  QQ邮箱为 smtp.qq.com
        'SMTP_PORT' => '465', //SMTP服务器端口     QQ邮箱为 465
        'SMTP_USER' => '2361547577', //SMTP服务器用户名 QQ邮箱为 QQ号
        'SMTP_PASS' => 'gkjdaedndlksebcb', //SMTP服务器密码
        'FROM_EMAIL' => 'itaozhushou@qq.com', //发件人EMAIL  加上 username@xx.xxx
        'FROM_NAME' => '网站通知', //发件人名称
        'REPLY_EMAIL' => '', //回复EMAIL（留空则为发件人EMAIL）
        'REPLY_NAME' => '', //回复名称（留空则为发件人名称）
    ),

);