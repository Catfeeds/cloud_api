<?php
defined('BASEPATH') OR exit('No direct script access allowed');


/**
 * User: weijinlong
 * Date: 2018-08-16
 * Time: 10:37
 */
/**
 * for test
 */
class Index extends MY_Controller
{
    public function __construct() {
        parent::__construct();
       
    }

    public function index()
    {
        echo 'ok->'.get_instance()->x_api_token;
    }

    //调用demo
    public function client()
    {
        $apikey='111111111';
        $apisecret='nf239fh293hf8h23f';
        $timestamp=time();

        $hash=$apihash = hash('sha256',"$apikey.$timestamp.$apisecret");
        $x_api_token = "$apikey.$timestamp.$hash";

        //调用curl
        //此处省略初始化代码
        $header[0] = "content-type: application/x-www-form-urlencoded;charset=UTF-8"; 
        $header[] = "x-api-token: $x_api_token"; 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
}
