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
        $this->load->model('roomtypemodel');
        $this->load->model('residentmodel');
        $post = $this->input->post(null,true);
        $where      = [];
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(!empty($post['status'])){$where['status'] = trim($post['status']);};
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);}
        if(!empty($post['number'])){$where['number'] = trim($post['number']);}
        $filed      = ['id','layer','status','room_type_id','number','rent_price','resident_id'];
        $roomunion  = new Roomunionmodel();
        if (!empty($post['BLANK_days'])){
            $days = $post['BLANK_days'];
            $where['status'] = "BLANK";
            switch ($days){
                case 1:
                    $time = [date('Y-m-d H:i:s',strtotime('-10 day',time())),date('Y-m-d H:i:s',time())];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                case 2:
                    $time = [date('Y-m-d H:i:s',strtotime('-20 day',time())),date('Y-m-d H:i:s',strtotime('-10 day',time()))];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                case 3;
                    $time = [date('Y-m-d H:i:s',strtotime('-30 day',time())),date('Y-m-d H:i:s',strtotime('-20 day',time()))];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                case 4:
                    $time = [date('Y-m-d H:i:s',0),date('Y-m-d H:i:s',time())];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                default:
                    $time = [date('Y-m-d H:i:s',0),date('Y-m-d H:i:s',time())];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
            }
        }else{
            $time = [date('Y-m-d H:i:s',0),date('Y-m-d H:i:s',time())];
            $list = $roomunion->room_details($where,$filed,$time);
        }
        $this->api_res(0,$list);
    }
}