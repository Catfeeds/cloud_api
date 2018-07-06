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
            'ping/index',
            'mini/login/gettoken',
            'mini/login/handleloginstatus',
            'account/login/login',
            'bill/bill/test',
            'bill/checkout/list',
            'bill/checkout/show',

//            'demo/copy/transferimages',
            'demo/copy/run',
//
//            'common/imageupload',
            'common/fileupload',
//
//            'bill/order/download',
//
            'demo/sheet/index',
//            'demo/sheet/output',
//
//            'bill/bill/generate',
//            //
//            'bill/bill/listbill',
//            'bill/bill/showbill',

//            'account/login/login',
//            'account/login/listmenu',
            'demo/test/test1',

            //'mini/resident/checkroomunion',
//            'mini/resident/checkin',
//            'mini/resident/destory',
            //'mini/resident/showqrcode',
//            'mini/resident/reservation',
//            'mini/resident/getresident',
//            'mini/resident/listresident',
//            'mini/resident/unconfirm',
//            'mini/resident/bookingtocheckin',
//            'mini/resident/renew',
//            'mini/checkout/listcheckout',
//            'mini/checkout/store',
//            'mini/checkout/submitforapproval',
//            'mini/checkout/approve',
//            'mini/checkout/show',
//            'mini/checkout/destroy',

//            'mini/activity/showactivity',
//

            //'home/home/home'
//            'bill/meter/confirm',
//            'bill/meter/import',
//            'bill/meter/outputtemplate',
//            'bill/bill/generate',
//            'bill/bill/listbill',
//            'bill/bill/testa',
            'demo/test/testa',
            'demo/test/getendtimerooms',
            'demo/test/getendtimeresidentorder',
//            'bill/order/listorder',
//            'bill/order/editorder',
//
//            'mini/order/getavailablecoupons',
//            'mini/order/listorder',
//            'mini/order/amountwithdiscount',
//            'bill/order/addorder',
//            'mini/order/showbyroom',
            //'mini/order/confirm',
            //'mini/order/pay',
            //'mini/owner/showdetail',

//            'mini/renew/getresidentbyroom',
//            'mini/renew/getrenewroomstatus',
//            'mini/renew/renew',
//            'mini/renew/listrenew',
//            'mini/renew/destroy',


            'mini/rerequire/getendtimerooms',

//            'store/store/liststore',
            //'store/store/addstoredot',
            //'store/store/addstoreunion',
            //'store/store/deletestore',
            //'store/store/destroystore',
            //'store/store/showstore',
            //'store/store/showcity',
            //'store/store/getstore',
            //'store/store/updatestore',
            //'store/store/searchstore',
//            'store/store/seachbymulti',
//            'store/roomtype/listroomtype',
//            'store/roomtype/addroomtype',
//            'store/roomtype/deleteroomtype',
//            'store/roomtype/searchroomtype',
//            'store/roomtype/getroomtype',
//            'store/roomtype/updateroomtype',
//            'store/roomtype/destroyroomtype',
//            'store/template/listtemplate',
//            'store/template/deletetemplate',
//            'store/template/addtemplate',
//            'store/template/searchtemplate',
//            'store/template/destroytemplate',
//            'store/template/gettemplate',
//            'store/template/updatetemplate',
//            'store/template/showtemplate',
//
//            'store/roomdot/adddot',
//            'store/roomunion/addunion',
//            'store/roomunion/listunion',
//            'store/roomunion/showbuilding',
//            'store/roomunion/submitunion',
//            'store/roomunion/destory',
//            'store/roomdot/listdot',
//            'store/roomunion/getunion',
//            'store/roomdot/getdot',
//            'store/roomdot/destroy',
//            'store/roomunion/batchupdateunion',
//            'store/roomdot/batchupdatedot',
//
//            'store/community/addcommunity',
//            'store/community/listcommunity',
//            'store/community/searchcommunity',
//            'store/community/updatecommunity',
//            'store/community/getcommunity',
//            'store/community/deletecommunity',
//            'store/community/destroycommunity',
//            'store/community/showcommunity',
//
//            'store/room/adddot',
            'bill/order/push',
//            'store/room/addunion',
//
//            'service/servicetype/listservicetype',
//            'service/servicetype/addservicetype',
//            'service/servicetype/imageupload',
//            'service/servicetype/updateservicetype',
//
//            'service/serviceorder/listserviceorder',
//            'service/serviceorder/getservicetype',
//            'service/serviceorder/getdetail',
//
//            'service/reserveorder/listreserveorder',
//
//            'shop/goodscategory/goodscategory',
//            'shop/goodscategory/addcategory',
//            'shop/goodscategory/updatecategory',
//            'shop/goodscategory/deletecategory',
//
//            'shop/goods/listgoods',
//            'shop/goods/getcategory',
//            'shop/goods/addgoods',
//            'shop/goods/updategoods',
//            'shop/goods/updateonsale',
//            'shop/goods/deletegoods',
//
//            'shop/goodsorder/listgoodsorder',
//            'shop/goodsorder/detail',
//
//            'smartdevice/smartdevice/listsmartdevice',
            'smartdevice/smartdevice/record',
//            'smartdevice/smartdevice/test',
//            'smartdevice/smartdevice/getallrecord',
//            'smartdevice/readmeter/handle',
//            'smartdevice/crondreadmeter/readmeter',
//
//            'common/city',
//            'common/province',
//            'common/district',
//
//            'sellcontrol/sellcontrol/details',

//            'resident/resident/getresidentorder'
            //'mini/reserve/listreserve',
//            'mini/reserve/addreserve',
//            'mini/reserve/reserveinfo',
//            'mini/reserve/room_type',
//            'mini/reserve/reservestatus',

//            'mini/contract/listunsign',
//
//            'mini/visitrecord/visit',
//            'mini/login/gettoken',
//            'mini/login/handleloginstatus',
//            'mini/personalcenter/center',

//            'mini/home/lists',

            //'mini/server/show',
            //'mini/server/comfirmorder',
//            'mini/server/create',
            //'mini/server/listserver',
            //'mini/server/limitcontract',
//            'mini/server/serverstatus',
//            'mini/room/listroom',
//            'mini/room/detailsroom',
//            'mini/room/building',
//            'mini/room/countroom',
//
//            'contract/operation/operatlist',
            //'contract/operation/operationfind',
//            'contract/operation/booking',
//            'contract/operation/book',
           //'contract/resident/resident',
//            'contract/contract/showcontract',
//
//
//            'activity/activity/listactivity',
//
//            'coupon/coupon/listcoupon',
//            'coupon/coupon/addcoupon',
//            'coupon/coupon/updatecoupon',
//            'coupon/coupon/sendcoupon',
//            'coupon/coupon/sendcoupon',
            //'coupon/coupon/resident',
//            'coupon/coupon/assigncoupon',
//
//            'contract/operation/pdflook',
//            'contract/operation/loadcontract',
//
//            'pricecontrol/pricecontrol/pricecontrol',
//            'pricecontrol/pricecontrol/rentprice',
//            'pricecontrol/pricecontrol/utilities',
//            'pricecontrol/pricecontrol/changeutility',
//
//            'resident/resident/showresident',
//            'resident/resident/residentinfo',
//            'resident/resident/updateresident',
//            'resident/resident/contract',
//            'resident/resident/bill',
//            'sellcontrol/sellcontrol/details',
//            'utility/utility/listutility',
            'utility/utility/listutility1',
//            'utility/utility/updatenumber',

        );
//        if(ENVIRONMENT !=='production')
//        {
//
//        }

        $directory = $this->CI->router->fetch_directory();
        $class = $this->CI->router->fetch_class();
        $method = $this->CI->router->fetch_method();
        $full_path = strtolower($directory . $class . '/' . $method);
        // var_dump( $full_path );
        if (!in_array($full_path, $authArr)) {
            try {
                $token = $this->CI->input->get_request_header('token');
                $decoded = $this->CI->m_jwt->decodeJwtToken($token);
                $d_bxid = $decoded->bxid;
                $d_company_id = $decoded->company_id;
                define('CURRENT_ID', $d_bxid);
                define('COMPANY_ID', $d_company_id);

                $pre = substr(CURRENT_ID, 0, 2);
                if ($pre == SUPERPRE) {
                    //super 拥有所有的权限
                    $this->CI->position = 'SUPER';
                } else {
                    $this->CI->position = 'EMPLOYEE';
                    $this->CI->load->model('employeemodel');
                    $this->CI->employee = Employeemodel::where('bxid', CURRENT_ID)->first();
                }

                //操作记录测试
                if (!$this->operationRecord($full_path)) {
                    header("Content-Type:application/json;charset=UTF-8");
                    echo json_encode(array('rescode' => 1012, 'resmsg' => '操作log出错', 'data' => []));
                    exit;
                }
                /*//权限匹配
                if (!$this->privilegeMatch($directory, $class, $full_path)) {
                    header("Content-Type:application/json;charset=UTF-8");
                    echo json_encode(array('rescode' => 1011, 'resmsg' => '没有访问权限', 'data' => []));
                    exit;
                }*/
            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }

    public function privilegeMatch($full_path)
    {
        $this->CI->load->model('employeemodel');
        $this->CI->load->model('positionmodel');
        $employee = Employeemodel::with('position')->where('bxid', CURRENT_ID)->first(['id', 'position_id']);
        if (!$employee || !$employee->position) {
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode(array('rescode' => 1009, 'resmsg' => '操作数据库出错', 'data' => []));
            exit;
        }
        $this->CI->load->model('privilegemodel');
        $pc_privilege_ids = $employee->position->pc_privilege_ids;
        $ids_three = explode(',', $pc_privilege_ids);
        $ids_two = Privilegemodel::whereIn('id', $ids_three)->where('url',$full_path)->get();
        if(empty($ids_two)){
            return false;
        }
    }

    public function operationRecord($full_path)
    {
        $this->CI->load->model('employeemodel');
        $this->CI->load->model('operationrecordmodel');
        $operation = new Operationrecordmodel();
        $employee = Employeemodel::where('bxid', CURRENT_ID)->first();
        if (!$employee) return false;
        $operation->bxid = CURRENT_ID;
        $operation->company_id = COMPANY_ID;
        $operation->employee_id = $employee->id;
        $operation->name = $employee->name;
        $operation->url = $full_path;
        $operation->created_at = date('Y-m-d H:i:s', time());
        $operation->updated_at = date('Y-m-d H:i:s', time());
        if (!$operation->save()) {
            return false;
        }
        return true;
    }
}