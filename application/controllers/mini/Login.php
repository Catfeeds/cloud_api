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
        $this->app = (new Application($this->getWechatConfig()))->mini_program;
    }

    public function getToken()
    {

        $post = $this->input->post(null,true);
        if($post['code']){
            $code           = trim($post['code']);
            $sessionKeyData = $this->app->sns->getSessionKey($code);
            $token          = $this->handleLoginStatus($sessionKeyData);
            $this->api_res(0,$token);
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
        $wechat->unionid        = isset($sessionKeyData->unionid) ? $sessionKeyData->unionid : '';
        $wechat->session_key    = $sessionKeyData->session_key;
        $wechat->save();
        return $this->m_jwt->generateJwtToken($wechat['bxid'],$wechat['$company_id']);
    }

    /**
     * 小程序配置信息
     */
    public function getWechatConfig()
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
}
