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
		$post = $this->input->post(NULL, true);
		log_message('debug', json_encode($post));
		if ($post['code']) {
			$sessionKeyData = $this->app->sns->getSessionKey($post['code']);
			$token          = $this->handleLoginStatus($sessionKeyData);
			$this->api_res(0, ['token' => $token]);
		} else {
			log_message('error', 'mini-getToken-获取token失败');
			$this->api_res(10002);
			return;
		}
	}
	
	public function handleLoginStatus($sessionKeyData)
	{
		$this->load->library('M_jwt');
		$this->debug('员工微信信息-->', $sessionKeyData);
		if (!isset($sessionKeyData->unionid)) {
			$this->api_res(10002);
			return false;
		}
		$wechat = Employeemodel::where('unionid', $sessionKeyData->unionid)
			->where('status', 'ENABLE')
			->first();
		$this->debug('员工信息-->', $wechat);
		if (empty($wechat)) {
			$this->api_res(10002);
			return false;
		}
		log_message('debug', '获取员工信息' . json_encode($wechat->toArray()));
		$wechat->mini_openid = $sessionKeyData->openid;
		$wechat->session_key = $sessionKeyData->session_key;
		$wechat->save();
		return $this->m_jwt->generateJwtToken($wechat->bxid, $wechat->company_id);
	}
	
	public function authority()
	{
		$this->load->model('storemodel');
		//获取门店列表
		$store_ids = explode(',', $this->employee->store_ids);
		if (empty($store_ids) || !isset($store_ids)) {
			$this->api_res(1018);
			return;
		}
		$where = ['company_id' => $this->company_id];
		
		$data['store'] = Storemodel::whereIn('id', $store_ids)->get(['id', 'name', 'province', 'city', 'district']);
		
		$this->api_res(0, $data);
	}
}
