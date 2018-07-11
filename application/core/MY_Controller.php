<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/4/8
 * Time:        09:11
 * Describe:    授权登录token验证Hook
 */
class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');


        //测试使用
        /*if(defined('CURRENT_ID'))
        {
            $pre    = substr(CURRENT_ID,0,2);
            if($pre == SUPERPRE){
                //super 拥有所有的权限
                $this->position = 'SUPER';
            }else{
                $this->position = 'EMPLOYEE';
                $this->load->model('employeemodel');
                $this->employee = Employeemodel::where('bxid',CURRENT_ID)->first();
            }
        }else{
            $this->load->model('employeemodel');
            $this->employee = Employeemodel::find(1);
            //define('CURRENT_ID',1001);
            //define('COMPANY_ID',4);
        }*/
        /*if(defined('CURRENT_ID')){
            $this->load->model('employeemodel');
            $this->employee = Employeemodel::where('bxid',CURRENT_ID)->first();
            echo 1;exit;
        }*/
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
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
        curl_setopt($ch,CURLOPT_HEADER,0);
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
     * 表单验证
     * 传入config数组
     * config数组中包含 field 和config 两个类型
     * example $config=['filed'=>['a','b','c'],'config'=>[['field'=>'a'....],['field'=>'b'....]]]
     */
    public function validationText($config,$data=[])
    {
        if(!empty($data)&&is_array($data))
        {
            $this->load->library('form_validation');
            $this->form_validation->set_data($data)->set_rules($config);
            if(!$this->form_validation->run())
            {
                return false;
            }else{
                return true;
            }
        }
        $this->load->library('form_validation');
        $this->form_validation->set_rules($config);
        if(!$this->form_validation->run())
        {
            return false;
        }else{
            return true;
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
                return $OBJ->error($field,NULL,NULL);
            }
        }
    }

    /**
     * 将alioss路径 拼接成完整的URL
     */
    public function fullAliossUrl($oss_path,$bool=false){
        if(empty($oss_path)){
            return null;
        }
        if($bool==true&&is_array($oss_path)){
            foreach ($oss_path as $path){
                $full_url[]   = config_item('cdn_path').$path;
            }
        }else{
            $full_url   = config_item('cdn_path').$oss_path;
        }
        return $full_url;
    }

    /**
     * 对上传的文件url拆解成alioss路径
     * true传入数组 对数组进行遍历拆解
     */
    public function splitAliossUrl($full_path,$bool=false){
        if($bool==true){
            $alioss_path = [];
            foreach ($full_path as $path){
                $split   = substr($path,strlen(config_item('cdn_path')));
                if(!$split){
                    throw new Exception('截取url失败');
                }
                $alioss_path[]=$split;
            }
        }else{
            if(!$alioss_path    = substr($full_path,strlen(config_item('cdn_path')))){
                throw new Exception('截取url失败');
            }
        }
        return $alioss_path;
    }

    /**
     * 判断是否是公寓管理员或者是超级管理员
     */
    public function position(){

    }

    /**
     * 获取并检查输入的年份
     * 要求输入的 key 为 year
     */
    protected function checkAndGetYear($year,$withDefault = true)
    {

        if (preg_match('/^(19|20)[0-9]{2}$/', $year)) {
            return $year;
        }

        if ($withDefault) {
            return date('Y');
        }

        return null;
    }

    /**
     * 获取输入的月份值，要求键为 month
     */
    protected function checkAndGetMonth($month,$withDefault = true)
    {

        if (preg_match('/^(0?[1-9]|1[0-2])$/', $month)) {
            return $month;
        }

        if ($withDefault) {
            return date('n');
        }

        return null;
    }

    /**
     * 判断是不是管理员
     */
    public function isAdmin()
    {
        return ($this->employee->position   == 'ADMIN');
    }

    /**
     * 检测当前权限是否是公寓管理员
     */
    protected function isApartment()
    {
        return ($this->employee->position == 'APARTMENT');
    }

    /**
     * 财务
     */
    protected function isFinance()
    {
        return ($this->employee->position == 'FINANCE');
    }


    /**
     * 处理请请求参数中的 apartment_id
     */
    protected function apartmentIdFilter()
    {
        if ($this->isApartment()) {
            return $this->employee->store_id;
        }

        if ($this->isAdmin()) {
            return $this->input->post('apartment_id');
        }

        return 0;
    }

}
