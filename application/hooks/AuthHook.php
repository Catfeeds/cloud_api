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
		header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

            'common/imageupload',
            'login/login/login',
            'store/store/liststore',
            'store/store/addstoredot',
            'store/store/addstoreunion',
            'store/store/deletestore',
            'store/store/showstore',
            'store/roomtype/listroomtype',
            'store/roomtype/addroomtype',
            'store/roomtype/deleteroomtype',
            'store/template/listtemplate',

            'service/servicetype/index',
            'service/servicetype/addservicetype',
            'service/servicetype/imageupload',
            'service/servicetype/updateservicetype',

            'service/serviceorder/index',
            'service/serviceorder/getcity',
            'service/serviceorder/getstore',
            'service/serviceorder/getservicetype',
            'service/serviceorder/getdetail',
            'service/serviceorder/test',

            'service/reserveorder/index',
            'service/reserveorder/getvisittype',

            'shop/goodscategory/goodscategory',
            'shop/goodscategory/addcategory',
            'shop/goodscategory/updatecategory',
            'shop/goodscategory/deletecategory',

            'shop/goods/index',
            'shop/goods/getcategory',
            'shop/goods/addgoods',
            'shop/goods/updateonsale',
            'shop/goods/deletegoods',
        );

        $directory  = $this->CI->router->fetch_directory();
        $class      = $this->CI->router->fetch_class();
        $method     = $this->CI->router->fetch_method();
        $full_path  = $directory.$class.'/'.$method;
        // var_dump( $full_path );
        if(!in_array($full_path,$authArr)) {

            try {

                $token = $this->CI->input->get_request_header('token');
                $decoded = $this->CI->m_jwt->decodeJwtToken($token);
                $d_bxid   = $decoded->bxid;
                define('CURRENT_ID',$d_bxid);

            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }
}