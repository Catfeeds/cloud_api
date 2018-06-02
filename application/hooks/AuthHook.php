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

            'bill/bill/generate',

            'account/login/login',

            'mini/resident/checkroomunion',
            'mini/resident/checkin',
            'mini/resident/destory',
            'mini/resident/showqrcode',
            'mini/resident/reservation',
            'mini/resident/getresident',
            'mini/resident/listresident',
            'mini/resident/bookingtocheckin',
            'mini/resident/renew',
            'mini/checkout/listcheckout',
            'mini/checkout/store',

            'mini/activity/showactivity',

            'mini/order/listorder',
            'mini/order/confirm',
            'mini/order/pay',
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
            'store/roomunion/submitunion',
            'store/roomunion/destory',
            'store/roomdot/listdot',
            'store/roomunion/getunion',
            'store/roomdot/getdot',
            'store/roomdot/destroy',
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

            'smartdevice/smartdevice/listsmartdevice',
            'smartdevice/smartdevice/record',
            'smartdevice/smartdevice/test',
            'smartdevice/yeeuulock/test',
            'smartdevice/danbaylock/test',
            'smartdevice/cjoyelectric/test',

            'common/city',
            'common/province',
            'common/district',

            'sellcontrol/sellcontrol/details',

            'mini/reserve/listreserve',
            'mini/reserve/addreserve',
            'mini/reserve/reserveinfo',
            'mini/reserve/room_type',
            'mini/reserve/reservestatus',

            'mini/visitrecord/visit',
            'mini/login/gettoken',
            'mini/login/handleloginstatus',
            'mini/personalcenter/center',
            'mini/room/listroom',
            'mini/room/detailsroom',

            'contract/operation/operatlist',
            'contract/operation/operationfind',
            'contract/operation/booking',
            'contract/operation/book',
            'contract/resident/resident',


            'activity/activity/listactivity',

            'coupon/coupon/listcoupon',
            'coupon/coupon/addcoupon',
            'coupon/coupon/updatecoupon',
            'coupon/coupon/sendcoupon',

            'contract/operation/pdflook',

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
                /*
                //操作记录测试
                $this->operationRecord($full_path);*/
                /*
                //权限测试
                define('CURRENT_ID',1);
                $positions = $this->getCurrentPrivilege($directory);
                if (!$this->privilegeMatch($class, $positions)) {
                    header("Content-Type:application/json;charset=UTF-8");
                    echo json_encode(array('rescode' => 1001, 'resmsg' => '您没有访问权限', 'data' => []));
                    exit;
                }*/
            } catch (Exception $e) {
                header("Content-Type:application/json;charset=UTF-8");
                echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
                exit;
            }
        }
    }

    public function getCurrentPrivilege($directory)
    {
        $this->CI->load->model('employeemodel');
        $this->CI->load->model('positionmodel');
        $employee = Employeemodel::with('position')->where('bxid', CURRENT_ID)->first();
        $pc_privilege = $employee->position->pc_privilege;
        $mini_privilege = $employee->position->mini_privilege;
        return (substr($directory, 0, 4) == 'mini') ? $mini_privilege : $pc_privilege;
    }

    public function privilegeMatch($class, $positions)
    {
        $positionmap = [
            '员工管理' => 'employee',
            '职位管理' => 'position'
        ];
        $positions = explode(',', $positions);
        foreach($positions as $position) {
            $value = array_get($positionmap, $position);
            $controllers[] = $value;
        }
        $controller_url = strtolower($class);
        return in_array($controller_url, $controllers);
    }

    public function operationRecord($full_path)
    {
        $this->CI->load->model('employeemodel');
        $this->CI->load->model('operationrecordmodel');
        $operation = new Operationrecordmodel();
        $employee = Employeemodel::where('bxid', CURRENT_ID)->first();
        if (!$employee) {
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode(array('rescode' => 1001, 'resmsg' => '', 'data' => []));
            exit;
        }
        $operation->bxid = CURRENT_ID;
        $operation->company_id = COMPANY_ID;
        $operation->employee_id = $employee->id;
        $operation->name = $employee->name;
        $operation->url = $full_path;
        $operation->created_at = date('Y-m-d H:i:s', time());
        $operation->updated_at = date('Y-m-d H:i:s', time());
        if (!$operation->save()) {
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode(array('rescode' => 1001, 'resmsg' => '访问数据库出错', 'data' => []));
            exit;
        }
    }
}