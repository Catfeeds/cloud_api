<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use OSS\OssClient;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/27 0027
 * Time:        10:31
 * Describe:    公共接口
 */

class Common extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    /**
     * 上传图片接口
     * 传入字段名为image
     */
    public function imageUpload() {
        $config = [
            'allowed_types' => 'gif|jpg|png|jpeg',
            'max_size'      => 4 * 1024,
        ];
        parse_str($_SERVER["QUERY_STRING"], $query);
        if (!empty($query) && !empty($query["watermark"])) {
            switch ($query["watermark"]) {
                case 'idcard':
                    $config['watermark'] = "仅供合同签署使用\n金地火花草莓社区";
                    break;
            }
        }
        $this->load->library('alioss', $config);
        if (!$this->alioss->do_upload('image')) {
            $this->api_res(1004, array('error' => $this->alioss->display_errors('', '')));
        } else {
            $oss_path = $this->alioss->data()['oss_path'];
            log_message('debug','返回URL为'.$oss_path);
            $this->api_res(0, ['image_url' => config_item('cdn_path') . $oss_path]);
        }
    }

    /*
     * 上传文件
     */
    public function fileUpload() {
        $config = [
            'allowed_types' => 'pdf|xls|xlsx',
            'max_size'      => 4 * 1024,
        ];
        $this->load->library('alioss', $config);
        if (!$this->alioss->do_upload('file')) {
            $this->api_res(1004, array('error' => $this->alioss->display_errors('', '')));
        } else {
            $oss_path = $this->alioss->data()['oss_path'];
            $this->api_res(0, ['file_url' => config_item('cdn_path') . $oss_path]);
        }
    }

    /**
     * 获取全国的省
     */
    public function province() {
        $this->load->model('provincemodel');
        // $province = Provincemodel::all();
        $province = Provincemodel::get(['province_id', 'province']);
        $this->api_res(0, ['province' => $province]);
    }

    /**
     *获取省对应的市
     */
    public function city() {
        $this->load->model('citymodel');
        $province_id = $this->input->post('province_id', true);
        if (isset($province_id)) {
            $city = Citymodel::where('province_id', $province_id)->get(['city_id', 'city']);
        } else {
            //$city  = Citiesmodel::get(['cityid', 'city']);
            $city = [];
        }
        $this->api_res(0, ['city' => $city]);
    }

    /**
     * 获取市对应的区县
     */
    public function district() {
        $this->load->model('districtmodel');

        $post = $this->input->post(null, true);
        if (isset($post['city_name'])) {
            $this->load->model('citymodel');
            $city_id = Citymodel::where('city', strip_tags($post['city_name']))->first()->city_id;
        } else {
            $city_id = isset($post['city_id']) ? intval($post['city_id']) : null;
        }
        if (isset($city_id)) {
            $district = Districtmodel::where('city_id', $city_id)->get(['district_id', 'district']);
        } else {
            $district = [];
        }
        $this->api_res(0, ['district' => $district]);
    }

    /**
     * base64位图片上传到alioss
     */
    public function baseImageUpload(){
        $base_64    = $this->input->post('base');
        $base_arr   = explode(',',$base_64);
        $base_image = base64_decode(end($base_arr));
        $content    = $base_image;
        $this->config->load('alioss', TRUE);
        $accessKeyId = $this->config->item('alioss')['accessKeyId'];
        $accessKeySecret = $this->config->item('alioss')['accessKeySecret'];
        $endpoint = $this->config->item('alioss')['endpoint'];
        $bucket = $this->config->item('alioss')['bucket'];
        $ossClient = new OssClient($accessKeyId, $accessKeySecret,$endpoint, false);
        $object = date('Y-m-d',time()).'/'.uniqid().'.png';
        $options = array();
        $ossClient->putObject($bucket, $object, $content, $options);
        $this->api_res(0,config_item('cdn_path').'/'.$object);
    }
}
