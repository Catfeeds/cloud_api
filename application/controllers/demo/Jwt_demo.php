<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \Firebase\JWT\JWT;

class Jwt_demo extends MY_Controller {

    //登录 ，参考https://blog.csdn.net/qq_15096707/article/details/51693593
    public function login()
    {
        $key = $this->config->item('jwt_key');
        $alg= $this->config->item('jwt_alg');

        //token中各字段含义参见JWT payload的说明
        $token = array(
            "iss" => "http://tapi.funxdata.com", 
            "exp" => strtotime("+1 day"),  //过期时间
            "nbf" => 1357000000, 
            "waid" => 100001  //自添加字段，租户ID
        );

        $jwt = JWT::encode($token, $key);
                
        //将$jwt返回给客户端，客户端存储于localstorange中，每次调用其他API时传递此参数
        $this->api_res(0,['token'=>$jwt]);
    }

    //其他API
    public function otherAPI()
    {
        
        $key = $this->config->item('jwt_key');
        $alg= $this->config->item('jwt_alg');
        //由客户端传递而来的token
        $jwt="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLm9yZyIsImV4cCI6MTUyMjY0NzEwMiwibmJmIjoxMzU3MDAwMDAwLCJ3YWlkIjoxMDAwMDF9.kkHStImIeUG7iyxGImH882acc9n4g-jj-XKG0Ie3UcE"; 

        //解码token，从中取出waid使用。如果登录失效则会抛出异常终止执行
        $decoded = JWT::decode($jwt, $key, array($alg));
        print_r($decoded);


        //其他业务代码。。。。
        //do_something
    }
}