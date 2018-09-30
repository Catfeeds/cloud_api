<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once(APPPATH . '/libraries/wxBizMsgCrypt.php');
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
		$this->load->library('M_jwt');
		$post = $this->input->post(NULL, true);
		log_message('debug', json_encode($post));
		if ($post['code']) {
			$sessionKeyData = $this->app->sns->getSessionKey($post['code']);
			$this->debug('debug','sessionKeyData为-->'.$sessionKeyData);
			if (!isset($sessionKeyData->unionid)) {
				$this->debug('debug','===未授权====');
				$encryptedData = $post['encryptedData'];
				$iv            = $post['iv'];
				$pc            = new WXBizDataCrypt(config_item('miniAppid'), $sessionKeyData->sessionKey);
				$data          = '';
				$errCode       = $pc->decryptData($encryptedData, $iv, $data);
				$data          = json_decode($data, true);
				$this->debug('消息解密-->',$data);
				if ($errCode == 0) {
					$unionid = $data['unionid'];
				} else {
					$this->api_res('10103', $errCode);
					return false;
				}
			}else{
				$unionid = $sessionKeyData->unionid;
			}
			$wechat = Employeemodel::where('unionid', $unionid)
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
			$token = $this->m_jwt->generateJwtToken($wechat->bxid, $wechat->company_id);
			$this->api_res(0, ['token' => $token]);
		} else {
			log_message('error', 'mini-getToken-获取token失败');
			$this->api_res(10101);
		}
	}
	
	public function authority()
	{
		$this->load->model('storemodel');
		//获取门店列表
		$store_ids = $this->employee_store->store_ids;
		if (empty($store_ids) || !isset($store_ids)) {
			$this->api_res(1018);
			return;
		}
		$where         = ['company_id' => $this->company_id];
		$data['store'] = Storemodel::whereIn('id', $store_ids)->get(['id', 'name', 'province', 'city', 'district']);
		$this->api_res(0, $data);
	}
}
