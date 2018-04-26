<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/4/8
 * Time:        09:11
 * Describe:    授权登录token验证Hook
 */
class MY_Controller extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    //API返回统一方法
    public function api_res($code,$data = false)
    {

        $msg = $this->config->item('api_code')[$code];
        if ($data) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('rescode'=>$code,'resmsg'=>$msg,'data'=>$data)));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('rescode'=>$code,'resmsg'=>$msg,'data'=>[])));
        }
    }

    /**
     *  $url 		curl请求的网址
     *  $type		curl请求的方式，默认get
     *  $res		curl 是否把返回的json数据转换成数组
     *  $arr		curl post传递的数据
     */
    public function httpCurl($url,$method='get',$res='',$arr=''){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_REFERER,0);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array("content-type: application/x-www-form-urlencoded;charset=UTF-8"));
        if($method=='post'){
            curl_setopt($ch,CURLOPT_POST,true);        // 开启post提交
            curl_setopt($ch,CURLOPT_POSTFIELDS,$arr); //post 数据  http_build_query($data)
        }
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0); //CURLOPT_SSL_VERIFYHOST 设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。
        $output = curl_exec($ch);
        if(curl_errno($ch)){
            echo curl_error($ch);
            curl_close($ch);
            return false;
        }else{
            $encode = mb_detect_encoding($output, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
            if($encode !== "UTF-8"){
                $output = mb_convert_encoding($output, "UTF-8", $encode);
            }
            if($res =='json'){
                $output = json_decode($output,true);
            }
            curl_close($ch);
            return $output;
        }

    }

    /**
     * @param array $fieldarr 传入验证字段的数组
     * @return string 返回验证表单的第一个错误信息方便 ajax返回
     */
    function form_first_error($fieldarr = [])
    {
        if (FALSE === ($OBJ =& _get_validation_object()))
        {
            return '';
        }
        foreach ($fieldarr as $field){
            if($OBJ->error($field)){
                return $OBJ->error($field);
            }
        }
    }

    /**
     * 拼接完整的 alioss URL
     */
    public function fullAliossUrl($oss_path){

        $full_url   = config_item('cdn_path').$oss_path;
        return $full_url;
    }
}
