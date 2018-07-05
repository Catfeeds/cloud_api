<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/4 0004
 * Time:        14:20
 * Describe:    续租
 */
class Renew extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    //通过房间号获取住户信息
    public function getResidentByRoom()
    {
        $input  = $this->input->post(null,true);
        $room_number    = $input['room_number'];
//        $status   = $input['status'];
        $store_id   = $this->employee->store_id;
//        $store_id   = 1;
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $where  = [
            'store_id'=>$store_id,
            'number'=>$room_number,
        ];

        $room   = Roomunionmodel::with('resident')
            ->where($where)
            ->first();
        if(empty($room))
        {
            $this->api_res(1007);
            return;
        }
        if(empty($room->resident) || $room->status!=Roomunionmodel::STATE_RENT){
            $this->api_res(10035);
            return;
        }

        $this->api_res(0,[$room]);

    }

    //续租列表
    public function listRenew()
    {

    }

}
