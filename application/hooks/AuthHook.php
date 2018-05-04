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
    }

    public function isAuth()
    {
        //免登录白名单
        //格式1 目录/类/方法
        //格式2 类/方法
        //注意，所有url统一用小写，不要大写
        $authArr = array(

            'common/imageupload',
            'common/fileupload',

            'login/login/login',
            'store/store/liststore',
            'store/store/addstoredot',
            'store/store/addstoreunion',
            'store/store/deletestore',
            'store/store/showstore',
            'store/store/showcity',
            'store/store/getstore',
            'store/store/updatestore',
            'store/store/searchstore',
            'store/store/seachbymulti',
            'store/roomtype/listroomtype',
            'store/roomtype/addroomtype',
            'store/roomtype/deleteroomtype',
            'store/roomtype/searchroomtype',
            'store/roomtype/getroomtype',
            'store/roomtype/updateroomtype',
            'store/template/listtemplate',
            'store/template/deletetemplate',
            'store/template/addtemplate',
            'store/template/searchtemplate',

            'store/community/addcommunity',
            'store/community/listcommunity',
            'store/community/searchcommunity',
            'store/community/updatecommunity',
            'store/community/getcommunity',
            'store/community/deletecommunity',

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
            'shop/goods/updategoods',
            'shop/goods/updateonsale',
            'shop/goods/deletegoods',

            'shop/goodsorder/index',
            'shop/goodsorder/detail',

            'smartdevice/smartdevice/index',
            'smartdevice/smartdevice/record',
        );

        $directory  = $this->CI->router->fetch_directory();
        $class      = $this->CI->router->fetch_class();
        $method     = $this->CI->router->fetch_method();
        $full_path  = strtolower($directory.$class.'/'.$method);
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