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
        $store_id   = empty($input['store_id'])?:$where['store_id']=$input['store_id'];
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
            });
        $total_page = ceil(($bills->count())/PAGINATE);
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
        $orders=Ordermodel::where('room_id',34)->get();

        $bill       = new Billmodel();

        $bill->id     =    '';
        $count      = $this->billmodel->ordersConfirmedToday()+1;
        $dateString = date('Ymd');
        $bill->sequence_number     =   sprintf("%s%06d", $dateString, $count);

        $bill->store_id            =    $orders[0]->store_id;
        $bill->employee_id         =    $orders[0]->employee_id;
        $bill->resident_id         =    $orders[0]->resident_id;
        $bill->customer_id         =    $orders[0]->customer_id;
        $bill->uxid                =    $orders[0]->uxid;
        $bill->room_id             =    $orders[0]->room_id;

        foreach($orders as $order){
            $bill->money               =    $bill->money+$order->paid;
            if($order->pay_type=='REFUND'){
                $bill->type                =    'OUTPUT';
            }else{
                $bill->type                =    'INPUT';
            }
        }
        $bill->pay_type            =    $orders[0]->pay_type;
        $bill->confirm             =    '';
        $bill->pay_date            =    date('Y-m-d H:i:s',time());
        $bill->data                =    '';
        $bill->confirm_date        =    date('Y-m-d H:i:s',time());

        //如果是微信支付
        $bill->out_trade_no='';
        $bill->store_pay_id='';

        $this->api_res(0,$bill);




    }


}
