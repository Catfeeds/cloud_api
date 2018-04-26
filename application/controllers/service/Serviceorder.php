<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/23
 * Time:        10:37
 * Describe:    [boss端]服务管理--服务订单
 */
class Serviceorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('serviceordermodel');
    }

    /**
     * 返回服务订单列表
     */
    public function index()
    {
        $post           = $this->input->post(NULL,true);
        $city_id        = trim($post['city_id']);
        $apartment_id   = trim($post['apartment_id']);
        $bt             = trim($post['bt']);
        $et             = trim($post['et']);
    }
    

}