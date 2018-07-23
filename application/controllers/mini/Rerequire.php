<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/10 0010
 * Time:        19:48
 * Describe:
 */
class Rerequire extends MY_Controller {
    protected $store_id;

    public function __construct() {
        parent::__construct();
//        exit;
        $this->store_id = $this->input->post('store_id');
    }

    public function test1() {

    }

    public function getEndTimeRooms() {
        $year     = 2018;
        $month    = 7;
        $input    = $this->input->post(null, true);
        $store_id = $this->store_id;
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('devicemodel');
        $payDate = Ordermodel::calcPayDate($year, $month);

        $rooms = Roomunionmodel::with('resident')
            ->with(['orders' => function ($query) use ($month, $year) {
                $query->where('year', $year)->where('month', $month)
                    ->whereIn('type', [Ordermodel::PAYTYPE_ROOM, Ordermodel::PAYTYPE_MANAGEMENT]);
//                    ->where('status',Ordermodel::STATE_GENERATED);
            }])
            ->where('resident_id', '>', 0)
            ->where('store_id', $store_id)
            ->whereIn('status', [
                Roomunionmodel::STATE_RENT,
                Roomunionmodel::STATE_ARREARS,
            ])
            ->whereHas('resident', function ($query) {
                $query->whereBetween('end_time', ['2018-07-01 00:00:00', '2018-07-31 23:59:59']);
            })
            ->orderBy('resident_id')
            ->get();
//            ->map(function($res){
        //                return $res->resident->id;
        //            });
        //        var_dump($rooms->toArray());

        $data = $this->calcEndTimeResidentMoney($rooms, $year, $month, $payDate);

        $this->api_res(0);
//        $this->dataToExcel($data);
        //        $this->api_res(0,$data);
    }
    //计算本月到期应付的金额
    public function calcEndTimeResidentMoney($rooms, $year, $month, $payDate) {
        $arr = [];
        foreach ($rooms as $room) {
            $resident = $room->resident;

            $endTime           = $resident->end_time;
            $dataCheckoutYear  = $endTime->year;
            $dataCheckoutMonth = $endTime->month;

            if ($year == $dataCheckoutYear && $month == $dataCheckoutMonth) {
                $number                                    = Ordermodel::newNumber();
                $dataCheckoutDay                           = $endTime->day;
                $startDay                                  = $resident->begin_time->day;
                $daysOfMonth                               = $endTime->copy()->endOfMonth()->day;
                $rent                                      = ceil($resident->real_rent_money * ($endTime->day) / $daysOfMonth);
                $property                                  = ceil($resident->real_property_costs * ($endTime->day) / $daysOfMonth);
                $data[$resident->id]['store_id']           = $room->store_id;
                $arr[$resident->id]['room_number']         = $room->number;
                $arr[$resident->id]['name']                = $resident->name;
                $arr[$resident->id]['end_time']            = $resident->end_time->toDateTimeString();
                $arr[$resident->id]['real_rent_money']     = $resident->real_rent_money;
                $arr[$resident->id]['real_property_costs'] = $resident->real_property_costs;
                $arr[$resident->id]['rent']                = $rent;
                $arr[$resident->id]['property']            = $property;

                if (empty($room->orders->toArray())) {
                    if ($rent > 0) {
                        $numberRoom = $number;
                        $this->newBill($room, $resident, Ordermodel::PAYTYPE_ROOM, $rent, $numberRoom, $year, $month, $payDate, 0);
                    }

                    if ($property > 0) {
                        $numberProperty = $number;
                        $this->newBill($room, $resident, Ordermodel::PAYTYPE_MANAGEMENT, $property, $numberProperty, $year, $month, $payDate, 0);

                    }
                } else {
                    log_message('error', 1);
                }

            } else {
                $arr[$resident->id] = null;
            }

        }

        return $arr;
    }
    //获取本月到期住户已经生成未审核的本月账单
    public function getEndTimeResidentOrder() {
        $year  = 2018;
        $month = 7;
        $input = $this->input->post(null, true);
//        $store_id   = $input['store_id'];
        $store_id = $this->store_id;
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('devicemodel');
        $payDate = Ordermodel::calcPayDate($year, $month);

        $rooms = Roomunionmodel::with(['resident',
            'orders' => function ($query) use ($month, $year) {
                $query->where('year', $year)->where('month', $month)
                    ->whereIn('type', [Ordermodel::PAYTYPE_ROOM, Ordermodel::PAYTYPE_MANAGEMENT]);
//                    ->where('status',Ordermodel::STATE_GENERATED);
            }])
            ->where('resident_id', '>', 0)
            ->where('store_id', $store_id)
            ->whereIn('status', [
                Roomunionmodel::STATE_RENT,
                Roomunionmodel::STATE_ARREARS,
            ])
            ->whereHas('resident', function ($query) {
                $query->whereBetween('end_time', ['2018-07-01 00:00:00', '2018-07-31 23:59:59']);
            })
            ->orderBy('resident_id')
            ->get();

        $data = [];
        foreach ($rooms as $room) {
            $orders = $room->orders;

            $resident = $room->resident;

            $data[$resident->id]['store_id']    = $room->store_id;
            $data[$resident->id]['room_number'] = $room->number;
            $data[$resident->id]['name']        = $resident->name;
            $data[$resident->id]['rent']        = null;
            $data[$resident->id]['property']    = null;
            if (!empty($orders)) {
                foreach ($orders as $order) {
                    if ($order->type == Ordermodel::PAYTYPE_ROOM) {
                        $data[$resident->id]['rent'] = $order->money;
                    }
                    if ($order->type == Ordermodel::PAYTYPE_MANAGEMENT) {
                        $data[$resident->id]['property'] = $order->money;
                    }
                }
            }
        }

        $this->dataToExcel($data);

//        $this->api_res(0,$data);

    }

    /**
     * 向数据库里写入账单记录
     */
    private function newBill($room, $resident, $type, $money, $number, $year, $month, $payDate, $otherId = 0, $remark = '') {
        $order = new Ordermodel();
        $order->fill([
            'number'       => $number,
            'type'         => $type,
            'other_id'     => $otherId,
            'year'         => $year,
            'month'        => $month,
            'pay_date'     => $payDate,
            'money'        => $money,
            'paid'         => $money,
            'room_id'      => $room->id,
            'employee_id'  => 99,
//            'employee_id'   => $this->employee->id,
            'store_id'     => $room->store_id,
            'room_type_id' => $room->room_type_id,
            'deal'         => Ordermodel::DEAL_UNDONE,
            'status'       => Ordermodel::STATE_GENERATED,
            'resident_id'  => $room->resident_id,
            'customer_id'  => $resident->customer_id,
            'uxid'         => $resident->uxid,
            'remark'       => $remark,
            'pay_status'   => Ordermodel::PAYSTATE_RENEWALS,
        ]);
        $order->save();
        return $order;
    }

    public function testa() {
        $year     = 2018;
        $month    = 7;
        $input    = $this->input->post(null, true);
        $store_id = $input['store_id'];
        $payDate  = Ordermodel::calcPayDate($year, $month);
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('devicemodel');
//        $rooms   = Roomunionmodel::whereIn('id',[1759])->get();
        $rooms = Roomunionmodel::with([
            'resident',
            'orders'  => function ($query) use ($month, $year) {
                $query->where('year', $year)->where('month', $month)->whereIn('type', [Ordermodel::PAYTYPE_ROOM, Ordermodel::PAYTYPE_MANAGEMENT]);
            },
            'devices' => function ($query) use ($month, $year) {
                $query->where('year', $year)->where('month', $month);
            },
        ])
            ->where('store_id', $store_id)
            ->where('resident_id', '>', 0)

            ->whereIn('status', [
                Roomunionmodel::STATE_RENT,
                Roomunionmodel::STATE_ARREARS,
            ])
            ->whereDoesntHave('orders', function ($query) use ($month, $year) {
                $query->where('year', $year)->where('month', $month)->whereIn('type', [Ordermodel::PAYTYPE_ROOM, Ordermodel::PAYTYPE_MANAGEMENT]);
            })
            ->orderBy('resident_id')
            ->get();

        foreach ($rooms as $room) {

            $arr[] = $this->testb($room, $year, $month, $payDate);
        }
        $this->api_res(0, $rooms);
    }

    private function testb($room, $year, $month, $payDate) {
        if (empty($room->resident_id)) {
            return false;
        }

        $resident = $room->resident;

//        var_dump($resident->toArray());exit;

        $endTime           = $resident->end_time;
        $dataCheckoutYear  = $endTime->year;
        $dataCheckoutMonth = $endTime->month;
//        var_dump($dataCheckoutMonth);exit;

        if (empty($resident)) {
            log_message('error', 'no-resident' . $room->number);
            return false;
        }

        $orders = $room->orders->where('resident_id', $resident->id);
        $unpaid = $orders->whereIn('status', Ordermodel::unpaidStatuses());
        $paid   = $orders->whereIn('status', Ordermodel::paidStatuses());

        if ($unpaid->count()) {
            $number = $unpaid->first()->number;
        } else {
            $number = Ordermodel::newNumber();
        }

        $rentPaid       = $paid->where('type', Ordermodel::PAYTYPE_ROOM)->sum('money');
        $managementPaid = $paid->where('type', Ordermodel::PAYTYPE_MANAGEMENT)->sum('money');

        if ($year == $dataCheckoutYear && $month == $dataCheckoutMonth) {

            $dataCheckoutDay = $endTime->day;

            $startDay = $resident->begin_time->day;

            $daysOfMonth = $endTime->copy()->endOfMonth()->day;

            $rent     = ceil($resident->real_rent_money * ($endTime->day) / $daysOfMonth);
            $property = ceil($resident->real_property_costs * ($endTime->day) / $daysOfMonth);
//            $arr[$resident->id]['rent']=$rent;
            //            $arr[$resident->id]['property']=$property;

            if ($rent > 0) {
                $numberRoom = $number;
                $this->newBill($room, $resident, Ordermodel::PAYTYPE_ROOM, $rent, $numberRoom, $year, $month, $payDate, 0);
//            var_dump($rentOrder->toArray());exit;
            }

            if ($property > 0) {
                $numberProperty = $number;
                $this->newBill($room, $resident, Ordermodel::PAYTYPE_MANAGEMENT, $property, $numberProperty, $year, $month, $payDate, 0);

            }

        } else {
            $bills = [
                Ordermodel::PAYTYPE_ROOM       => [
                    'price' => $resident->real_rent_money,
                    'id'    => 0,
                ],
                Ordermodel::PAYTYPE_MANAGEMENT => [
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
                        'money'  => $moneyToPay,
                        'paid'   => $moneyToPay,
                        'status' => Ordermodel::STATE_GENERATED,
                    ]);
                } else {
                    $this->newBill($room, $resident, $type, $moneyToPay, $number, $year, $month, $payDate, $bill['id']);
                }
            }
        }

    }

    //导出数据
    public function dataToExcel($data) {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, 'A1');
        $writer = new Xlsx($spreadsheet);

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="store_id_' . $this->store_id . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $writer->save('php://output');
    }
}
