<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/4/8
 * Time:        09:11
 * Describe:    授权登录token验证Hook
 */

class AuthHook {
    private $CI;


	public function __construct()
  	{
        $this->CI = &get_instance();   //获取CI对象

        header("Access-Control-Allow-Origin: * ");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Token");
		header('Access-Control-Allow-Methods: GET, POST');
    }

    public function isAuth()
    {
        //免登录白名单
        //格式1 目录/类/方法
        //格式2 类/方法
        //注意，所有url统一用小写，不要大写
        $authArr = array(
            'demo/base_demo/index',
            'demo/jwt_demo/login',
            'demo/upload/index',
            'demo/upload/do_upload',
            'test/doshow',
            'login/login',
            'login/wechatLogin',
            'login/phoneLogin',

            'funxadmin/addadmin',
            //'funxadmin/listadmin',
            'funxadmin/qrcodeaddadmin',
            'funxadmin/deleteadmin',
            'funxadmin/searchadmin',
            'funxadmin/updateadmin',

            //'company/listcompany',
            'company/addcompany',
            'company/updatecompany',
            'company/deletecompany',
            'company/qrcodeaddcompany',
            'company/querylicense',
            'company/licenseupload',

            'boss/servicetype/index',
            'boss/servicetype/imageupload',
            'boss/servicetype/addservicetype',
            'boss/servicetype/updateservicetype',

        );

        $directory  = $this->CI->router->fetch_directory();
        $class      = $this->CI->router->fetch_class();
        $method     = $this->CI->router->fetch_method();
        $full_path  = $directory.$class.'/'.$method;
        // var_dump( $full_path );
        if(!in_array($full_path,$authArr)) {

            try {
                //$token = $this->CI->input->server('HTTP_TOKEN');
                $token = $this->CI->input->get_request_header('token');
                // $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC90YXBpLmZ1bnhkYXRhLmNvbSIsImV4cCI6MTUyNDYyMzc0MiwibmJmIjoxMzU3MDAwMDAwLCJmeGlkIjoxMDAwMDF9.EVq_KdvkaVSSOYoza0I1Cu-b06i7lNc0YLCUvPGl444';
                $decoded = $this->CI->m_jwt->decodeJwtToken($token);
                $d_fxid   = $decoded->fxid;
                //定义的常量无法传递到控制器
                
                define('CURRENT_ID',$d_fxid);
                // $this->CI->current_id=$d_fxid;
               
            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }
}