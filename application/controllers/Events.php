<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use GuzzleHttp\Client;
include_once(APPPATH . '/libraries/wxBizMsgCrypt.php');
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
		$this->appid       = config_item('wx_cloud_appid');
		$this->secret      = config_item('wx_cloud_secret');
		$this->token       = config_item('wx_cloud_token');
		$this->aesKey      = config_item('wx_cloud_key');//消息加解密Key
		$this->re_url      = config_item('wx_info_url');//消息事件接收回调地址
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
	public function authLocation()
	{
		if ($this->m_redis->getPreAuthCode()) {
			$pre_auth_code = $this->m_redis->getPreAuthCode();
		} else {
			$pre_auth_code = $this->getPreAuthCode();
		}
		$url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component\_appid=$this->appid&pre\_auth\_code=$pre_auth_code&redirect\_uri=$this->re_auth_url";
		header("Location: " . $url, TRUE, 301);
	}
	
	/**
	 * 功能:获取授权码
	 *      用户授权之后的回调，微信平台将返回用户的授权码auth_code和过期时间expires_in=600
	 */
	public function authCallBack()
	{
		$input = $this->input->get(null, true);     //url上携带参数
		if (empty($input['auth_code']) || $input['expires_in']) {
			log_message('error', '授权回调参数有误');
		}
		$this->debug('授权回调袖带参数为-->', $input);
		$auth_code  = empty($input['auth_code']) ? "" : trim($input['auth_code']);
		$expires_in = empty($input['expires_in']) ? "" : trim($input['expires_in']);
		$this->m_redis->saveAuthCode($auth_code, $expires_in);
		return $auth_code;
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
	public function getPreAuthCode()
	{
		if ($this->m_redis->getAccessToken()) {
			$access_token = $this->m_redis->getAccessToken();
		} else {
			$access_token = $this->getAccessToken();
		}
		$url  = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=' . "$access_token";
		$data = ['component_appid' => $this->appid];
		$this->debug('POST参数为-->',$data);
		$res  = $this->httpCurl($url, 'post', 'json', $data);
		$this->debug('获取预授权码-->',$res);
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
	public function getAccessToken()
	{
		if ($this->m_redis->getComponentVerifyTicket()) {
			$this->ticket = $this->m_redis->getComponentVerifyTicket();
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
		$this->debug('POST参数为-->',$data);
		$res  = $this->httpCurl($url, 'post', 'json', $data);
//		$res    = (new Client())->request('POST', $url, $data)->getBody()->getContents();
//		$res    = json_decode($res,true);
		$this->debug('获取AccessToken返回-->',$res);
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
		$encryptMsg = file_get_contents('php://input');//微信推送信息
		$this->debug('url上携带参数为', $input);
		$timestamp = empty($input['timestamp']) ? "" : trim($input['timestamp']);
		$nonce     = empty($input['nonce']) ? "" : trim($input['nonce']);
		$msg_sign  = empty($input['msg_signature']) ? "" : trim($input['msg_signature']);
		$pc        = new WXBizMsgCrypt($this->token, $this->aesKey, $this->appid);
		//接收XML数据
		$xml_tree = new DOMDocument();
		$xml_tree->loadXML($encryptMsg);
		$array_e  = $xml_tree->getElementsByTagName('Encrypt');
		$encrypt  = $array_e->item(0)->nodeValue;
		$format   = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
		$from_xml = sprintf($format, $encrypt);
		log_message('debug', $from_xml);
		//开始消息解密，解密内容存入msg变量
		$msg     = '';
		$errCode = $pc->decryptMsg($msg_sign, $timestamp, $nonce, $from_xml, $msg);
		if ($errCode == 0) {
			// 从解密内容中获取ComponentVerifyTicket
			log_message('debug', '开始解码-->' . $msg);
			$xml = new DOMDocument();
			$xml->loadXML($msg);
			$array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
			//解密得到的ticket
			$this->ticket = $array_e->item(0)->nodeValue;
			log_message('debug', '解密得到的ticket为-->' . $this->ticket);
			$this->m_redis->saveComponentVerifyTicket($this->ticket);
		} else {
			log_message('error', '解密失败-->' . $errCode);
		}
	}
	
	public function test()
	{
		echo 1;
	}
	
	/**
	 * 测试
	 */
	function getTicket()
	{
		$data = input('param.');
		trace($data, 'data');
		$msg_sign  = input('msg_signature');
		$timeStamp = input('timestamp');
		$nonce     = input('nonce');
		
		$encryptMsg = file_get_contents('php://input');
		trace($encryptMsg, 'getTicket');
		
		//因为数据格式，先加密再解密
		$pc = new WXBizMsgCrypt($this->token, $this->aesKey, $this->appid);
		
		$xml_tree = new \DOMDocument();
		$xml_tree->loadXML($encryptMsg);
		$array_e = $xml_tree->getElementsByTagName('Encrypt');
		$encrypt = $array_e->item(0)->nodeValue;
		
		echo 'success';
		
		$format   = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
		$from_xml = sprintf($format, $encrypt);
		// 第三方收到公众号平台发送的消息
		$msg     = '';
		$errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
		trace($msg, "msg: ");
		if ($errCode == 0) {
			//print("解密后: " . $msg . "\n");
			$xml = new \DOMDocument();
			$xml->loadXML($msg);
			$array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
			//保存下来
			$this->component_verify_ticket = $array_e->item(0)->nodeValue;
			
			//获取保存accessToken
			$this->component_access_token = $this->component->getComponentToken($this->component_verify_ticket);
		} else {
			print($errCode . "\n");
		}
	}
	
	//全网发布响应
	function callback()
	{
		$appid     = input('appid');
		$msg_sign  = input('msg_signature');
		$timeStamp = input('timestamp');
		$nonce     = input('nonce');
		
		$encryptMsg = file_get_contents('php://input');
		
		trace($encryptMsg, 'php://inpu');
		
		//解密
		$pc      = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->appid);
		$msg     = '';
		$errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $encryptMsg, $msg);
		
		trace($msg, "3解密后: ");
		$response = json_decode(json_encode(simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		//生成返回公众号的消息
		$res_msg = $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
		
		//判断事件
		
		//2模拟粉丝发送文本消息给专用测试公众号
		
		if ($response['MsgType'] == "text") {
			$needle   = 'QUERY_AUTH_CODE:';
			$tmparray = explode($needle, $response['Content']);
			if (count($tmparray) > 1) {
				trace($response, "解密后: ");
				//3、模拟粉丝发送文本消息给专用测试公众号，第三方平台方需在5秒内返回空串
				//表明暂时不回复，然后再立即使用客服消息接口发送消息回复粉丝
				$contentx                 = str_replace($needle, '', $response['Content']);//将$query_auth_code$的值赋值给API所需的参数authorization_code
				$this->authorization_code = $contentx;//authorization_code
				trace($contentx, 'authorization_code');
				
				//使用授权码换取公众号或小程序的接口调用凭据和授权信息
				$postdata = [
					"component_appid"    => $this->appid,
					"authorization_code" => $this->authorization_code,
				];
				
				$this->component_access_token = $this->component->getAccessToken();
				
				trace($this->component_access_token, 'access_token');
				
				$component_return = send_post($this->authorizer_access_token_url . $this->component_access_token, $postdata);
				
				$component_return = json_decode($component_return, true);
				trace($component_return, '$component_return');
				$this->authorizer_access_token = $test_token = $component_return['authorization_info']['authorizer_access_token'];
				$content_re                    = $contentx . "_from_api";
				echo '';
				
				//調用客服接口
				
				$data = [
					"touser"  => $response['FromUserName'],
					"msgtype" => "text",
					"text"    => [
						"content" => $content_re,
					],
				];
				$url  = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $test_token;
				$ret  = send_post($url, $data);
				trace($ret, '客服消息');
				
			} else {
				//2、模拟粉丝发送文本消息给专用测试公众号
				$contentx = "TESTCOMPONENT_MSG_TYPE_TEXT_callback";
				
				
				trace($response, "2模拟粉丝发送文本消息给专用测试公众号: ");
				$responseText = sprintf($textTpl, $response['FromUserName'], $response['ToUserName'], $response['CreateTime'], $contentx);
//                echo $responseText;
				$echo_msg = '';
				$errCode  = $pc->encryptMsg($responseText, $timeStamp, $nonce, $echo_msg);
				trace($responseText, "2222转数组: ");
				echo $echo_msg;
			}
		}
		
		//1、模拟粉丝触发专用测试公众号的事件
		
		if ($response['MsgType'] == 'event') {
			$content = $response['Event'] . "from_callback";
			
			trace($response, "111转数组: ");
			$responseText = sprintf($textTpl, $response['FromUserName'], $response['ToUserName'], $response['CreateTime'], $content);
			trace($responseText, "111: ");
//            echo $responseText;
			$errCode = $pc->encryptMsg($responseText, $timeStamp, $nonce, $echo_msg);
			
			echo $echo_msg;
		}
		
		
	}
}