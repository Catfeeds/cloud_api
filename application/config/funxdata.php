<?php

/**
 * funxdata的一些配置文件放在这，此文件添加到 .gitignore
 * 后期需要隐藏，不上传到版本库
 */


/*
|--------------------------------------------------------------------------
| 设置时区
|--------------------------------------------------------------------------
*/
date_default_timezone_set('Asia/ShangHai');
$config['time_zone']    = date_default_timezone_get();

/*
//梵响数据系统相关文件的整理配置
*/

//微信网页授权的信息
$config['wx_web_appid']     = 'wxc6f533f3cde3e647';
$config['wx_web_secret']    = '11c0ca3432e9d0d96f1dd94a9120d20b';

//云片信息
//https://sms.yunpian.com/v1/sms/send.json
$config['yunpian_api_url']  = 'https://sms.yunpian.com/v2/sms/single_send.json';
$config['yunpian_api_key']  = 'a91819aaea5b684dfb571442c279a9a3';

//jwt相关
$config['jwt_key'] = 'jfo2jf02jfoijf02klbm9&@Fklwfwef';
$config['jwt_alg'] = 'HS256';
$config['jwt_iss'] = 'http://example.org';
$config['jwt_exp'] = (time()+7200); //过期时间
//$config['jwt_nbf'] = 1357000000;


//上传附件的cdn地址
$config['cdn_path'] = 'http://tfunx.oss-cn-shenzhen.aliyuncs.com';
