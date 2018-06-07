<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use EasyWeChat\Foundation\Application;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/29 0029
 * Time:        10:11
 * Describe:    账单
 */
class Bill extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 展示账单列表
     */
    public function listBill(){

        $input  = $this->input->post(null,true);

       /* $args = [
            'year'        => $this->checkAndGetYear($this->input->post('year',true)),
            'month'       => $this->checkAndGetMonth($this->input->post('month',true)),
            'status_show' => $this->input->post('status_show',true),
            'room_number' => $this->input->post('room_number',true),
        ];*/



//        $city       = $input['city'];
//        $store_id   = $input['store_id'];
//        $year       = $input['year'];
//        $month      = $input['month'];
//        $room_number    = $input['room_number'];
//        $status     = $input['status'];

        //判断权限
//        if(!$this->isAdmin()){
//            $this->api_res(1011);
//            return;
//        }




        //$apartmentId = (int) $this->apartmentIdFilter();

        $where  = [];

        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');

        $query  = Roomunionmodel::where('resident_id','>',0)->where($where);


        var_dump($data->toArray());







    }

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
        $payDate = Ordermodel::calcPayDate($year, $month);
        try {
            DB::beginTransaction();
            Roomunionmodel::with([
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
                ])
                ->chunk(100, function ($rooms) use ($year, $month, $payDate) {
                    foreach ($rooms as $room) {
                        $this->queryAndGenerateOrders($room, $year, $month, $payDate);
                    }
                });
            DB::commit();
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
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

        if (empty($resident)) {
            log_message('error', 'no-resident' . $room->number);
            return false;
        }

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

        return true;
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
            'apartment_id'  => $room->apartment_id,
            'room_type_id'  => $room->room_type_id,
            'deal'          => Ordermodel::DEAL_UNDONE,
            'status'        => Ordermodel::STATE_GENERATED,
            'resident_id'   => $room->resident_id,
            'customer_id'   => $resident->customer_id,
            'remark'        => $remark,
            'pay_status'    => Ordermodel::PAYSTATE_RENEWALS,
        ]);
        $order->save();

        return $order;
    }


}
