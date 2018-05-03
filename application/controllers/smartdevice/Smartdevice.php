<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/3
 * Time:        10:44
 * Describe:    智能设备管理
 */

class Smartdevice extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('smartdevicemodel');
    }

    public function index()
    {
        $post   = $this->input->post(NULL,true);
        $where  = [];
        $page                   = empty($post['page'])?NULL:trim($post['page']);
        $room_number            = empty($post['room_number'])?NULL:trim($post['room_number']);
        $where['city']          = empty($post['city'])?NULL:trim($post['city']);
        $where['store']         = empty($post['store_id'])?NULL:trim($post['store_id']);
        $where['device_type']   = empty($post['device_type'])?NULL:trim($post['device_type']);

        $filed = ['id','room_id','type','serial_number','supplier'];

        $device = Smartdevicemodel::all();
        $this->api_res(0,$device);

    }



}