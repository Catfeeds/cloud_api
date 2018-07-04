<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/9
 * Time:        15:06
 * Describe:    住户
 */
class Resident extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('residentmodel');
    }

    /**
     * 展示住户列表
     */
    public function showResident()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('customermodel');
        $post = $this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $filed = ['id','name','customer_id','phone','room_id','card_number','created_at','status'];
        $where = [];
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);};
        if(!empty($post['name'])){$where['name'] = trim($post['name']);};

        $count = $count = ceil(Residentmodel::where($where)->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else {
            $resident = Residentmodel::with('room')->with('customer_s')->where($where)->orderBy('created_at','DESC')->take(PAGINATE)
                    ->skip($offset)->get($filed)->map(function ($s){
                    $s->room->store_name = (Storemodel::where('id',$s->room->store_id)->get(['name']))[0]['name'];
                    $s->createdat = date('Y-m-d',strtotime($s->created_at->toDateTimeString()));
                    return $s;
                })->toArray();
            $this->api_res(0, ['list' => $resident, 'count' => $count]);
        }
    }

    /**
     * 住户基本信息
     */
    public function residentInfo()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('customermodel');
        $post = $this->input->post(null,true);
        if (isset($post['id'])){
            $resident_id = intval($post['id']);
            $filed = ['id','name','customer_id','phone','card_type','card_number','card_one','card_two','card_three','alternative','alter_phone'];
            $resident = Residentmodel::with('customer_s')
                ->where('id',$resident_id)->get($filed)
                ->map(function ($s){
                    $s->card_one = $this->fullAliossUrl($s->card_one);
                    //var_dump($s->card_one);
                    $s->card_two = $this->fullAliossUrl($s->card_two);
                    $s->card_three = $this->fullAliossUrl($s->card_three);
                    return $s;
                })
                ->toArray();
            $this->api_res(0, $resident);
        }else{
            $this->api_res(1002);
        }
    }

    /**
     * 修改住户信息
     */
    public function updateResident()
    {
        $this->load->model('customermodel');
        $post = $this->input->post(null,true);
        $id = intval($post['id']);
        $customer_id = intval($post['customer_id']);
        if(!$this->validation())
        {
            $fieldarr   = ['name','gender','phone','card_type','card_number','card_one','card_two','card_three','alternative','alter_phone'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $resident   = Residentmodel::findOrFail($id);
        $customer   = Customermodel::findOrFail($customer_id);
        $resident->fill($post);

        $card_one  = $this->splitAliossUrl($post['card_one']);
        $resident->card_one=$card_one;

        $card_two  = $this->splitAliossUrl($post['card_two']);

        $resident->card_two=$card_two;

        $card_three  = $this->splitAliossUrl($post['card_three']);
        $resident->card_three=$card_three;

        $customer->gender = $post['gender'];
        $customer->save();
        if($resident->save())
        {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 住户合同信息
     */
    public function contract()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('contractmodel');
        $this->load->model('couponmodel');
        $this->load->model('activitymodel');
        $this->load->model('storemodel');
        $post   = $this->input->post(NULL,true);
        $serial = intval($post['id']);
        $filed  = ['id','contract_id','resident_id','store_id','room_id','status','created_at'];
        $resident = Contractmodel::where('id',$serial)->with('store')->with('roomunion')->with('residents')->get($filed);
        $this->api_res(0,['resident'=>$resident]);
    }

    /**
     * 住户账单信息
     */
    public function bill()
    {
        //账单表
        $this->load->model('ordermodel');
        $this->load->model('testbillmodel');
        $post = $this->input->post(null,true);
        $resident_id = intval($post['id']);
        $filed = ['money','type'];
        $order = Ordermodel::where('resident_id',$resident_id)->whereIn('status',['PENDING','AUDITED'])
                ->get($filed)->toArray();
        if(!empty($order)){
            var_dump($order);
        }
        //流水表
        $bill = Ordermodel::where('resident_id',$resident_id)->whereIn('status',['COMPLATE','CONFIRM'])
                ->get($filed)->toArray();
        $this->api_res(0,['order'=>$order,'bill'=>$bill]);
        /*if (!empty($bill)){
            if (isset($bill['ROOM'])){
                $bill_room = $bill['ROOM'];
                $room_money = 0.00;
                foreach ($bill_room as $key =>$value ){
                    $room_money += $bill_room[$key]['money'];
                    //var_dump($room_money);
                    $bill['room_money'] = $room_money;
                }
            }

            if (isset($bill['ELECTRICITY'])){
                $bill_room = $bill['ELECTRICITY'];
                $device_money = 0.00;
                foreach ($bill_room as $key =>$value ){
                    $device_money  += $bill_room[$key]['money'];
                    $bill['device_money'] = $device_money;
                }
            }

            if (isset($bill['DEIVCE'])){
                $bill_room = $bill['DEIVCE'];
                $device_money = 0.00;
                foreach ($bill_room as $key =>$value ){
                    $device_money  += $bill_room[$key]['money'];
                    $bill['room_money'] = $device_money;
                }
            }
//            'ROOM','DEIVCE','UTILITY','REFUND','DEPOSIT_R',
//            'DEPOSIT_O','MANAGEMENT','OTHER','RESERVE','CLEAN',
//            'WATER','ELECTRICITY','COMPENSATION','REPAIR','HOT_WATER','OVERDUE'
//            房间 设备  水电费 退房 预订 清洁费 水费 电费 赔偿费 维修费 热水水费 滞纳金
            if (isset($bill['UTILITY'])){
                $bill_room = $bill['UTILITY'];
                $utility_money = 0.00;
                foreach ($bill_room as $key =>$value ){
                    $utility_money   += $bill_room[$key]['money'];
                    var_dump($utility_money );
                }
            }

            if (isset($bill['UTILITY'])){
                $bill_room = $bill['UTILITY'];
                $utility_money = 0.00;
                foreach ($bill_room as $key =>$value ){
                    $utility_money   += $bill_room[$key]['money'];
                    var_dump($utility_money );
                }
            }


        }*/
    }

    /**
     * 获取用户账单信息(按账单周期分组)
     */
    public function getResidentOrder()
    {

        $this->load->model('ordermodel');
        $resident_id    = $this->input->post('resident_id',true);
        $resident   = Residentmodel::findOrFail($resident_id);

        //未支付的列表
        $unpaid    = $resident->orders()
            ->whereIn('status',[Ordermodel::STATE_PENDING,Ordermodel::STATE_GENERATED,Ordermodel::STATE_AUDITED])
            ->orderBy('year','DESC')
            ->orderBy('month','DESC')
            ->get()
            ->map(function($order){
                $order->date    = $order->year.'-'.$order->month;
                return $order;
            });

        $paid    = $resident->orders()
            ->whereIn('status',[Ordermodel::STATE_CONFIRM,Ordermodel::STATE_COMPLETED])
            ->orderBy('year','ASC')
            ->orderBy('month','ASC')
            ->get()
            ->map(function($order){
                $order->date    = $order->year.'-'.$order->month;
                return $order;
            })
        ;

        $unpaid_money   = $unpaid->sum('money');
        $paid_money     = $paid->sum('money');
        $discount_money     = $paid->sum('discount_money');

        $unpaid   = $unpaid->groupBy('date')->map(function($unpaid){
            $a  = [];
            $a['orders']    = $unpaid->toArray();
            $a['total_money']=$unpaid->sum('money');
            return $a;
        });
        $paid   = $paid->groupBy('date')->map(function($paid){
            $a  = [];
            $a['orders']    = $paid->toArray();
            $a['total_money']=$paid->sum('money');
            $a['total_paid']=$paid->sum('paid');
            $a['discount_money']=$paid->sum('discount');
            return $a;
        });
        $this->api_res(0,compact('unpaid_money','paid_money','discount_money','unpaid','paid'));

    }




    /**
     * @return mixed
     * 表单验证
     */
    private function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'gender',
                'label' => '性别',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '联系电话',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_type',
                'label' => '证件类型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_number',
                'label' => '证件号',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_one',
                'label' => '证件正面',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_two',
                'label' => '证件反面',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'card_three',
                'label' => '手持证件',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'alternative',
                'label' => '紧急联系人',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'alter_phone',
                'label' => '紧急联系人电话',
                'rules' => 'trim|required',
            ),
        );
        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }
}