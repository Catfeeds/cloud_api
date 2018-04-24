<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/2
 * Time:        21:13
 * Describe:    微信及短信登录
 */

use \Firebase\JWT\JWT;
use EasyWeChat\Foundation\Application;

class Login extends MY_Controller
{

    public function __construct()
    {

        parent::__construct();

    }

    /**
     * 登陆 手机或者微信
     * 接收参数 type code
     */
    public function login()
    {
        try{
            $input  = $this->input->post(NULL, TRUE);
            if(!isset($input['type']))
            {
                $this->api_res(10001);
                return false;
            }
            $type   = $input['type'];
            switch($type)
            {
                case "wechat":
                    if(!isset($input['code']) || empty($input['code']))
                    {
                        $this->api_res(10002);
                        return false;
                    }
                    // 微信登陆逻辑
                    $code = $input['code'];
                    $this->wechatLogin($code);
                    break;
                case "phone":
                    if(!isset($input['phone']) || empty($input['phone']))
                    {
                        $this->api_res(10003);
                        return false;
                    }
                    //手机号码登陆逻辑
                    $phone  = $input['phone'];
                    $admin_user = Funxadminmodel::where('phone',$phone)->first();
                    if(!$admin_user){
                        $this->api_res(1003);
                        return false;
                    }
                    $this->phoneLogin($phone);
                    break;
                case "verify_phone_code":
                    if(!isset($input['phone']) || empty($input['phone']))
                    {
                        $this->api_res(10003);
                        return false;
                    }
                    if(!isset($input['code']) || empty($input['code']))
                    {
                        $this->api_res(10002);
                        return false;
                    }
                    $phone  = $input['phone'];
                    $code   = $input['code'];
                    $admin_user = Funxadminmodel::where('phone',$phone)->first();
                    if(!$admin_user){
                        $this->api_res(1003);
                        return false;
                    }
                    $this->phoneLogin($phone,$code);
                    break;

                default:
                    $this->api_res(10005);
                    return false;
            }
        }catch (Exception $e){
            log_message('error',$e->getMessage());
            $this->api_res(500);
        }
    }


    /**
     * 微信开放平台登陆
     */
    public function wechatLogin($code='')
    {

        $code   = str_replace(' ','',trim(strip_tags($code)));
        $appid  = config_item('wx_web_appid');
        $secret = config_item('wx_web_secret');
        $url    = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
        $user   = $this->httpCurl($url,'get','json');
        if(array_key_exists('errcode',$user))
        {
            //echo $user['errmsg'];
            $this->api_res(10006);
            return false;
        }
        $access_token   = $user['access_token'];
        $refresh_token  = $user['refresh_token'];
        $openid         = $user['openid'];
        $unionid        = $user['unionid'];
        if($user = Funxadminmodel::where(WXID,$user[WXID])->first())
        {

            $fxid   = $user->fxid ;
            $token  = $this->m_jwt->generateJwtToken($fxid);
            //$this->m_redis->storeToken($fxid,$token);
            $this->api_res(0,['current_id'=>$fxid,'token'=>$token]);
        }
        else
        {

            $this->api_res(1003);
        }
    }

    /**
     * 手机登陆
     */
    public function phoneLogin($phone,$code=0)
    {
        if(!$code)
        {
            $this->load->library('m_redis');
            if(!$this->m_redis->ttlSmsCode($phone))
            {

                $this->api_res(10007);
                return false;
            }
            try{
                $this->load->library('sms');
                $code   = str_pad(rand(1,9999),4,0,STR_PAD_LEFT);
                $str    = '【火花草莓社区】您的验证码是'.$code;
                $this->m_redis->storeSmsCode($phone,$code);
                $this->sms->send($str,$phone);
                $this->api_res(0);
            }catch (Exception $e){
                $this->api_res(10009);
            }

        }
        else
        {
            //暂时关闭验证短信验证码功能
            /*$this->load->library('m_redis');
            if($this->m_redis->verifySmsCode($phone,$code))
            {*/
                $fxid   = Funxadminmodel::where('phone',$phone)->first()->fxid;
                $token  = $this->m_jwt->generateJwtToken($fxid);
                $this->api_res(0,['current_id'=>$fxid,'token'=>$token]);
            /*}
            else
            {

                $this->api_res(10008);
            }*/
        }
    }

}