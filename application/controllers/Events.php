<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once(APPPATH . '/libraries/WXBizMsgCrypt.php');

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/8/29
 * Time:        19:12
 * Describe:    微信事件
 */
class Events extends MY_Controller
{
	protected $appid;
	protected $secret;
	protected $token;
	protected $aesKey;
	protected $ticket;
	protected $re_url;
	protected $re_auth_url;
	
	public function __construct()
	{
		parent::__construct();
		$this->load->library('m_redis');
		$this->appid       = config_item('wx_web_appid');
		$this->secret      = config_item('wx_web_secret');
		$this->token       = config_item('wx_web_token');
		$this->aesKey      = config_item('wx_web_key');
		$this->re_url      = config_item('wx_info_url');//消息时间接收回调地址
		$this->re_auth_url = config_item('wx_auth_url');//授权回调地址
		
	}
	
	/*********************************************************
	 *** 接入第三方平台概述                                   ***
	 *** 1.第三方获取预授权码                                 ***
	 *** 2.用户方进入第三方提供的授权二维码页面，扫码授权给第三方   ***
	 *** 3.回调获取用户授权码及授权码的过期时间                  ***
	 *** 4.利用授权码调用用户公众号API                         ***
	 *********************************************************/
	
	/**
	 * 功能：重定向到用户扫码授权页面
	 * 参数：
	 *      component_appid :   第三方平台方appid
	 *      pre_auth_code :     预授权码
	 *      redirect_uri :      回调URI
	 */
	public function authorization()
	{
		if ($this->m_redis->getPreAuthCode()) {
			$pre_auth_code = $this->m_redis->getPreAuthCode();
		} else {
			$pre_auth_code = $this->getPreAuthCode();
		}
		$url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component\_appid=$this->appid&pre\_auth\_code=$pre_auth_code&redirect\_uri=$this->re_url";
		header();
	}
	
	/**
	 * 功能：获取预授权码pre_auth_code
	 * 请求参数：
	 *      component_access_token： 第三方access_token(get方式)
	 *      component_appid：        第三方平台方appid(post方式)
	 * 返回值：
	 *      pre_auth_code：            预授权码
	 *      expires_in：                有效期，为10分钟
	 */
	private function getPreAuthCode()
	{
		if ($this->m_redis->getAccessToken()) {
			$access_token = $this->m_redis->getAccessToken();
		} else {
			$access_token = $this->getAccessToken();
		}
		$url  = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=' . "$access_token";
		$data = ['component_appid' => $this->appid];
		$res  = $this->httpCurl($url, 'post', 'json', $data);
		if (array_key_exists('errcode', $res)) {
			log_message('error', '获取预授权码失败--> ' . $res['errmsg']);
			return false;
		} else {
			log_message('debug', '获取预授权码成功--> ' . $res['pre_auth_code']);
			$this->m_redis->savePreAuthCode($res['pre_auth_code']);
			return $res['pre_auth_code'];
		}
	}
	
	/**
	 * 功能：获取第三方平台component_access_token
	 *    component_access_token：   令牌有效期2小时，且从微信获取令牌有次数限制,自行保存(一小时50分左右)再刷新。
	 * 请求体：
	 *      component_appid            第三方平台appid
	 *      component_appsecret        第三方平台appsecret
	 *      component_verify_ticket    微信后台推送的ticket，此ticket会定时推送。
	 * 请求方式：
	 *      post
	 * 返回值：
	 *      component_access_token    第三方平台access_token
	 *      expires_in                有效期7200s
	 */
	private function getAccessToken()
	{
		if ($this->m_redis->getTicket()) {
			$this->ticket = $this->m_redis->getTicket();
		} else {
			log_message('debug', '--ticket获取失败--');
			return false;
		}
		$url  = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
		$data = [
			"component_appid"         => $this->appid,
			"component_appsecret"     => $this->secret,
			"component_verify_ticket" => $this->ticket,
		];
		$res  = $this->httpCurl($url, 'post', 'json', $data);
		if (array_key_exists('errcode', $res)) {
			log_message('error', '获取AccessToken失败-> ' . $res['errmsg']);
			return false;
		} else {
			//存储access_token
			log_message('debug', 'component_access_token为-->' . $res['component_access_token']);
			$this->m_redis->saveAccessToken($res['component_access_token']);
			return $res['component_access_token'];
		}
	}
	
	/**
	 * 功能：获取微信的推送信息
	 * 概述：每十分钟将收到微信以post方式推送的XML格式的加密信息。
	 *      推送信息包括APPID,CreateTime,InfoType(即 component_verify_ticket)以及ComponentVerifyTicket
	 */
	public function auth()
	{
		$input      = $this->input->get(null, true);     //url上携带参数
		$encryptMsg = $this->input->post(null, true);    //通过post方式推送的加密xml数据
		$this->debug('url上携带参数为', $input);
		$timestamp = empty($input['timestamp']) ? "" : trim($input['timestamp']);
		$nonce     = empty($input['nonce']) ? "" : trim($input['nonce']);
		$msg_sign  = empty($input['msg_signature']) ? "" : trim($input['msg_signature']);
		$pc        = new WXBizMsgCrypt($this->token, $this->aesKey, $this->appid);
		//开始消息解密，解密内容存入msg变量
		$msg     = '';
		$errCode = $pc->decryptMsg($msg_sign, $timestamp, $nonce, $encryptMsg, $msg);
		if ($errCode == 0) {
			// 从解密内容中获取ComponentVerifyTicket
			log_message('debug', '-----开始解码-----');
			$xml = new DOMDocument();
			$xml->loadXML($msg);
			$array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
			//解密得到的ticket
			$this->ticket = $array_e->item(0)->nodeValue;
			log_message('debug', '解密得到的ticket为-->' . $this->ticket);
			$this->m_redis->saveAccessToken($this->ticket);
		} else {
			log_message('error', '解密失败-->' . $errCode);
		}
	}
}