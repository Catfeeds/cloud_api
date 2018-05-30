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

//微信开放平台网页授权的信息
$config['wx_web_appid']     = 'wx75fd74e2316b2355';
$config['wx_web_secret']    = '70fa3a7fe658be97552788fc764f5434';

//微信用户端公众号的信息
$config['wx_map_appid']     = 'wxd8da84ed2a26aa06';
$config['wx_map_secret']    = '00e6fd3ce1151e3d2bd0e01c98c925d3';
$config['wx_map_token']     = 'aJ1B3XhY7qRvTG3DrbxNhCLo90kpsds4';
$config['wx_map_aes_key']   = 'IwTUFptFaJ1B3XhY7qRvTG3DrbxNhCLo90kpsqP0cNL';



//云片信息
//https://sms.yunpian.com/v1/sms/send.json
$config['yunpian_api_url']  = 'http://sms.yunpian.com/v2/sms/single_send.json';
$config['yunpian_api_key']  = 'a91819aaea5b684dfb571442c279a9a3';

//jwt相关
$config['jwt_key'] = 'jfo2jf02jfoijf02klbm9&@Fklwfwefboss';
$config['jwt_alg'] = 'HS256';
$config['jwt_iss'] = 'http://funxdata.com';
$config['jwt_exp'] = (time()+7200); //过期时间
//$config['jwt_nbf'] = 1357000000;


//上传附件的cdn地址
$config['cdn_path'] = 'http://tfunx.oss-cn-shenzhen.aliyuncs.com';

//智能设备相关配置信息
$config['yeeuuapiBaseUrl']  = 'https://api.yeeuu.com/v1/locks';
$config['yeeuualmsUrl']     = 'https://alms.yeeuu.com/apartments/synchronize_apartments';

$config['joyMeterClientId'] = 'joy000001';
$config['joyMeterApiUrl']   = 'http://122.225.71.66:8787/amr/joy/';
$config['joyPublicKeyPath'] = 'private/keys/rsa_public_key.pem';
$config['joyPrivateKeyPath']= 'private/keys/rsa_private_key.pem';
$config['joyLockPartnerId'] = '59cf50b69e23627437000028';
$config['joyLockSecret']    = 'fahwkc5M';

$config['danbayUserName']   = 'jindihuohua';
$config['danbayPassword']   = 'a123456';

//法大大电子合同接口
$config['fadada_api_app_secret'] ='PMKQo0b3RCb911OaqmsGAFnw';
$config['fadada_api_app_id'] ='400388';
$config['fadada_customer_sign_key_word'] ='RESIDENT_SIGNATURE';
$config['fadada_api_base_url'] ='https://testapi.fadada.com:8443/api/';

$config['syncPerson_auto.api']  ='syncPerson_auto.api';
$config['contractFiling.api'] ='contractFiling.api';
$config['uploadtemplate.api'] ='uploadtemplate.api';
$config['generate_contract.api'] ='generate_contract.api';
$config['extsign.api'] ='extsign.api';
$config['extsign_auto.api'] ='extsign_auto.api';


//员工端相关配置信息
$config['miniAppid']        = 'wx5721f56e75cc901e';
$config['miniSecret']       = 'c2681fbb7a0cb09b3817bc7706a57163';
$config['miniToken']        = '';
$config['miniAes_key']      = '';

