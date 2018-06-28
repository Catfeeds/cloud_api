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
        $this->load->model('billmodel');
        $this->load->model('ordermodel');
    }


    /**
     * 流水列表
     */
    public function listBill()
    {
        $input  = $this->input->post(null,true);
        $page   = isset($input['page'])?$input['page']:1;
        $where  = [];
        empty($input['store_id'])?:$where['store_id']=$input['store_id'];
        $start_date = empty($input['start_date'])?'1970-01-01':$input['start_date'];
        $end_date   = empty($input['end_date'])?'2030-12-12':$input['end_date'];
        $search     = empty($input['search'])?'':$input['search'];
        $offset = ($page-1)*PAGINATE;
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');

        $bills  = Billmodel::with(['roomunion','store','resident','employee'])
            ->offset($offset)->limit(PAGINATE)
            ->where($where)
            ->whereBetween('pay_date',[$start_date,$end_date])
            ->orderBy('sequence_number','desc')
            ->where(function($query) use ($search){
                $query->orWhereHas('resident',function($query) use($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('employee',function($query) use ($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('roomunion',function($query) use ($search){
                    $query->where('number','like',"%$search%");
                });
            })->offset($offset)->limit(PAGINATE)->get()->map(function($query){
                $query->pay_date    = $query->created_at->toDateTimeString();
                return $query;
            });

        $billnumber  = Billmodel::with(['roomunion','store','resident','employee'])
            ->where($where)
            ->whereBetween('pay_date',[$start_date,$end_date])
            ->where(function($query) use ($search){
                $query->orWhereHas('resident',function($query) use($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('employee',function($query) use ($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('roomunion',function($query) use ($search){
                    $query->where('number','like',"%$search%");
                });
            })->get()->count();

        $total_page = ceil($billnumber/PAGINATE);
        $this->api_res(0,['bills'=>$bills,'total_page'=>$total_page]);
    }

    /**
     * 查看流水下的账单信息
     */
    public function showBill()
    {
        $input  = $this->input->post(null,true);
        $bill_id    = $input['id'];
        $bill   = Billmodel::find($bill_id);
        if(empty($bill))
        {
            $this->api_res(1007);
            return;
        }
        $sequence=$bill->sequence_number;

        /*
         * ROOM
         * DEIVCE
         * UTILITY
         * REFUND
         * DEPOSIT_R
         * DEPOSIT_O
         * MANAGEMENT
         * OTHER
         * RESERVE
         * CLEAN
         * WATER
         * ELECTRICITY
         * COMPENSATION
         * REPAIR
         * HOT_WATER
         * OVERDUE
         * */

        $data['lists']=Ordermodel::where('sequence_number',$sequence)->get()->toArray();
        //        获取money
        $data['sum']=$bill->money;


        $this->api_res(0,$data);

    }


    public function test(){

        $this->load->model('roomtypemodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $post = $this->input->post(null,true);
        $where      = [];
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(!empty($post['status'])){$where['status'] = trim($post['status']);};
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);}
        if(!empty($post['number'])){$where['number'] = trim($post['number']);}
        $filed      = ['id','layer','status','room_type_id','number','rent_price','resident_id'];
        $roomunion  = new Roomunionmodel();
        if (!empty($post['BLANK_days'])){
            $days = $post['BLANK_days'];
            $where['status'] = "BLANK";
            switch ($days){
                case 1:
                    $time = [date('Y-m-d H:i:s',strtotime('-10 day',time())),date('Y-m-d H:i:s',time())];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                case 2:
                    $time = [date('Y-m-d H:i:s',strtotime('-20 day',time())),date('Y-m-d H:i:s',strtotime('-10 day',time()))];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                case 3;
                    $time = [date('Y-m-d H:i:s',strtotime('-30 day',time())),date('Y-m-d H:i:s',strtotime('-20 day',time()))];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                case 4:
                    $time = [date('Y-m-d H:i:s',0),date('Y-m-d H:i:s',time())];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
                default:
                    $time = [date('Y-m-d H:i:s',0),date('Y-m-d H:i:s',time())];
                    $list = $roomunion->room_details($where,$filed,$time);
                    break;
            }
        }else{
            $time = [date('Y-m-d H:i:s',0),date('Y-m-d H:i:s',time())];
            $list = $roomunion->room_details($where,$filed,$time);
        }
        $this->api_res(0,$list);
    }


}
