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

    public function __construct() {
        $this->CI = &get_instance(); //获取CI对象
    }

    /**
     * 生产环境的白名单
     * 白名单内的不需要验证token
     */
    private function productionAuth() {
        return [
            'mini/login/gettoken',
            'mini/login/handleloginstatus',
            'account/login/login',
            'bill/order/push',
            'bill/order/notify',

            'mini/contract/notify',
            'mini/contract/autosignnotify',
            'mini/contract/autosign',
            'mini/contract/archive',

            'ping/index',
        ];
    }

    /**
     * 测试环境白名单
     * 白名单内的不需要验证token
     */
    private function developmentAuth() {
        return array(
            'account/login/logintest',
            'ping/index',
            'company/company/test',
            'mini/login/gettoken',
            'mini/login/handleloginstatus',
            'account/login/login',
            'mini/contract/autosignnotify',
            'bill/bill/test',
            'demo/copy/run',
            'demo/sheet/index',
            'demo/test/test1',
            'demo/test/testa',
            'demo/test/getendtimerooms',
            'demo/test/getendtimeresidentorder',
            'mini/rerequire/getendtimerooms',
            'bill/order/push',
            'bill/order/notify',
            'mini/contract/notify',
            'mini/contract/autosign',
            'mini/contract/archive',
            'utility/utility/listutility1',

            'company/company/sendcode',
            'company/company/register',
            'company/company/boundwechat',
            
            'events/auth'
        );
    }

    /**
     * 是否验证token
     */
    public function isAuth() {
        
        $directory = $this->CI->router->fetch_directory();
        $class     = $this->CI->router->fetch_class();
        $method    = $this->CI->router->fetch_method();
        $full_path = strtolower($directory . $class . '/' . $method);
        try {
            if (ENVIRONMENT == 'production') {
                $authArr = $this->productionAuth();
            } else {
                $authArr = $this->developmentAuth();
            }
            
            
            if(strtolower($directory) == 'innserservice/'){
                //内部服务API认证
                $this->apiAuth();
            }else if (!in_array($full_path, $authArr)) {
                //web端jwt认证
                $this->auth($full_path);
            }
        }catch (Exception $e) {
            // var_dump($e);exit;
            header('HTTP/1.1 401 Forbidden'); 
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode(array('rescode' => 1001, 'resmsg' => 'token无效', 'data' => []));
            exit;
        }
    }


    public function apiAuth(){
        $xapitoken = $this->CI->input->get_request_header('x-api-token');
        log_message('debug','x-api-token'.$xapitoken);
        if (empty($xapitoken)) {
            throw new InvalidArgumentException('xapitoken may not be empty');
        }
        $tks = explode('.', $xapitoken);
        if (count($tks) != 3) {
            throw new UnexpectedValueException('xapitoken Wrong number of segments');
        }
        $this->CI->load->model('apimodel');
        $model = Apimodel::where('apikey',$tks[0])->first();
        $apihash = hash('sha256',"$tks[0].$tks[1].$model->apisecret");
        // var_dump($apihash);exit;
        if($tks[2] == $apihash){
            define('X_API_TOKEN' , $xapitoken);
        }else{
            throw new Exception('x-api-toekn 认证失败');
        }
    }

    /**
     * @param $full_path
     * 验证token的方法
     */
    public function auth($full_path) {
        $token        = $this->CI->input->get_request_header('token');
        log_message('debug','TOKEN-'.$token);
        $decoded      = $this->CI->m_jwt->decodeJwtToken($token);
        $d_bxid       = $decoded->bxid;
        $d_company_id = $decoded->company_id;
        define('CURRENT_ID', $d_bxid);
        define('COMPANY_ID', $d_company_id);
        //SaaS权限验证
        $this->saas();

        log_message('debug','C_ID'.COMPANY_ID);
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
            header('HTTP/1.1 500 Forbidden'); 
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode(array('rescode' => 1012, 'resmsg' => '操作log出错', 'data' => []));
            exit;
        }
    }

     //SaaS权限验证
    private function saas(){
       
        $company_id = COMPANY_ID;

        if(!empty($company_id)){
            // if(!$this->CI->load->is_loaded('companymodel')){
                $this->CI->load->model('companymodel');
            // }
            $model = Companymodel::where('id',$company_id)->first();

            if(empty($model)){
                throw new Exception('该账号不存在');
            }

            //判断有效期
            if(strtotime($model->expiretime)<time()){
                throw new Exception('该账号已经过期失效，请续费');
            }

            //判断模块权限


            //判断状态
            if('CLOSE' === $model->status){
                throw new Exception('该账号已经注销，请联系管理员');
            }
        }
    }

    public function privilegeMatch($full_path) {
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
        $ids_three        = explode(',', $pc_privilege_ids);
        $ids_two          = Privilegemodel::whereIn('id', $ids_three)->where('url', $full_path)->get();
        if (empty($ids_two)) {
            return false;
        }
    }

    public function operationRecord($full_path) {
        $this->CI->load->model('employeemodel');
        $this->CI->load->model('operationrecordmodel');
        $operation = new Operationrecordmodel();
        $employee  = Employeemodel::where('bxid', CURRENT_ID)->first();
        if (!$employee) {
            return false;
        }

        $operation->bxid        = CURRENT_ID;
        $operation->company_id  = COMPANY_ID;
        $operation->employee_id = $employee->id;
        $operation->name        = $employee->name;
        $operation->url         = $full_path;
        $operation->created_at  = date('Y-m-d H:i:s', time());
        $operation->updated_at  = date('Y-m-d H:i:s', time());
        if (!$operation->save()) {
            return false;
        }
        return true;
    }
}
