<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/8
 * Time:        20:08
 * Describe:    合同后台操作
 */
class Contract extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     * 合同列表
     */
    public function showContract()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $this->load->model('employeemodel');
        $post  = $this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $filed = ['id','contract_id','resident_id','room_id','type','created_at','status','employee_id','store_id'];
        $where = [];
        $store_ids = explode(',',$this->employee->store_ids);
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);};
        if(!empty($post['status'])){$where['status'] = trim($post['status']);};
        if(!empty($post['contract_id'])){$search = trim($post['contract_id']);}else{$search = '';};
        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};

        $count = ceil(Contractmodel::where($where)->where(function($query) use ($search){
                $query->orWhereHas('resident',function($query) use($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('roomunion',function($query) use ($search){
                    $query->where('number','like',"%$search%");
                });
            })->whereIn('store_id',$store_ids)->whereBetween('created_at',[$bt,$et])->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else{
            $order = Contractmodel::with('employee')->with('resident')->with('store')->with('roomunion')
                    ->where($where)->where(function($query) use ($search){
                        $query->orWhereHas('resident',function($query) use($search){
                            $query->where('name','like',"%$search%");
                        })->orWhereHas('roomunion',function($query) use ($search){
                            $query->where('number','like',"%$search%");
                        });
                    })->whereIn('store_id',$store_ids)
                        ->whereBetween('created_at',[$bt,$et])
                        ->take(PAGINATE)->skip($offset)->get($filed)
                        ->map(function ($s){
                            $s['begin_time']= date('Y-m-d',strtotime($s['resident']['begin_time']));
                            $s['end_time']  = date('Y-m-d',strtotime($s['resident']['end_time']));
                            return $s;
                    })->toArray();
        }
        $this->api_res(0,['list'=>$order,'count'=>$count]);
    }

    /**
     *  即将到期合同
     */
    public function limitContract()
    {
        $post = $this->input->post(null,true);
        $limit_time = isset($post['limit_time']);
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $this->load->model('employeemodel');
        $store_ids = $this->employee->id;
        $post  = $this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $filed = ['id','contract_id','resident_id','room_id','type','created_at','status','employee_id','store_id'];
        $resident = Residentmodel::where('end_time','<','limit_time')->get(['resident_id'])->toArray();

        $order = Contractmodel::whereIn($resident)->where('store_id',$store_ids)
            ->with('employee')->with('resident')->with('store')->with('roomunion')
            ->take(PAGINATE)->skip($offset)
            ->orderBy('id','desc')->get($filed)->toArray();
        $this->api_res(0,$order);
    }
}

