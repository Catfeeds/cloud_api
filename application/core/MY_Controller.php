<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/4/8
 * Time:        09:11
 * Describe:    授权登录token验证Hook
 */
class MY_Controller extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    //API返回统一方法
    public function api_res($code, $data = false) {

        $msg = $this->config->item('api_code')[$code];
        if ($data) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('rescode' => $code, 'resmsg' => $msg, 'data' => $data)));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('rescode' => $code, 'resmsg' => $msg, 'data' => [])));
        }
    }

    /**
     *  $url         curl请求的网址
     *  $type        curl请求的方式，默认get
     *  $res        curl 是否把返回的json数据转换成数组
     *  $arr        curl post传递的数据
     */
    public function httpCurl($url, $method = 'get', $res = '', $arr = '') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_REFERER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("content-type: application/x-www-form-urlencoded;charset=UTF-8"));
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true); // 开启post提交
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr); //post 数据  http_build_query($data)
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //CURLOPT_SSL_VERIFYHOST 设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            echo curl_error($ch);
            curl_close($ch);
            return false;
        } else {
            $encode = mb_detect_encoding($output, array("ASCII", "UTF-8", "GB2312", "GBK", "BIG5"));
            if ($encode !== "UTF-8") {
                $output = mb_convert_encoding($output, "UTF-8", $encode);
            }
            if ($res == 'json') {
                $output = json_decode($output, true);
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
    public function validationText($config, $data = []) {
        if (!empty($data) && is_array($data)) {
            $this->load->library('form_validation');
            $this->form_validation->set_data($data)->set_rules($config);
            if (!$this->form_validation->run()) {
                return false;
            } else {
                return true;
            }
        }
        $this->load->library('form_validation');
        $this->form_validation->set_rules($config);
        if (!$this->form_validation->run()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param array $fieldarr 传入验证字段的数组
     * @return string 返回验证表单的第一个错误信息方便 ajax返回
     */
    public function form_first_error($fieldarr = []) {
        if (FALSE === ($OBJ = &_get_validation_object())) {
            return '';
        }
        foreach ($fieldarr as $field) {
            if ($OBJ->error($field)) {
                return $OBJ->error($field, NULL, NULL);
            }
        }
    }

    /**
     * 将alioss路径 拼接成完整的URL
     */
    public function fullAliossUrl($oss_path, $bool = false) {
        if (empty($oss_path)) {
            return null;
        }
        if ($bool == true && is_array($oss_path)) {
            foreach ($oss_path as $path) {
                $full_url[] = config_item('cdn_path') . $path;
            }
        } else {
            $full_url = config_item('cdn_path') . $oss_path;
        }
        return $full_url;
    }

    /**
     * 对上传的文件url拆解成alioss路径
     * true传入数组 对数组进行遍历拆解
     */
    public function splitAliossUrl($full_path, $bool = false) {
        if ($bool == true) {
            $alioss_path = [];
            foreach ($full_path as $path) {
                $split = substr($path, strlen(config_item('cdn_path')));
                if (!$split) {
                    throw new Exception('截取url失败');
                }
                $alioss_path[] = $split;
            }
        } else {
            if (!$alioss_path = substr($full_path, strlen(config_item('cdn_path')))) {
                throw new Exception('截取url失败');
            }
        }
        return $alioss_path;
    }

    /**
     * 判断是否是公寓管理员或者是超级管理员
     */
    public function position() {

    }

    /**
     * 获取并检查输入的年份
     * 要求输入的 key 为 year
     */
    protected function checkAndGetYear($year, $withDefault = true) {

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
    protected function checkAndGetMonth($month, $withDefault = true) {

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
    public function isAdmin() {
        return ($this->employee->position == 'ADMIN');
    }

    /**
     * 检测当前权限是否是公寓管理员
     */
    protected function isApartment() {
        return ($this->employee->position == 'APARTMENT');
    }

    /**
     * 财务
     */
    protected function isFinance() {
        return ($this->employee->position == 'FINANCE');
    }

    /**
     * 处理请请求参数中的 apartment_id
     */
    protected function apartmentIdFilter() {
        if ($this->isApartment()) {
            return $this->employee->store_id;
        }

        if ($this->isAdmin()) {
            return $this->input->post('apartment_id');
        }

        return 0;
    }
    public function sendCheckIn($resident_id,$time) {
        $this->load->model('activitymodel');
        $this->load->model('storeactivitymodel');
        $this->load->model('activityprizemodel');
        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');
        $this->load->model('residentmodel');
        $resident = Residentmodel::where('id', $resident_id)->first();
        $store_id = $resident->store_id;
        $activity_id = Activitymodel::where('activity_type','CHECKIN')
            ->where('start_time','<=',strtotime(time()))->where('end_time','>=',strtotime(time()))
            ->where('type','!=','LOWER')
            ->where(function($query) use ($store_id){
                $query->orwherehas('store',function($query) use ($store_id){
                    $query->where('store_id',$store_id);
                });
            })->select(['id','prize_id'])->first();
        if(!$activity_id){
            return '没有查询到该活动';
        }
        $ac_prize = Activityprizemodel::where('id',$activity_id->prize_id)->select(['prize','count','grant'])->first();
        $prize = unserialize($ac_prize->prize);
        $count = unserialize($ac_prize->count);
        $grant = unserialize($ac_prize->grant);
        $coupon = Couponmodel::where('customer_id',$resident->customer_id)->whereIn('coupon_type_id',$prize)->where('activity_id',$activity_id->id)->count();
        if($coupon>=1){
            return '以从该活动领取过同类奖品';
        }
        if($time=='Three_months'){
            $prize_id = $prize['one'];
            $count['one'] = $count['one'] - $grant['one'];
            $grant_number = $grant['one'];
        }elseif($time=='Half_A_year'){
            $prize_id = $prize['two'];
            $count['two'] = $count['two'] - $grant['two'];
            $grant_number = $grant['two'];
        }elseif($time=='A_year'){
            $prize_id = $prize['three'];
            $count['three'] = $count['three'] - $grant['three'];
            $grant_number = $grant['three'];
        }elseif($time == 'under_time'){
            return '入住时间不满足活动需求';
        }
        if(($count['one']<0) || ($count['two']<0) || ($count['three']<0)){
            return '您来晚了，奖品发放完了';
        }
        $count_change = Activityprizemodel::find($activity_id->prize_id);
        $count_change ->count=serialize($count);
        if(!$count_change->save()){
            return '奖品数量更改出错';
        }

        $datetime = time();
        $coupon_type = Coupontypemodel::where('id',$activity_id->prize_id)->select(['deadline'])->first();
        for($i=0;$i<$grant_number;$i++){
            $data[] =[
                'customer_id'    => $resident->customer_id,
                'resident_id'    => $resident->id,
                'activity_id'    => $activity_id->id,
                'coupon_type_id' => $prize_id,
                'store_id'       => $store_id,
                'status'         => 'UNUSED',
                'deadline'       => $coupon_type->deadline,
                'created_at'     => $datetime,
                'updated_at'     => $datetime,
            ];
        }
        Couponmodel::insert($data);
        return '发放成功';
    }


    /*
     * 老带新优惠卷
     * */
    public function sendOldbeltNew($resident_id , $time ,$old_phone) {
        $this->load->model('activitymodel');
        $this->load->model('storeactivitymodel');
        $this->load->model('activityprizemodel');
        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');
        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $resident = Residentmodel::where('id', $resident_id)->first();
        $store_id = $resident->store_id;
        $activity_id = Activitymodel::where('activity_type','OLDBELTNEW')
            ->where('start_time','<=',strtotime(time()))->where('end_time','>=',strtotime(time()))
            ->where('type','!=','LOWER')
            ->where(function($query) use ($store_id){
                $query->orwherehas('store',function($query) use ($store_id){
                    $query->where('store_id',$store_id);
                });
            })->select(['id','prize_id'])->first();
        if(!$activity_id){
            return '没有查询到活动';
        }
        $ac_prize = Activityprizemodel::where('id',$activity_id->prize_id)->select(['prize','count','grant'])->first();
        $prize = unserialize($ac_prize->prize);
        $count = unserialize($ac_prize->count);
        $grant = unserialize($ac_prize->grant);
        $prize_new = [$prize['one'],$prize['two'],$prize['three']];
        $coupon_new = Couponmodel::where('customer_id',$resident->customer_id)->whereIn('coupon_type_id',$prize_new)->where('activity_id',$activity_id->id)->count();
        $old_id = Customermodel::where('phone',$old_phone)->select(['id'])->first();
        if(!$old_id){
            return '没有查询到该老用户';
        }
        $coupon_old = Couponmodel::where('customer_id',$old_id->id)->where('coupon_type_id',$prize['old'])->where('activity_id',$activity_id->id)->count();
        if($coupon_new>=1 || $coupon_old>=1){
            return '其中一人以从该活动领取过同类奖品';
        }
        $count['old'] = $count['old'] - $grant['old'];
        if($time=='Three_months'){
            $prize_id = $prize['one'];
            $count['one'] = $count['one'] - $grant['one'];
            $grant_number = $grant['one'];
        }elseif($time=='Half_A_year'){
            $prize_id = $prize['two'];
            $count['two'] = $count['two'] - $grant['two'];
            $grant_number = $grant['two'];
        }elseif($time=='A_year'){
            $prize_id = $prize['three'];
            $count['three'] = $count['three'] - $grant['three'];
            $grant_number = $grant['three'];
        }elseif($time == 'under_time'){
            return '入住时间不满足活动需求';
        }
        $datetime = time();
        $coupon_type = Coupontypemodel::where('id',$activity_id->prize_id)->select(['deadline'])->first();
        $count_change = Activityprizemodel::find($activity_id->prize_id);
        if($count['old']<0){
            return '您来晚了，奖品发放完了';
        }else{
            $old =[
                'customer_id'    => $old_id->id,
                'activity_id'    => $activity_id->id,
                'coupon_type_id' => $prize['old'],
                'store_id'       => $store_id,
                'status'         => 'UNUSED',
                'deadline'       => $coupon_type->deadline,
                'created_at'     => $datetime,
                'updated_at'     => $datetime,
            ];
            Couponmodel::insert($old);
            $count_change ->count=serialize($count);
            if(!$count_change->save()){
                return '奖品数量更改出错';
            }
        }
        if(($count['one']<0) || ($count['two']<0) || ($count['three']<0) ){
            return '您来晚了，奖品发放完了';
        }
        $count_change ->count=serialize($count);
        if(!$count_change->save()){
            return '奖品数量更改出错';
        }
        for($i=0;$i<$grant_number;$i++){
            $data[] =[
                'customer_id'    => $resident->customer_id,
                'resident_id'       => $resident->id,
                'activity_id'    => $activity_id->id,
                'coupon_type_id' => $prize_id,
                'store_id'       => $store_id,
                'status'         => 'UNUSED',
                'deadline'       => $coupon_type->deadline,
                'created_at'     => $datetime,
                'updated_at'     => $datetime,
            ];
        }
        return '发放成功';
    }

}
