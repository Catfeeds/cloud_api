<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/9 0009
 * Time:        20:42
 * Describe:
 */

class Checkout extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('checkoutmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');
        $this->load->model('Roomunionmodel');
        $this->load->model('Ordermodel');
    }

    //退房账单列表
    public function list(){
        $input  = $this->input->post(null,true);
        $page   = isset($input['page'])?$input['page']:1;
        $where  = [];
        empty($input['store_id'])?$where['store_id']='':$where['store_id']=$input['store_id'];
        if(isset($input['status'])){
            $status = [$input['status']];
        }else{
            //$status = $this->allStatus();
            $status = array_diff($this->allStatus(),[Checkoutmodel::STATUS_COMPLETED]);
//            $status = array_diff($this->allStatus(),[Checkoutmodel::STATUS_COMPLETED,Checkoutmodel::STATUS_COMPLETED]);
        }
        $offset = ($page-1)*PAGINATE;
        $list   = Checkoutmodel::with(['roomunion','store','resident'])->offset($offset)->limit(PAGINATE)->get();
        if(isset($input['room_number'])){
            $list   = $list->where('roomunion.number',$input['room_number']);
        }
        $allnumber=Checkoutmodel::with(['roomunion','store','resident'])->count();

        $total_page = ceil($allnumber/PAGINATE);
        $this->api_res(0,['checkouts'=>$list,'total_page'=>$total_page]);
    }

    //显示一笔退款交易
    public function show(){
        $input  = $this->input->post(null,true);
        empty($input['id'])?$id=21:$id=$input['id'];
        $checkout   = Checkoutmodel::find($id);
        if(empty($checkout))
        {
            $this->api_res(1007);
            return;
        }

        $data['checkout']=$checkout->toArray();
        $data['orders']=Ordermodel::where('resident_id',$checkout->resident_id)->where('sequence_number','')->get()->toArray();

        $this->api_res(0,$data);

    }


    //退房账单更新
    public function update(){


    }

    //确定正常退房
    public function sure(){


    }

    //押金转收入退房

    public function Breach(){


    }


    /**
     *
     * 创建生成流水账单
     * 根据流水账单来记录用户的每次支付记录
     *
     */

    private function createBill($orders)
    {
        $this->load->model('billmodel');
        $bill       = new Billmodel();
        $bill->id     =    '';
        $count      = $this->billmodel->ordersConfirmedToday()+1;
        $dateString = date('Ymd');
        $this->load->model('residentmodel');


        $bill->sequence_number     =   sprintf("%s%06d", $dateString, $count);

        $bill->store_id            =    $orders[0]->store_id;
        $bill->employee_id         =    $orders[0]->employee_id;
        $bill->resident_id         =    $orders[0]->resident_id;
        $bill->customer_id         =    $orders[0]->customer_id;
        $bill->uxid                =    $orders[0]->uxid;
        $bill->room_id             =    $orders[0]->room_id;
        $orderIds=array();

        $change_resident = false;
        foreach($orders as $order){

            $orderIds[]=$order->id;
            $bill->money               =    $bill->money+$order->paid;
//            if($order->pay_type=='REFUND'){
//                $bill->type                =    'OUTPUT';
//            }else{
//                $bill->type                =    'INPUT';
//            }
            if($order->pay_type=='ROOM'){
                $change_resident=true;
            }
        }
        if($change_resident){
            $Resident=Residentmodel::find($orders[0]->resident_id);
            $Resident_time=substr($Resident['begin_time'],0,7);
            if($Resident_time==substr($orders[0]->pay_type,0,7)){
                Residentmodel::where('id', $orders[0]->resident_id)->update(['status' => 'NORMAL']);
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

        $res=$bill->save();
        if(isset($res)){
            Ordermodel::whereIn('id', $orderIds)->update(['sequence_number' => $bill->sequence_number]);
        }
        return $res;
    }



    private function allStatus()
    {

        return array(
            Checkoutmodel::STATUS_APPLIED,
            Checkoutmodel::STATUS_UNPAID,
            Checkoutmodel::STATUS_PENDING,
            Checkoutmodel::STATUS_BY_MANAGER,
            Checkoutmodel::STATUS_MANAGER_APPROVED,
            Checkoutmodel::STATUS_PRINCIPAL_APPROVED,
            Checkoutmodel::STATUS_COMPLETED,
        );
    }





}

