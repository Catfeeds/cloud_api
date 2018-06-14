<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/10 0010
 * Time:        16:38
 * Describe:
 */
class Contract extends MY_Controller{

    public function notify(){
        log_message('error','FDD合同签署回调成功');
    }

    /**
     * 未归档的合同包括 住户未生成和住户未签署以及住户签署员工未签署
     */
    public function listUnSign()
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
//                $que->where('status','!=',Contractmodel::STATUS_ARCHIVED);
            })->orDoesntHave('contract')
            ;
        })
            ->where('resident_id','>',0)
            ->where($where)
            ->orderBy('updated_at','ASC')
            ->groupBy('resident_id')
            ->offset($offset)
            ->limit($per_page)
            ->get();
//            ->orderBy('resident.created_at')
            $total_page = ceil(($rooms->count())/PAGINATE);
            $this->api_res(0,['data'=>$rooms,'total_page'=>$total_page]);
    }


}
