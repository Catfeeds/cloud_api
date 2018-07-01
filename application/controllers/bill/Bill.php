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
                if ($query->pay_date = '1970-01-01 00:00:00'){
                    $query->pay_date    = $query->created_at->toDateTimeString();
                }
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



    /********************************************生成账单******************************************/

    /**
     * 生成账单
     */
    public function generate()
    {
        $input  = $this->input->post(null,true);
        $store_id   = $input['store_id'];
        $year       = $this->checkAndGetYear($input['year'],false);;
        $month      = $this->checkAndGetMonth($input['month'],false);

        if (empty($month) || empty($year)) {
            $this->api_res(10020);
            return;
        }

        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('smartdevicemodel');
        $this->load->model('residentmodel');
        $this->load->model('devicemodel');
        $payDate = Ordermodel::calcPayDate($year, $month);
        try {
            DB::beginTransaction();
            $c=Roomunionmodel::with([
                'resident',
                'orders'    => function ($query) use ($month, $year) {
                    $query->where('year', $year)->where('month', $month);
                },
                'devices'  => function ($query) use ($month, $year) {
                    $query->where('year', $year)->where('month', $month);
                }
            ])
                ->where('store_id', $store_id)
                ->where('resident_id', '>', 0)
                ->whereIn('status', [
                    Roomunionmodel::STATE_RENT,
                    Roomunionmodel::STATE_ARREARS,
                ])->count();
            $this->api_res(0,['count'=>$c]);
            return;
//                ->chunk(100, function ($rooms) use ($year, $month, $payDate) {
//                    foreach ($rooms as $room) {
//
//                        $this->queryAndGenerateOrders($room, $year, $month, $payDate);
//                    }
//                });

            DB::commit();
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

    public function testa(){
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('devicemodel');
        $rooms   = Roomunionmodel::whereIn('id',[2,4])->get();
        $year   = 2018;
        $month   = 8;
        foreach ($rooms as $room)
        $this->queryAndGenerateOrders($room,2018,8,$payDate = Ordermodel::calcPayDate($year, $month));
        echo 1;
    }

    /**
     * 查询数据库并生成当月账单, 这里没有考虑用户月中合同到期的情况
     */
    private function queryAndGenerateOrders($room, $year, $month, $payDate)
    {
        if (empty($room->resident_id)) {
            return false;
        }


        $resident   = $room->resident;

//        var_dump($resident->toArray());exit;

        $endTime    = $resident->end_time;
        $dataCheckoutYear   = $endTime->year;
        $dataCheckoutMonth   = $endTime->month;
//        var_dump($dataCheckoutMonth);exit;

        if (empty($resident)) {
            log_message('error', 'no-resident' . $room->number);
            return false;
        }


//        var_dump($dataCheckoutYear);
//        var_dump($dataCheckoutMonth);
//        exit;

        if($year==$dataCheckoutYear && $month==$dataCheckoutMonth)
        {
//            echo 1;exit;
//            $dataCheckoutDay    = $endTime->day;
            $startDay       = $resident->begin_time->day;
            $daysOfMonth    = $endTime->copy()->endOfMonth()->day;
            $rent       = ceil($resident->real_rent_money * ($endTime->day - $startDay + 1) / $daysOfMonth);
            $property   = ceil($resident->real_property_costs * ($endTime->day - $startDay + 1) / $daysOfMonth);

            if($rent>0){
                $numberRoom = Ordermodel::newNumber();
                $rentOrder=$this->newBill($room, $resident,Ordermodel::PAYTYPE_ROOM, $rent, $numberRoom, $year, $month, $payDate, 0);
//            var_dump($rentOrder->toArray());exit;
            }

            if($property>0){
                $numberProperty = Ordermodel::newNumber();
                $this->newBill($room, $resident,Ordermodel::PAYTYPE_MANAGEMENT, $property, $numberProperty, $year, $month, $payDate, 0);

            }

        }else{

            $orders     = $room->orders->where('resident_id', $resident->id);
            $unpaid     = $orders->whereIn('status', Ordermodel::unpaidStatuses());
            $paid       = $orders->whereIn('status', Ordermodel::paidStatuses());

            if ($unpaid->count()) {
                $number = $unpaid->first()->number;
            } else {
                $number = Ordermodel::newNumber();
            }

            $rentPaid       = $paid->where('type', Ordermodel::PAYTYPE_ROOM)->sum('money');
            $managementPaid = $paid->where('type', Ordermodel::PAYTYPE_MANAGEMENT)->sum('money');

            $bills = [
                Ordermodel::PAYTYPE_ROOM        => [
                    'price' => $resident->real_rent_money,
                    'id'    => 0,
                ],
                Ordermodel::PAYTYPE_MANAGEMENT  => [
                    'price' => $resident->real_property_costs,
                    'id'    => 0,
                ],
            ];

            if ($room->devices->count()) {
                $bills[Ordermodel::PAYTYPE_DEVICE] = [
                    'price' => $room->devices->sum('money'),
                    'id'    => $room->devices->first()->id,
                ];
            }

            foreach ($bills as $type => $bill) {
                $moneyPaid  = $paid->where('type', $type)->sum('money');
                $moneyToPay = ceil($bill['price'] - $moneyPaid);
                if (1 >= $moneyToPay) {
                    continue;
                }

                $unpaidOrder = $unpaid->where('type', $type)->first();

                if (count($unpaidOrder)) {
                    $unpaidOrder->update([
                        'money' => $moneyToPay,
                        'paid'  => $moneyToPay,
                        'status' => Ordermodel::STATE_GENERATED,
                    ]);
                } else {
                    $this->newBill($room, $resident, $type, $moneyToPay, $number, $year, $month, $payDate, $bill['id']);
                }
            }
        }

        return true;
    }

    private function generateHalfOrder($residents)
    {
       /* foreach($residents as $resident){
            $beginTime          = $resident->begin_time;
            $payFrequency       = $resident->pay_frequency;
            $dateCheckIn        = $beginTime->day;
            $daysThatMonth      = $beginTime->copy()->endOfMonth()->day;
            $daysLeftOfMonth    = $daysThatMonth - $dateCheckIn + 1;
            $firstOfMonth       = $resident->begin_time->copy()->firstOfMonth();

            //当月剩余天数的订单
            $data[]     = array(
                'year'       => $beginTime->year,
                'month'      => $beginTime->month,
                'rent'       => ceil($resident->real_rent_money * $daysLeftOfMonth / $daysThatMonth),
                'management' => ceil($resident->real_property_costs * $daysLeftOfMonth / $daysThatMonth),
            );

            //如果是短租, 只生成当月的账单
            if ($resident->rent_type == Residentmodel::RENTTYPE_SHORT) {
                return $data;
            }

            if ($payFrequency > 1 OR $beginTime->day >= 21) {
                $i = 1;
                do {
                    $tmpDate    = $firstOfMonth->copy()->addMonths($i);
                    $data[] = array(
                        'year'       => $tmpDate->year,
                        'month'      => $tmpDate->month,
                        'rent'       => $resident->real_rent_money,
                        'management' => $resident->real_property_costs,
                    );
                } while (++ $i < $resident->pay_frequency);
            }

            //如果是年付, 可能要有第13个月的账单
            if (12 == $payFrequency) {
                $endDate    = $resident->end_time;
                $endOfMonth = $endDate->copy()->endOfMonth();

                if ($endDate->day < $endOfMonth->day) {
                    $data[] = array(
                        'year'          => $endDate->year,
                        'month'         => $endDate->month,
                        'rent'          => ceil($resident->real_rent_money * $endDate->day / $endOfMonth->day),
                        'management'    => ceil($resident->real_property_costs * $endDate->day / $endOfMonth->day),
                    );
                }
            }
            return $data;
        }*/
    }

    /**
     * 向数据库里写入账单记录
     */
    private function newBill($room, $resident, $type, $money, $number, $year, $month, $payDate, $otherId = 0, $remark = '')
    {
        $order = new Ordermodel();
        $order->fill([
            'number'        => $number,
            'type'          => $type,
            'other_id'      => $otherId,
            'year'          => $year,
            'month'         => $month,
            'pay_date'      => $payDate,
            'money'         => $money,
            'paid'          => $money,
            'room_id'       => $room->id,
            'employee_id'   => 99,
//            'employee_id'   => $this->employee->id,
            'store_id'      => $room->store_id,
            'room_type_id'  => $room->room_type_id,
            'deal'          => Ordermodel::DEAL_UNDONE,
            'status'        => Ordermodel::STATE_GENERATED,
            'resident_id'   => $room->resident_id,
            'customer_id'   => $resident->customer_id,
            'uxid'          => $resident->uxid,
            'remark'        => $remark,
            'pay_status'    => Ordermodel::PAYSTATE_RENEWALS,
        ]);
        $order->save();
        return $order;
    }



}
