<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/24 0024
 * Time:        16:47
 * Describe:
 */
class Login extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
        $this->load->model('companymodel');
        $this->load->library('m_redis');
    }
    /**
     * 手机或者微信登陆
     * 接收参数 type code
     */
    public function login(){
        $input  = $this->input->post(NULL, TRUE);
        if(!isset($input['type']))
        {
            $this->api_res(1006);
            return false;
        }
        $type   = $input['type'];
        switch($type)
        {
            case "wechat":
                if(!isset($input['code']) || empty($input['code']))
                {
                    $this->api_res(1006);
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
                $phone  = trim($input['phone']);
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
                $phone  = trim($input['phone']);
                $code   = trim($input['code']);
                $this->verifyPhoneCode($phone,$code);
                break;

            default:
                $this->api_res(1006);
                return false;
        }

    }
    /**
     * 微信登陆
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
            log_message('error',$user['errmsg']);
            $this->api_res(1006);
            return false;
        }
        $access_token   = $user['access_token'];
        $refresh_token  = $user['refresh_token'];
        $openid         = $user['openid'];
        $unionid        = $user['unionid'];
        if($user = $this->getInfo('wechat',$user[WXID]))
        {
            //判断用户的身份
            $position   = $user->base_position;
            $bxid       = $user->bxid;
            if('SUPER'==$position){
                //S_100001
                $bxid   = SUPERPRE.$bxid;
                //把个人信息存入redis
                $this->m_redis->storeUserInfo($bxid,$user->toJson());
                //把公司信息存入redis
                $company_id = $user->id;
                $this->m_redis->storeCompanyInfo($company_id,$user->toJson());
            }else{
                //把个人信息存入redis
                $this->m_redis->storeUserInfo($bxid,$user->toJson());
                $company_id = $user->company_id;
                //从redis获取公司信息并刷新，如果没有 则查数据库并存储到redis
                if(!$this->m_redis->getCompanyInfo($company_id,true))
                {
                    $company_info    = Companymodel::find($company_id);
                    $this->m_redis->storeCompanyInfo($company_id,$company_info->toJson());
                }
            }
            $token  = $this->m_jwt->generateJwtToken($bxid,$company_id);
            $privilege  = json_decode($this->m_redis->getCompanyInfo($company_id))->privilege;
            $this->api_res(0,['bxid'=>$bxid,'token'=>$token,'privilege'=>$privilege]);
        }
        else
        {
            $this->api_res(1003);
        }
    }

    /**
     * 手机登陆 发送验证码
     */
    public function phoneLogin($phone)
    {
        if($user=$this->getInfo('phone',$phone))
        {
            if(!$this->m_redis->ttlSmsCode($phone))
            {

                $this->api_res(10007);
                return false;
            }
            $this->load->library('sms');
            $code   = str_pad(rand(1,9999),4,0,STR_PAD_LEFT);
            $str    = SMSTEXT.$code;
            $this->m_redis->storeSmsCode($phone,$code);
            $this->sms->send($str,$phone);
            $this->api_res(0);
        }
        else
        {
            $this->api_res(1003);
        }
    }

    /**
     * 手机登陆 验证验证码
     * @param $phone
     * @param $code
     */

    public function verifyPhoneCode($phone,$code){
        $user=$this->getInfo('phone',$phone);
        if(!$user)
        {
            $this->api_res(1006);
        }
        //暂时关闭验证短信验证码功能
        //if($this->m_redis->verifySmsCode($phone,$code))
        if(true)
        {
            //判断用户的身份
            $position   = $user->base_position;
            $bxid       = $user->bxid;
            if('SUPER'==$position){
                //S_100001
                $bxid   = SUPERPRE.$bxid;
                //把个人信息存入redis
                $this->m_redis->storeUserInfo($bxid,$user->toJson());
                //把公司信息存入redis
                $company_id = $user->id;
                $this->m_redis->storeCompanyInfo($company_id,$user->toJson());
            }else{
                //把个人信息存入redis
                $this->m_redis->storeUserInfo($bxid,$user->toJson());
                //从redis获取公司信息并刷新，如果没有 则查数据库并存储到redis
                $company_id = $user->company_id;
                if(!$this->m_redis->getCompanyInfo($company_id,true))
                {
                    $company_info    = Companymodel::find($company_id);
                    $this->m_redis->storeCompanyInfo($company_id,$company_info->toJson());
                }
            }
            $token  = $this->m_jwt->generateJwtToken($bxid,$company_id);
            $privilege  = json_decode($this->m_redis->getCompanyInfo($company_id))->privilege;
            $this->api_res(0,['bxid'=>$bxid,'token'=>$token,'privilege'=>$privilege]);
        }
        else
        {
            $this->api_res(10008);
        }
    }

    /**
     * @param $type 'wechat','phone'
     * @param $sign string wechat/phone
     * return $bxid or FALSE
     */
    public function getInfo($type,$sign)
    {
        switch ($type) {
            case 'wechat':
                //查找employee表 有无信息
                $info = $this->employeemodel->getInfo('wechat', $sign);
                if (!$info) {
                    $info = $this->companymodel->getInfo('wechat', $sign);
                }
                break;
            case 'phone':
                $info = $this->employeemodel->getInfo('phone', $sign);
                if (!$info) {
                    $info = $this->companymodel->getInfo('phone', $sign);
                }
                break;
            default:
                $info = null;
                break;
        }
        return $info;
    }

}
