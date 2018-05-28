<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/25
 * Time:        18:37
 * Describe:    销控管理
 */

class Sellcontrol extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('roomunionmodel');
    }

    public function details()
    {
        $post = $this->input->post(null,true);
        $where      = [];
        if(isset($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(isset($post['status'])){$where['status'] = trim($post['status']);};
        if(isset($post['store_id'])){$where['store_id'] = intval($post['store_id']);}
        if(isset($post['number'])){$where['number'] = trim($post['number']);}

        $filed      = ['id','layer','status','room_type_id','number','rent_price','resident_id'];

        $details    = Roomunionmodel::where($where)->get($filed)->gtoupBy->toArray();
        $this->api_res(0,$details);
    }
}