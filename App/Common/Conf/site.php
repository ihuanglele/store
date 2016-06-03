<?php

return array(
    //网站名字
    'SITE_NAME' => '方正大米',

    //微信支付配置项
    'Wx' => array(
        'AppID' => 'wxed5b296d4910abe6',        //微信APPID
        'AppSecret' => '80bd81e91b99deae2f895c0179aa7e54',  //微信APPsecret
        'Token' => 'Z60z6Z6Q1aavK30K0GVv460t30bnA606',       //微信Token(令牌)
        'EncodingAESKey' => 'k6GOifBwDFG0IMEUDv9KDejLvEVnQh8A0XwsBfyDskH',//微信消息加解密密钥
        'key' => 'WeixingTianxia043187956777wxtx00',
        'mch_id' => '1331093701', //商户号
        'notify_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/index.php/wechat/notify',
        'SSLCERT_PATH' => LIB_PATH . "Org/Wxpay/apiclient_cert.pem",
        'SSLKEY_PATH' => LIB_PATH . "Org/Wxpay/apiclient_key.pem",
        'CURL_PROXY_HOST' => "0.0.0.0",
        'CURL_PROXY_PORT' => 0,
        'REPORT_LEVENL' => 1,
    ),
    //微信公众号
    'Wechat' => array(
        'name' => '方正大米',
        'num' => '',
        'welcome' => '欢迎我们',       //微信关注提示语
    ),


    //电话
    'TelNum' => '010-6668888',
    //公司名字
    'IncName' => '',

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