<?php
defined('BASEPATH') OR exit('No direct script access allowed');
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
		$this->debug('授权回调携带参数为-->', $input);
		$auth_code  = empty($input['auth_code']) ? "" : trim($input['auth_code']);
		$expires_in = empty($input['expires_in']) ? "" : trim($input['expires_in']);
		$this->getAuthRefreshToken($auth_code);
		return true;
	}
	
	/**
	 * 功能：通过授权码获取
	 *      授权方接口调用令牌authorizer_access_token
	 *      接口调用凭据刷新令牌authorizer_refresh_token
	 */
	public function getAuthRefreshToken($auth_code)
	{
		if ($this->m_redis->getAccessToken()) {
			$access_token = $this->m_redis->getAccessToken();
		} else {
			$access_token = $this->getAccessToken();
		}
		$url  = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' . "$access_token";
		$data = [
			'component_appid'    => $this->appid,
			'authorization_code' => $auth_code,
		];
		$this->debug('POST参数为-->', $data);
		$res = $this->httpCurl($url, 'post', 'json', json_encode($data, true));
		if (array_key_exists('errcode', $res)) {
			log_message('error', '获取authorizer_access_token失败--> ' . $res['errmsg']);
			return false;
		} else {
			log_message('debug', '获取authorizer_access_token成功');
			$this->m_redis->saveAuthorAccessToken($res['authorization_info']['authorizer_access_token']);
			$this->load->model('companymodel');
			$company_id                       = COMPANY_ID;
			$company                          = Companymodel::where('id', $company_id)->first();
			$company->authorizer_appid        = $res['authorization_info']['authorizer_appid'];
			$company->authorizer_access_token = $res['authorization_info']['authorizer_refresh_token'];
			if ($company->save()) {
				return true;
			} else {
				$this->api_res(1009);
				return false;
			}
		}
	}
	
	/**
	 * 功能：通过授权方的刷新令牌获取令牌
	 */
	public function getAuthToken()
	{
		if ($this->m_redis->getAccessToken()) {
			$access_token = $this->m_redis->getAccessToken();
		} else {
			$access_token = $this->getAccessToken();
		}
		$this->load->model('companymodel');
		$company    = COMPANY_ID;
		$authorizer = Companymodel::where('id', $company)->first(['authorizer_refresh_token', 'authorizer_appid']);
		$url        = 'https:// api.weixin.qq.com /cgi-bin/component/api_authorizer_token?component_access_token=' . "$access_token";
		$data       = [
			'component_appid'          => $this->appid,
			'authorizer_appid'         => $authorizer->authorizer_appid,
			'authorizer_refresh_token' => $authorizer->authorizer_refresh_token,
		];
		$this->debug('POST参数为-->', $data);
		$res = $this->httpCurl($url, 'post', 'json', json_encode($data, true));
		if (array_key_exists('errcode', $res)) {
			log_message('error', '获取授权方令牌失败--> ' . $res['errmsg']);
			return false;
		} else {
			log_message('debug', '--获取授权方成功--');
			$this->m_redis->saveAuthorAccessToken($res['authorizer_access_token']);
			return true;
		}
	}
	
	/**
	 * 功能：获取授权方的账号基本信息
	 */
	public function getAuthorInfo()
	{
		if ($this->m_redis->getAccessToken()) {
			$access_token = $this->m_redis->getAccessToken();
		} else {
			$access_token = $this->getAccessToken();
		}
		$this->load->model('companymodel');
		$company    = COMPANY_ID;
		$authorizer = Companymodel::where('id', $company)->first(['authorizer_refresh_token', 'authorizer_appid']);
		$url        = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=' . "$access_token";
		$data       = [
			'component_appid'  => $this->appid,
			'authorizer_appid' => $authorizer->authorizer_appid,
		];
		$this->debug('POST参数为-->', $data);
		$res = $this->httpCurl($url, 'post', 'json', json_encode($data, true));
		if (array_key_exists('errcode', $res)) {
			log_message('error', '获取授权方信息失败--> ' . $res['errmsg']);
			return false;
		} else {
			log_message('debug', '--获取授权方信息成功--');
			$this->load->model('companywxinfomodel');
			$company_id                 = COMPANY_ID;
			$company                    = Companywxinfomodel::where('id', $company_id)->first();
			$company->nick_name         = $res['authorizer_info']->nick_name;
			$company->head_img          = $res['authorizer_info']->head_img;
			$company->service_type_info = $res['authorizer_info']->service_type_info;
			$company->verify_type_info  = $res['authorizer_info']->verify_type_info;
			$company->user_name         = $res['authorizer_info']->user_name;
			$company->principal_name    = $res['authorizer_info']->principal_name;
			$company->alias             = $res['authorizer_info']->alias;
			$company->qrcode_url        = $res['authorizer_info']->qrcode_url;
			$company->open_store        = $res['authorizer_info']->business_info->open_store;
			$company->open_scan         = $res['authorizer_info']->business_info->open_scan;
			$company->open_pay          = $res['authorizer_info']->business_info->open_pay;
			$company->open_card         = $res['authorizer_info']->business_info->open_card;
			$company->open_shake        = $res['authorizer_info']->business_info->open_shake;
			$company->func_info         = json_encode($res['authorization_info']->func_info, true);
			if ($company->save()) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	/**
	 * 功能：获取预授权码pre_auth_code
	 * 请求参数：
	 *      component_access_token： 第三方access_token(get方式)
	 *      component_appid：        第三方平台方appid(post方式)
	 * 返回值：
	 *      pre_auth_code：            预授权码
	 *      expires_in：               有效期，为10分钟
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
		$this->debug('POST参数为-->', $data);
		$res = $this->httpCurl($url, 'post', 'json', json_encode($data, true));
		$this->debug('获取预授权码-->', $res);
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
		$this->debug('POST参数为-->', $data);
		$res = $this->httpCurl($url, 'post', 'json', json_encode($data, true));
		$this->debug('获取AccessToken返回-->', $res);
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
	
	/**
	 * 消息与事件接收URL
	 */
	function callBack1($appid)
	{
		log_message('info', 'AppId' . "$appid" . '发来消息');
		$input      = $this->input->get(null, true);     //url上携带参数
		$encryptMsg = file_get_contents('php://input');//微信推送信息
		$this->debug('url上携带参数为', $input);
		$timestamp = empty($input['timestamp']) ? "" : trim($input['timestamp']);
		$nonce     = empty($input['nonce']) ? "" : trim($input['nonce']);
		$msg_sign  = empty($input['msg_signature']) ? "" : trim($input['msg_signature']);
		//解密
		$pc      = new WXBizMsgCrypt($this->token, $this->aesKey, $this->appid);
		$msg     = '';
		$errCode = $pc->decryptMsg($msg_sign, $timestamp, $nonce, $encryptMsg, $msg);
		if ($errCode != 0) {
			log_message('error', '解码失败-->' . $errCode);
		}
		log_message('info', '解密后-->' . "$msg");
		$param = json_decode(json_encode(simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		//生成返回公众号的消息
		$xmlTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
		//判断事件
		$keyword = isset ($param ['Content']) ? trim($param ['Content']) : '';
		if (isset($param ['Event']) && $param ['ToUserName'] == 'gh_3c884a361561') { // 案例1
			$contentStr = $param ['Event'] . 'from_callback';
		} elseif ($keyword == "TESTCOMPONENT_MSG_TYPE_TEXT") { // 案例2
			$contentStr = "TESTCOMPONENT_MSG_TYPE_TEXT_callback";
		} elseif (strpos($keyword, "QUERY_AUTH_CODE:") !== false) { // 案例3
			$ticket                          = str_replace("QUERY_AUTH_CODE:", "", $keyword);
			$contentStr                      = $ticket . "_from_api";
			$tokenInfo                       = WechatOpenApiLogic::getAuthorizerAccessTokenByAuthCode($ticket);
			$param ['authorizerAccessToken'] = $tokenInfo ['authorization_info'] ['authorizer_access_token'];
			
			self::sendServiceMsg($param ['FromUserName'], $param ['ToUserName'], 1, $contentStr); // 客服消息接口
			
			return 1;
		}
		$result = '';
		if (!empty ($contentStr)) {
			$result = sprintf($xmlTpl, $param ['FromUserName'], $param ['ToUserName'], time(), $contentStr);
			if (isset ($_GET ['encrypt_type']) && $_GET ['encrypt_type'] == 'aes') { // 密文传输
				$msgCryptObj = new WXBizMsgCrypt ($this->token, $this->aesKey, $this->appid);
				$encryptMsg  = '';
				$msgCryptObj->encryptMsg($result, $_GET ['timestamp'], $_GET ['nonce'], $encryptMsg);
				$result = $encryptMsg;
			}
		}
	}
	
	
	/**
	 * 消息与事件接收URL
	 */
	public function callBack($appid)
	{
		// 每个授权小程序的appid，在第三方平台的消息与事件接收URL中设置了 $APPID$
		log_message('info', 'AppId' . "$appid" . '发来消息');
		// 每个授权小程序传来的加密消息
		$postStr = file_get_contents("php://input");
		if (!empty($postStr)) {
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			
			$toUserName = trim($postObj->ToUserName);
			$encrypt    = trim($postObj->Encrypt);
			
			$format   = "<xml>
						<ToUserName><![CDATA[{$toUserName}]]></ToUserName>
						<Encrypt><![CDATA[%s]]></Encrypt></xml>";
			$from_xml = sprintf($format, $encrypt);
			
			$inputs = [
				'encrypt_type'  => '',
				'timestamp'     => '',
				'nonce'         => '',
				'msg_signature' => '',
				'signature'     => '',
			];
			foreach ($inputs as $key => $value) {
				$tmp = $_REQUEST[$key];
				if (!empty($tmp)) {
					$inputs[$key] = $tmp;
				}
			}
			
			// 第三方收到公众号平台发送的消息
			$msg            = '';
			$timeStamp      = $inputs['timestamp'];
			$msg_sign       = $inputs['msg_signature'];
			$nonce          = $inputs['nonce'];
			$token          = $this->token;
			$encodingAesKey = $this->aesKey;
			$appid          = $this->appid;
			$appsecret      = $this->secret;
			$pc             = new WXBizMsgCrypt($token, $encodingAesKey, $appid);
			$errCode        = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
			if ($errCode == 0) {
				$msgObj  = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
				$content = trim($msgObj->Content);
				//第三方平台全网发布检测普通文本消息测试
				if (strtolower($msgObj->MsgType) == 'text' && $content == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
					$toUsername = trim($msgObj->ToUserName);
					if ($toUsername == 'gh_08cb40357652') {
						$content = 'TESTCOMPONENT_MSG_TYPE_TEXT_callback';
						echo $this->responseText($msgObj, $content);
					}
				}
				//第三方平台全网发布检测返回api文本消息测试
				if (strpos($content, 'QUERY_AUTH_CODE') !== false) {
					$toUsername = trim($msgObj->ToUserName);
					if ($toUsername == 'gh_08cb40357652') {
						$query_auth_code = str_replace('QUERY_AUTH_CODE:', '', $content);
						if ($this->m_redis->getAccessToken()) {
							$access_token = $this->m_redis->getAccessToken();
						} else {
							$access_token = $this->getAccessToken();
						}
						$url  = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' . "$access_token";
						$data = [
							'component_appid'    => $this->appid,
							'authorization_code' => $query_auth_code,
						];
						$this->debug('POST参数为-->', $data);
						$res = $this->httpCurl($url, 'post', 'json', json_encode($data, true));
						if (array_key_exists('errcode', $res)) {
							log_message('error', '获取authorizer_access_token失败--> ' . $res['errmsg']);
							return false;
						} else {
							log_message('debug', '获取authorizer_access_token成功');
							$authorizer_access_token = $res['authorization_info']['authorizer_access_token'];
						}
						$content = "{$query_auth_code}_from_api";
						$this->sendServiceText($msgObj, $content, $authorizer_access_token);
					}
				}
			}
		}
		echo "success";
	}
	
	/**
	 * 自动回复文本
	 */
	public function responseText($object = '', $content = '')
	{
		if (!isset($content) || empty($content)) {
			return "";
		}
		$xmlTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                    </xml>";
		$result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
		return $result;
	}
	
	/**
	 * 发送文本消息
	 */
	public function sendServiceText($object = '', $content = '', $access_token = '')
	{
		/* 获得openId值 */
		$openid    = (string)$object->FromUserName;
		$post_data = [
			'touser'  => $openid,
			'msgtype' => 'text',
			'text'    => [
				'content' => $content,
			],
		];
		$this->sendMessages($post_data, $access_token);
	}
	
	/**
	 * 发送消息-客服消息
	 */
	public function sendMessages($post_data = [], $access_token = '')
	{
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
		$this->httpCurl($url, 'POST', 'json', json_encode($post_data, JSON_UNESCAPED_UNICODE));
	}
}