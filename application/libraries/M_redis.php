<?php

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/2
 * Time:        21:13
 * Describe:    使用原生redis，扩展方法写在这里  此文件在autoload自动加载
 */

class M_redis
{
    public $redis;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->config->load('redis', TRUE);
 
        $this->redis    = new Redis();
        $this->redis->connect($CI->config->item('redis')['host'],
                                $CI->config->item('redis')['port'],
                                $CI->config->item('redis')['timeout']);

        $this->redis->auth($CI->config->item('redis')['password']);

    }


    /**
     * redis中存储短信验证码
     * @param $phone int    手机号
     * @param $code int     短信验证码
     * return   bool
     */
    public function storeSmsCode($phone,$code){
        $key    = FXPHONE.$phone;
        $val    = $code;
        $this->redis->set($key,$val,600);
    }

    /**
     * 验证手机验证码
     * @param $phone int    手机号
     * @param $code int     短信验证码
     * return   bool
     */
    public function verifySmsCode($phone,$code){
        $key    = FXPHONE.$phone;
        if($code !== $this->redis->get($key)){
            return false;
        }
        $this->redis->expire($key,-1);
        return true;
    }

    /**
     * 刷新短信验证码
     * @param $phone
     * return bool
     */
    public function ttlSmsCode($phone){
        $key    = FXPHONE.$phone;
        if( $this->redis->exists($key) ){
            if( ($this->redis->ttl($key))>540) {
                return false;
            }
        }
        return true;
    }

    /**
     * 存储token 存在则覆盖
     * @param $fxid
     * @param $token
     */
    public function storeToken($fxid,$token){
        $key    = FXTOKEN.$fxid;
        $val    = $token;
        $this->redis->set($key,$val,2*60*60);
    }

    public function getToken($fxid){
        $key    = FXTOKEN.$fxid;
        $token  = $this->redis->get($key);
        return $token;
    }

}