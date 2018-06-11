<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use EasyWeChat\Foundation\Application;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/29 0029
 * Time:        10:11
 * Describe:    流水
 */
class Bill extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 流水列表
     */
    public function listBill()
    {
        $input  = $this->input->post(null,true);
        $page   = isset($input['page'])?$input['page']:1;
        $where  = [];
        $store_id   = empty($input['store_id'])?:$where['store_id']=$input['store_id'];
        $start_date = empty($input['start_date'])?'1970-01-01':$input['start_date'];
        $end_date   = empty($input['end_date'])?'2030-12-12':$input['end_date'];
        $search     = empty($input['search'])?'':$input['search'];
        $offset = ($page-1)*PAGINATE;
        $this->load->model('billmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');

        $bills  = Billmodel::with(['roomunion','store','resident','employee'])
            ->offset($offset)->limit(PAGINATE)
            ->where($where)
            ->whereBetween('pay_date',[$start_date,$end_date])
            ->orderBy('id','desc')
            ->where(function($query) use ($search){
                $query->orWhereHas('resident',function($query) use($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('employee',function($query) use ($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('roomunion',function($query) use ($search){
                    $query->where('number','like',"%$search%");
                });
            })->offset($offset)->limit(PAGINATE)->get()->map(function($query){
//                $query->pay_date    = date('Y-m-d',strtotime($query->pay_date));
                return $query;
            });;
        $this->api_res(0,['bills'=>$bills]);
    }




}
