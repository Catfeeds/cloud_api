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
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);};
        if(!empty($post['status'])){$where['status'] = trim($post['status']);};
        if(!empty($post['contract_id'])){$where['contract_id'] = trim($post['contract_id']);};
        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};

        $count = ceil(Contractmodel::where($where)->whereBetween('created_at',[$bt,$et])->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else{
            $order = Contractmodel::where($where)
                ->with('employee')->with('resident')->with('store')->with('roomunion')
                ->whereBetween('created_at',[$bt,$et])
                ->take(PAGINATE)->skip($offset)
                ->orderBy('id','desc')->get($filed)->toArray();
        }
        $this->api_res(0,['list'=>$order,'count'=>$count]);
    }

    /**
     *  合同详细信息
     */

}

