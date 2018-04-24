<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \Firebase\JWT\JWT;

class Base_demo extends MY_Controller {
    public function index(){
        //接收参数.
        //判断参数合法性
        //调用model获取返回值
        
        //调用扩展api返回结果
        $this->api_res(0,['a'=>1,'b'=>2]);
    }
}