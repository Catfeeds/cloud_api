<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/26
 * Time:        15:17
 * Describe:    服务管理-预约订单
 */
class Reserveorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('rserveorder');
    }

    /**
     * 返回预约订单列表
     */
    public function index()
    {
        
    }
}