<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/8/27
 * Time:        14:47
 * Describe:    微信相关操作
 */
class Wechat
{
	/**
	 * 通过code换取access_token
	 */
	public function getAccessToken($code)
	{
		$appid  = config_item('wx_web_appid');
		$secret = config_item('wx_web_secret');
		$url    = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
		$user   = $this->httpCurl($url, 'get', 'json');
		if (array_key_exists('errcode', $user) || empty($user['openid'])) {
			log_message('error', 'GET_ACCESS_TOKEN' . $user['errmsg']);
			$this->api_res(1006);
			return false;
		}
		$this->debug('返回用户信息为-->'.$user);
		return $user;
	}
	
	/**
	 * 通过access_token获取用户信息
	 */
	public function getWXUserInfo($user)
	{
		$info_url  = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $user['access_token']. '&openid=' . $user['openid']. '&lang=zh_CN';
		$user_info = $this->httpCurl($info_url, 'get', 'json');
		if (array_key_exists('errcode', $user_info)) {
			log_message('error', '请求info:' . $user_info['errmsg']);
			$this->api_res(1006);
			return false;
		}
		return $user_info;
	}
}