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
        $post = $this->input->post(null,true);
        $where      = [];
        if(isset($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(isset($post['status'])){$where['status'] = trim($post['status']);};
        if(isset($post['store_id'])){$where['store_id'] = intval($post['store_id']);}
        if(isset($post['number'])){$where['number'] = trim($post['number']);}
        if (isset($post['BLANK_days'])){
            $days = $post['BLANK_days'];
        }

        $filed      = ['id','layer','status','room_type_id','number','rent_price','resident_id'];

        $details    = Roomunionmodel::with('room_type')->where($where)->get($filed)->groupBy('layer')->toArray();
        $total_count= Roomunionmodel::where($where)->get($filed)->count();
        $blank_count= Roomunionmodel::where($where)->where('status','BLANK')->get($filed)->count();
        $reserve_count= Roomunionmodel::where($where)->where('status','RESERVE')->get($filed)->count();
        $rent_count = Roomunionmodel::where($where)->where('status','RENT')->get($filed)->count();
        $arrears_count= Roomunionmodel::where($where)->where('status','ARREARS')->get($filed)->count();


        $this->api_res(0,[ 'list'=>$details,
                                'total_count'=>$total_count,
                                'blank_count'=>$blank_count,
                                'reserve_count'=>$reserve_count,
                                'rent_count'=>$rent_count,
                                'arrears_count'=>$arrears_count]);

    }
}