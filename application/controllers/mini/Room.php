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
        if(isset($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(isset($post['status'])){$where['status'] = $post['status'];};
        if(isset($post['store_id'])){
            $where['store_id'] = intval($post['store_id']);
        }else{
            $this->api_res(0,[]);
            return;
        }
        $filed      = ['id','layer','status','number','room_type_id'];
        $this->load->model('roomtypemodel');
        $room = Roomunionmodel::with('room_type')->where($where)->get($filed)->groupBy('layer')
                ->map(function ($room){
                    $room = $room->toArray();
                    $room['count_total']    = count($room);;
                    $room['count_rent']     = 0;
                    $room['count_blank']    = 0;
                    $room['count_arrears']  = 0;
                    $room['count_repair']   = 0;
                    for($i = 0;$i<$room['count_total'];$i++){
                        $status = $room[$i]['status'];
                        if ($status == 'RENT'){
                            $room['count_rent']     += 1;
                        }
                        if ($status == 'BLANK'){
                            $room['count_blank']    += 1;
                        }
                        if ($status == 'ARREARS'){
                            $room['count_arrears']  += 1;
                        }
                        if ($status == 'REPAIR'){
                            $room['count_repair']   += 1;
                        }
                    }
                    return $room;
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


}