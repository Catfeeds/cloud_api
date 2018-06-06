<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;

/**
 * User: wws
 * Date: 2018-05-24
 * Time: 09:23
 *   运营合同
 */

class Operation extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     *  入住合同管理
     */
    public function operatList()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('employeemodel');
        $this->load->model('residentmodel');

        $post           = $this->input->post(NULL,true);
        $page           = empty($post['page'])?1:intval($post['page']);
        $offset         = PAGINATE*($page-1);
        $count  = ceil(Contractmodel::count()/PAGINATE);
        $where          = [];
        if(!empty($post['store_id'])){$where['id']  = $post['store_id'];}

        if(!empty($post['begin_time'])){$btime=$post['begin_time'];}else{$btime = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$etime=$post['end_time'];}else{$etime = date('Y-m-d H:i:s',time());};
        $filed  = ['id','contract_id','resident_id','sign_type','store_id','room_id','created_at','status','employee_id'];
        if ($where) {
            $operation = Contractmodel::with('resident')->with('employee')->with('store')->with('roomunion')->
            where($where)->whereBetween('created_at', [$btime, $etime])->take(PAGINATE)->
            skip($offset)->orderBy('id', 'desc')->get($filed);
        }elseif ($btime||$etime){
            $operation = Contractmodel::with('resident')->with('employee')->with('store')->with('roomunion')->
            whereBetween('created_at', [$btime, $etime])->take(PAGINATE)->
            skip($offset)->orderBy('id', 'desc')->get($filed);
        }else{
            $operation = Contractmodel::with('resident')->with('employee')->with('store')->with('roomunion')
                ->take(PAGINATE)->skip($offset)->orderBy('id', 'desc')->get($filed);
        }

        $this->api_res(0,['operationlist'=>$operation,'count'=>$count]);
    }

    /**
     *查看入住合同
     */
    public function operationFind()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('couponmodel');
        $this->load->model('activitymodel');
        $post   = $this->input->post(NULL,true);
        $serial = $post['id'];
        $filed  = ['id','contract_id','resident_id','room_id','status'];
        $operation = Contractmodel::where('id',$serial)->with('room')->with('residents')->get($filed);
        $aa = ['resident_id'];$bb = ['discount_id'];$cc = ['activity_id'];$dd = ['name'];
        $resident_id = Contractmodel::where('id',$serial)->get($aa)->toArray();
        $discount_id = Residentmodel::where('id',$resident_id)->get($bb)->toArray();
        $activity_id = Couponmodel::  where('id',$discount_id)->get($cc)->toArray();
        $name        = Activitymodel::where('id',$activity_id)->get($dd)->toArray();
        $this->api_res(0,['info'=>$operation,'activity'=>$name]);
    }

    /**
     * 预定合同管理
     */
    public function booking()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('residentmodel');
        $post           = $this->input->post(NULL,true);
        $page           = empty($post['page'])?1:intval($post['page']);
        $offset         = PAGINATE*($page-1);
        $count  = ceil(Contractmodel::count()/PAGINATE);
        $where          = [];
        if(!empty($post['store_id'])){$where['id']  = $post['store_id'];}
        if(!empty($post['begin_time'])){$btime=$post['begin_time'];}else{$btime = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$etime=$post['end_time'];}else{$etime = date('Y-m-d H:i:s',time());};
        $filed  = ['id','contract_id','resident_id','store_id','room_id','employee_id'];
        if ($where){
            $operation = Contractmodel::with('bookresident')->with('employee')->with('store')->with('roomunion')->
            where($where)->whereBetween('created_at', [$btime, $etime])->take(PAGINATE)->skip($offset)->
            orderBy('id', 'desc')->get($filed);
        } else{
            $operation = Contractmodel::with('bookresident')->with('employee')->with('store')->with('roomunion')->
           take(PAGINATE)->skip($offset)->orderBy('id', 'desc')->get($filed);
        }
        $this->api_res(0,['bookinglist'=>$operation,'count'=>$count]);
    }

    /**
     * 查看预订合同
     */
    public function book()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('employeemodel');
        $this->load->model('residentmodel');
        $post   = $this->input->post(NULL,true);
        $serial = $post['id'];
        $filed  = ['id','contract_id','resident_id','store_id','room_id'];

        $operation = Contractmodel::where('id',$serial)->with('roomunion')->with('store')->with('booking')->get($filed);
        $this->api_res(0,['info'=>$operation]);
    }

    /**
     * 查看PDF合同
     */
    public function pdfLook()
    {
        $post  =$this->input->post(NULL,true);
        $id = $post['id'];
        $filed  =['view_url'];
        $pdf  = Contractmodel::where('id',$id)->get($filed);
//        foreach ($pdf as $value){
//            $pdf['view_url'] = $this->fullAliossUrl($value['view_url']);
//        }
        $this->api_res(0,['seepdf'=>$pdf]);
    }

    /**
     *上传合同
     */
    public function loadcontract()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');
        $this->load->model('residentmodel');
//        $this->load->model('contracttemplatemodel');
//        //生成该合同的编号
        $post   = $this->input->post(NULL,true);
//        $resident_id = $post['resident_id'];
//        $resident = Contractmodel::find($resident_id);
        $resident   = Residentmodel::find(3);
        $room = $resident->roomunion;
        $apartment = $resident->roomunion->store;
//      统计今年门店的合同的数量
        $contractCount = $apartment->contracts()
            ->where('created_at', '>=', Carbon::parse($resident->begin_time)->startOfYear())
            ->count();
        //var_dump($contractCount);die();
        //生成合同编号 //门店里的合同前缀 - 用户表里的开始时间的年份 - 000格式合同数量自增 - 用户名 - 房间表的房间号
        $contractNumber = $apartment->contract_number_prefix . '-' . Carbon::parse($resident->begin_time)->year . '-' .
            sprintf("%03d", ++$contractCount) . '-' . $resident->name . '-' . $room->number;
       // var_dump($contractNumber);die();
        $this->load->model('contracttemplatemodel');
//        $post   = $this->input->post(NULL,true);
        $config   = [
            'allowed_types'   => 'pdf',
            'upload_path'     => 'temp',
        ];
        $this->load->library('upload',$config);
        if (!$this->upload->do_upload('file'))
        {
            var_dump($this->upload->display_errors());exit;
        }
        $data   = $this->upload->data('full_path');
      //  var_dump($data);die();
        if(Contractmodel::where()->exists()){
            $this->api_res(1008);
            return;
        }
        $name1 = $this->input->$post['number'];
        $name = Storemodel::where('number',$name1)->get();

        $template   = new Contractmodel();
//        $template-> = ;
//        $template-> = ;
        //$template-> = $this->fullAliossUrl($data);
        $template->url = $data;
        if($template->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }


}
