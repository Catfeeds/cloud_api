<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/22 0022
 * Time:        17:26
 * Describe:
 */
function getWechatCustomerConfig(){
    $debug  = (ENVIRONMENT!=='development'?false:true);
    return [
        'debug'     => $debug,
        'app_id'    => config_item('wx_map_appid'),
        'secret'    => config_item('wx_map_secret'),
        'token'     => config_item('wx_map_token'),
        'aes_key'   => config_item('wx_map_aes_key'),
        'log' => [
            'level' => 'debug',
            'file'  => APPPATH.'cache/wechat.log',
            ]
    ];
}

/**
 * 小程序配置信息
 */
function getMiniWechatConfig()
{
    return[
        'mini_program'  =>  [
            'app_id'        => config_item('miniAppid'),
            'secret'        => config_item('miniSecret'),
            'token'         => config_item('miniToken'),
            'aes_key'       => config_item('miniAes_key'),
        ],
    ];
}
