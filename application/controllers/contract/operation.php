<?php
defined('BASEPATH') OR exit('No direct script access allowed');
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

        $filed  = ['id','serial_number','resident_id','sign_type','store_id','room_id','created_at','status','employee_id'];
        if ($where) {
            $operation = Contractmodel::with('resident')->with('employee')->with('store')->with('roomunion')->
            where($where)->whereBetween('created_at', [$btime, $etime])->take(PAGINATE)->
            skip($offset)->orderBy('id', 'desc')->get($filed);
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
        $serial = $post['serial_number'];
        $filed  = ['id','serial_number','resident_id','room_id','status'];
        $operation = Contractmodel::where('serial_number',$serial)->with('room')->with('residents')->get($filed);

        $aa = ['resident_id'];$bb = ['discount_id'];$cc = ['activity_id'];$dd = ['name'];
        $resident_id = Contractmodel::where('serial_number',$serial)->get($aa);
        $discount_id = Residentmodel::where('id',$resident_id)->get($bb);
        $activity_id = Couponmodel::  where('id',$discount_id)->get($cc);
        $name        = Activitymodel::where('id',$activity_id)->get($dd);

        //var_dump($discount_id);
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
        $filed  = ['id','serial_number','resident_id','store_id','room_id','employee_id','status'];
        if ($where){
            $operation = Contractmodel::with('bookresident')->with('employee')->with('store')->with('roomunion')->
            where($where)->whereBetween('created_at', [$btime, $etime])->take(PAGINATE)->skip($offset)->
            orderBy('id', 'desc')->get($filed);
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
        $serial = $post['serial_number'];
        $filed  = ['id','serial_number','resident_id','store_id','room_id'];

        $operation = Contractmodel::where('serial_number',$serial)->with('roomunion')->with('store')->with('booking')->get($filed);
        $this->api_res(0,['info'=>$operation]);
    }


}
