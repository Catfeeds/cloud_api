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
        $this->load->model('employeemodel');
        $this->load->model('roomunionmodel');
    }

    /**
     * 展示房间列表
     */
    public function listRoom()
    {
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $post       = $this->input->post(null,true);
        $where      = [];
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(!empty($post['status'])){$status = $post['status'];}else{$status = null;};
        $where['store_id']  = $this->employee->store_id;

        $filed      = ['id','layer','status','number','room_type_id'];
        $this->load->model('roomtypemodel');
        if ($status == 'ARREARS'){
            $room = Roomunionmodel::with('room_type')->with('order')//->with('due')
                ->where($where)->whereHas('order')->orderBy('number','ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room){
                    $roominfo = $room->toArray();
                    $roominfo['count_total']    = count($room);
                    $roominfo['count_rent']     = 0;
                    $roominfo['count_blank']    = 0;
                    $roominfo['count_arrears']  = 0;
                    $roominfo['count_repair']   = 0;
                    $roominfo['count_due']      = 0;
                    for($i = 0;$i<$roominfo['count_total'];$i++){
                        $status = $roominfo[$i]['status'];
                        /*if ($status == 'RENT'){
                            $roominfo['count_rent']     += 1;
                        }
                        if ($status == 'BLANK'){
                            $roominfo['count_blank']    += 1;
                        }*/
                        if (!empty($roominfo[$i]['order'])){
                            $roominfo['count_arrears']  += 1;
                        }
                        /*if (!empty($roominfo[$i]['due'])){
                            $roominfo['count_due']  += 1;
                        }
                        if ($status == 'REPAIR'){
                            $roominfo['count_repair']   += 1;
                        }*/
                    }
                    return [$room,'count'=>[
                        'count_total'   =>$roominfo['count_total'],
                        'count_rent'    =>$roominfo['count_rent'],
                        'count_blank'   =>$roominfo['count_blank'],
                        'count_arrears' =>$roominfo['count_arrears'],
                        'count_repair'  =>$roominfo['count_repair'],
                        'count_due'     =>$roominfo['count_due'],
                    ]];
                })
                ->toArray();
        }
        elseif ($status == 'DUE'){
            $room = Roomunionmodel::with('room_type')->with('due')//->with('order')
                ->where($where)->whereHas('due')->orderBy('number','ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room){
                    $roominfo = $room->toArray();
                    $roominfo['count_total']    = count($room);
                    $roominfo['count_rent']     = 0;
                    $roominfo['count_blank']    = 0;
                    $roominfo['count_arrears']  = 0;
                    $roominfo['count_repair']   = 0;
                    $roominfo['count_due']      = 0;
                    for($i = 0;$i<$roominfo['count_total'];$i++){
                        $status = $roominfo[$i]['status'];
                        /*if ($status == 'RENT'){
                            $roominfo['count_rent']     += 1;
                        }
                        if ($status == 'BLANK'){
                            $roominfo['count_blank']    += 1;
                        }
                        if (!empty($roominfo[$i]['order'])){
                            $roominfo['count_arrears']  += 1;
                        }*/
                        if (!empty($roominfo[$i]['due'])){
                            $roominfo['count_due']  += 1;
                        }
                        /*if ($status == 'REPAIR'){
                            $roominfo['count_repair']   += 1;
                        }*/
                    }
                    return [$room,'count'=>[
                        'count_total'   =>$roominfo['count_total'],
                        'count_rent'    =>$roominfo['count_rent'],
                        'count_blank'   =>$roominfo['count_blank'],
                        'count_arrears' =>$roominfo['count_arrears'],
                        'count_repair'  =>$roominfo['count_repair'],
                        'count_due'     =>$roominfo['count_due']
                    ]];
                })
                ->toArray();
        }elseif($status == null){
            $room = Roomunionmodel::with('room_type')->with('due')->with('order')
                ->where($where)->orderBy('number','ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room){
                    $roominfo = $room->toArray();
                    $roominfo['count_total']    = count($room);
                    $roominfo['count_rent']     = 0;
                    $roominfo['count_blank']    = 0;
                    $roominfo['count_arrears']  = 0;
                    $roominfo['count_repair']   = 0;
                    $roominfo['count_due']      = 0;
                    for($i = 0;$i<$roominfo['count_total'];$i++){
                        $status = $roominfo[$i]['status'];
                        if ($status == 'RENT'){
                            $roominfo['count_rent']     += 1;
                        }
                        if ($status == 'BLANK'){
                            $roominfo['count_blank']    += 1;
                        }
                        if (!empty($roominfo[$i]['order'])){
                            $roominfo['count_arrears']  += 1;
                        }
                        if (!empty($roominfo[$i]['due'])){
                            $roominfo['count_due']  += 1;
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
                        'count_repair'  =>$roominfo['count_repair'],
                        'count_due'     =>$roominfo['count_due']
                    ]];
                })
                ->toArray();
        }else {
            $room = Roomunionmodel::with('room_type')/*->with('due')->with('order')*/
                ->where($where)->where('status',$status)->orderBy('number','ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room){
                    $roominfo = $room->toArray();
                    $roominfo['count_total']    = count($room);
                    $roominfo['count_rent']     = 0;
                    $roominfo['count_blank']    = 0;
                    $roominfo['count_arrears']  = 0;
                    $roominfo['count_repair']   = 0;
                    $roominfo['count_due']      = 0;
                    for($i = 0;$i<$roominfo['count_total'];$i++){
                        $status = $roominfo[$i]['status'];
                        if ($status == 'RENT'){
                            $roominfo['count_rent']     += 1;
                        }
                        if ($status == 'BLANK'){
                            $roominfo['count_blank']    += 1;
                        }
                        if (!empty($roominfo[$i]['order'])){
                            $roominfo['count_arrears']  += 1;
                        }
                        if (!empty($roominfo[$i]['due'])){
                            $roominfo['count_due']  += 1;
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
                        'count_repair'  =>$roominfo['count_repair'],
                        'count_due'     =>$roominfo['count_due']
                    ]];
                })
                ->toArray();
        }
        $this->api_res(0,['list'=>$room]);
    }

    /**
     *  门店下的房间状态统计
     */
    public function countRoom()
    {
//        $post = $this->input->post(null,true);
//        if ($post['store_id']){
            $store_id = $this->employee->store_id;
            $room = Roomunionmodel::where('store_id',$store_id)->get(['id','status'])->toArray();
            $count = [];
            $count['count_total']   = count($room);
            $count['count_blank']   = 0;
            $count['count_rent']    = 0;
            $count['count_arrears'] = 0;
            for($i = 0;$i<$count['count_total'];$i++){
                $status = $room[$i]['status'];
                if ($status == 'RENT'){
                    $count['count_rent']     += 1;
                }
                if ($status == 'BLANK'){
                    $count['count_blank']    += 1;
                }
                if ($status == 'ARREARS'){
                    $count['count_arrears']  += 1;
                }
            }
            $this->api_res(0,$count);

//        }else{
//            $this->api_res(1002);
//        }
    }

    /**
     * 房间详情
     */
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