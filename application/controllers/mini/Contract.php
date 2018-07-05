<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/10 0010
 * Time:        16:38
 * Describe:
 */
class Contract extends MY_Controller{

    public function notify()
    {
        log_message('error','FDD合同签署回调成功');
    }

    /**
     * 未归档的合同包括 住户未生成和住户未签署以及住户签署员工未签署
     */
    public function listUnSign()
    {
        $input  = $this->input->post(null,true);
        $page   = (int)(isset($input['page'])?$input['page']:1);
        $per_page   = (int)(isset($input['per_page'])?$input['per_page']:PAGINATE);
        $offset = ($page-1)*$per_page;
        $where['store_id']=$this->employee->store_id;
//        $where['store_id']=1;
//        isset($input['room_number'])?$where['number']=$input['room_number']:null;

        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('contractmodel');

        if(isset($input['room_number'])){
            $room_ids   = Roomunionmodel::where('number',$input['room_number'])
                ->where('store_id',$this->employee->store_id)
                ->get()
                ->map(function($a){
                    return $a->id;
                });
        }else{
            $room_ids   = Roomunionmodel::where('store_id',$this->employee->store_id)
                ->get()
                ->map(function($a){
                    return $a->id;
                });
        }
        if(empty($room_ids)){
            $room_ids   = [];
        }

        $rooms  = Residentmodel::with('roomunion')
            ->where($where)
            ->whereIn('room_id',$room_ids)
            ->whereIn('status',['NOT_PAY','PRE_RESERVE'])
            ->orderBy('updated_at','ASC')
            ->offset($offset)
            ->limit($per_page)
            ->get()
            ->map(function($room){
            $room2   = $room->toArray();
            $room2['begin_time'] =date('Y-m-d',strtotime($room->begin_time->toDateTimeString()));
            return $room2;
            });
        $total_page = ceil(($rooms->count())/PAGINATE);

        $data['data']= $rooms->toArray();
        $data['per_page']   = $per_page;
        $data['current_page']   = $page;
        $data['total']  = $rooms->count();
        $data['total_page']=$total_page;

         $this->api_res(0,$data);
    }


  /*  public function listUnSign()
    {
        $input  = $this->input->post(null,true);
        $page   = (int)(isset($input['page'])?$input['page']:1);
        $per_page   = isset($input['per_page'])?$input['per_page']:PAGINATE;
        $offset = ($page-1)*PAGINATE;
        $where=[];
//        $where['store_id']=$this->employee->store_id;
        isset($input['room_number'])?$where['number']=$input['room_number']:null;

        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('contractmodel');

        $rooms  = Roomunionmodel::with(['resident'=>function($query){
            $query->with(['contract']);
        }])
            ->whereHas('resident',function($query){
                $query->whereHas('contract',function ($que){
                    $que->where('status','!=',Contractmodel::STATUS_ARCHIVED);
                })->orDoesntHave('contract')
                ;
            })
            ->where('resident_id','>',0)
            ->where($where)
            ->orderBy('updated_at','ASC')
            ->offset($offset)
            ->limit($per_page)
            ->get();
//            ->orderBy('resident.created_at')

        $this->api_res(0,['data'=>$rooms]);
    }*/


}
