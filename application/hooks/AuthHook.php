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

            'demo/sheet/index',

            'account/login/login',
            //'store/store/liststore',
            //'store/store/addstoredot',
            //'store/store/addstoreunion',
            //'store/store/deletestore',
            //'store/store/destroystore',
            //'store/store/showstore',
            //'store/store/showcity',
            //'store/store/getstore',
            //'store/store/updatestore',
            //'store/store/searchstore',
            'store/store/seachbymulti',
            'store/roomtype/listroomtype',
            'store/roomtype/addroomtype',
            'store/roomtype/deleteroomtype',
            'store/roomtype/searchroomtype',
            'store/roomtype/getroomtype',
            'store/roomtype/updateroomtype',
            'store/roomtype/destroyroomtype',
            'store/template/listtemplate',
            'store/template/deletetemplate',
            'store/template/addtemplate',
            'store/template/searchtemplate',
            'store/template/destroytemplate',
            'store/template/gettemplate',
            'store/template/updatetemplate',
            'store/template/showtemplate',

            'store/roomdot/adddot',
            'store/roomunion/addunion',
            'store/roomunion/listunion',
            'store/roomunion/showbuilding',
            'store/roomdot/listdot',
            'store/roomunion/getunion',
            'store/roomdot/getdot',
            'store/roomunion/batchupdateunion',
            'store/roomdot/batchupdatedot',

            'store/community/addcommunity',
            'store/community/listcommunity',
            'store/community/searchcommunity',
            'store/community/updatecommunity',
            'store/community/getcommunity',
            'store/community/deletecommunity',
            'store/community/destroycommunity',
            'store/community/showcommunity',

            'store/room/adddot',
            'store/room/addunion',

            'service/servicetype/listservicetype',
            'service/servicetype/addservicetype',
            'service/servicetype/imageupload',
            'service/servicetype/updateservicetype',

            'service/serviceorder/listserviceorder',
            'service/serviceorder/getservicetype',
            'service/serviceorder/getdetail',

            'service/reserveorder/listreserveorder',

            'shop/goodscategory/goodscategory',
            'shop/goodscategory/addcategory',
            'shop/goodscategory/updatecategory',
            'shop/goodscategory/deletecategory',

            'shop/goods/listgoods',
            'shop/goods/getcategory',
            'shop/goods/addgoods',
            'shop/goods/updategoods',
            'shop/goods/updateonsale',
            'shop/goods/deletegoods',

            'shop/goodsorder/listgoodsorder',
            'shop/goodsorder/detail',

            'employee/position/editposition',

            'smartdevice/smartdevice/listsmartdevice',
            'smartdevice/smartdevice/record',
            'smartdevice/yeeuulock/test',
            'common/city',
            'common/province',
            'common/district',

        );

        $directory  = $this->CI->router->fetch_directory();
        $class      = $this->CI->router->fetch_class();
        $method     = $this->CI->router->fetch_method();
        $full_path  = strtolower($directory.$class.'/'.$method);
        // var_dump( $full_path );
        if(!in_array($full_path,$authArr)) {
            try {
                $token   = $this->CI->input->get_request_header('token');
                $decoded = $this->CI->m_jwt->decodeJwtToken($token);
                $d_bxid   = $decoded->bxid;
                $d_company_id   = $decoded->company_id;
                define('CURRENT_ID',$d_bxid);
                define('COMPANY_ID',$d_company_id);
            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }
}