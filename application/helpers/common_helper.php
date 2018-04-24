<?php

/**
 * 获取后台管理的url
 */
function admin_url($value = '')
{
    return rtrim(ADMIN_URL, '/').'/'.trim($value, '/');
}

/**
 * 获取用户微信端的url
 */
function wechat_url($value = '')
{
    return rtrim(WECHAT_URL, '/').'/'.trim($value, '/');
}

/**
 * 获取员工微信端的url
 */
function employee_url($value = '')
{
    return rtrim(EMPLOYEE_URL, '/').'/'.trim($value, '/');
}

function upyun_url($value = '')
{
    return rtrim(UPYUN_URL, '/').'/'.trim($value, '/');
}

/**
 * 获取员工端微信的配置
 */
function getEmployeeWechatConfig($debug = true)
{
    return array(
        'debug'     => $debug,
        'app_id'    => EMPLOYEE_WECHAT_APPID,
        'secret'    => EMPLOYEE_WECHAT_SECRET,
        'token'     => EMPLOYEE_WECHAT_TOKEN,
        'aes_key'   => EMPLOYEE_WECHAT_AES_KEY,
        'log' => [
            'level' => 'debug',
            'file'  => APPPATH.'cache/wechat.log',
        ],
        'oauth' => [
            'scopes'   => [EMPLOYEE_WECHAT_OAUTH_SCOPES],
            'callback' => site_url('callback'),
        ],
        'payment' => [
            'merchant_id'        => '',
            'key'                => '',
            'cert_path'          => '',
            'key_path'           => '',
        ],
        'guzzle' => [
            'timeout' => 3.0,
        ]
    );
}

/**
 * 获取客户端微信配置
 */
function getCustomerWechatConfig($debug = true)
{
    return array(
        'debug'     => $debug,
        'app_id'    => CUSTOMER_WECHAT_APPID,
        'secret'    => CUSTOMER_WECHAT_SECRET,
        'token'     => CUSTOMER_WECHAT_TOKEN,
        'aes_key'   => CUSTOMER_WECHAT_AES_KEY,
        'log' => [
            'level' => 'debug',
            'file'  => APPPATH.'cache/wechat.log',
        ],
        'oauth' => [
            'scopes'   => [CUSTOMER_WECHAT_OAUTH_SCOPES],
            'callback' => site_url('callback'),
        ],
        'payment' => [
            'merchant_id'   => CUSTOMER_WECHAT_PAYMENT_MERCHANT_ID,
            'key'           => CUSTOMER_WECHAT_PAYMENT_KEY,
            'cert_path'     => CUSTOMER_WECHAT_PAYMENT_CERT_PATH,
            'key_path'      => CUSTOMER_WECHAT_PAYMENT_KEY_PATH,
        ],
        'guzzle' => [
            'timeout' => 3.0,
        ]
    );
}