<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/18 0018
 * Time:        14:06
 * Describe:
 */
class Login extends MY_Controller
{
    protected $app;
    protected $oauth;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
        $this->load->helper('common');
        $this->app = (new Application(getMiniWechatConfig()))->mini_program;

    }

    public function getToken()
    {
        $post = $this->input->post(NULL,true);
        if($post['code']){
            $sessionKeyData = $this->app->sns->getSessionKey($post['code']);
            $token          = $this->handleLoginStatus($sessionKeyData);
            $this->api_res(0,['token'=>$token,'$sessionKeyData'=>$sessionKeyData]);
        }else{
            $this->api_res(10002);
            return;
        }
    }

    public function handleLoginStatus($sessionKeyData)
    {
        $this->load->library('M_jwt');
        if (!isset($sessionKeyData->unionid)) {
            $this->api_res(10002);
            return;
        }
        $wechat = Employeemodel::where('unionid', $sessionKeyData->unionid)->first();

        if (empty($wechat) OR $wechat->status == 'DISABLE') {
            $this->api_res(10002);
            return;
        }
        $wechat->mini_openid    = $sessionKeyData->openid;
        $wechat->session_key    = $sessionKeyData->session_key;
        $wechat->save();
        return $this->m_jwt->generateJwtToken($wechat['bxid'],$wechat['$company_id']);
    }

    public function authority(){
        $this->load->model('storemodel');
        //获取门店列表
        $store_ids['id']= explode(',',$this->employee->store_ids);
        if (empty($store_ids)||!isset($store_ids)){
            $this->api_res(1018);
            return;
        }
        $where  = ['company_id'=>COMPANY_ID];

        $data['store'] = Storemodel::where($where)->whereIn($store_ids)->get(['id','name','province','city','district']);

        $this->api_res(0,$data);



    }


}
