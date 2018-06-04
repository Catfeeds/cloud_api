<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/23
 * Time:        10:11
 * Describe:    房间管理
 */
class Room extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('roomunionmodel');
    }

    /**
     * 展示房间列表
     */
    public function listRoom()
    {
        $post       = $this->input->post(null,true);
        $where      = [];
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(!empty($post['status'])){$where['status'] = $post['status'];};
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);}
        $filed      = ['id','layer','status','number','room_type_id'];
        $this->load->model('roomtypemodel');
        $room = Roomunionmodel::with('room_type')->where($where)->get($filed)->groupBy('layer')
                ->map(function ($room){
                    $roominfo = $room->toArray();
                    $roominfo['count_total']    = count($room);;
                    $roominfo['count_rent']     = 0;
                    $roominfo['count_blank']    = 0;
                    $roominfo['count_arrears']  = 0;
                    $roominfo['count_repair']   = 0;
                    for($i = 0;$i<$roominfo['count_total'];$i++){
                        $status = $roominfo[$i]['status'];
                        if ($status == 'RENT'){
                            $roominfo['count_rent']     += 1;
                        }
                        if ($status == 'BLANK'){
                            $roominfo['count_blank']    += 1;
                        }
                        if ($status == 'ARREARS'){
                            $roominfo['count_arrears']  += 1;
                        }
                        if ($status == 'REPAIR'){
                            $roominfo['count_repair']   += 1;
                        }
                    }
                    return [$room,'count'=>[
                            'count_total'   =>$roominfo['count_total'],
                            'count_rent'    =>$roominfo['count_rent'],
                            'count_blank'   =>$roominfo['count_blank'],
                            'count_arrears' =>$roominfo['count_arrears'],
                            'count_repair'  =>$roominfo['count_repair']
                        ]];
                })
            ->toArray();
        $this->api_res(0,['list'=>$room]);
        /* $room = Roomunionmodel::where($where)->get($filed)->groupBy('layer')
                ->map(function($room){
                    $status = $room->groupBy('status')->toArray();
                    $status = array_keys($status);
                    foreach($status as $statuss){
                        if ($statuss == 'ARREARS'){
                            $room['count_arrears'] = $room->groupBy('status')['ARREARS']->count();
                        }
                        if ($statuss == 'BLANK'){
                            $room['count_blank']   = $room->groupBy('status')['BLANK']->count();
                        }
                    }
                    return $room;
                })->toArray();*/
    }

    public function detailsRoom()
    {
        $post  = $this->input->post(null,true);
        $id   = isset($post['id'])?intval($post['id']):null;
        $details = Roomunionmodel::where('id',$id)->get(['people_count','resident_id','arrears']);
        $this->api_res(0,$details);
    }

    public function building()
    {
        $this->load->model('buildingmodel');
        $post = $this->input->post(null,true);
        if($post['store_id']){
            $store_id = intval($post['store_id']);
            $building = Buildingmodel::where('store_id',$store_id)->get(['id','name'])->toArray();
            $this->api_res(0,$building);
        }else{
            $this->api_res(0,[]);
        }
    }

}