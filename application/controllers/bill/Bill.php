<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/29 0029
 * Time:        10:11
 * Describe:    流水
 */
class Bill extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('billmodel');
        $this->load->model('ordermodel');
    }

    /**
     * 流水列表
     */
    public function listBill() {
        $input                                          = $this->input->post(null, true);
        $page                                           = isset($input['page']) ? $input['page'] : 1;
        $where                                          = [];
        empty($input['store_id']) ?: $where['store_id'] = $input['store_id'];
        $store_ids                                      = explode(',', $this->employee->store_ids);
        $start_date                                     = empty($input['start_date']) ? '1970-01-01' : $input['start_date'];
        $end_date                                       = empty($input['end_date']) ? '2030-12-12' : $input['end_date'];
        $search                                         = empty($input['search']) ? '' : $input['search'];
        $offset                                         = ($page - 1) * PAGINATE;
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');

        $bills = Billmodel::with(['roomunion', 'store', 'resident', 'employee'])
            ->offset($offset)->limit(PAGINATE)
            ->where($where)->whereIn('store_id', $store_ids)
            ->whereBetween('pay_date', [$start_date, $end_date])
            ->orderBy('sequence_number', 'desc')
            ->where(function ($query) use ($search) {
                $query->orWhereHas('resident', function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                })->orWhereHas('employee', function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                })->orWhereHas('roomunion', function ($query) use ($search) {
                    $query->where('number', 'like', "%$search%");
                });
            })->offset($offset)->limit(PAGINATE)->get()->map(function ($query) {
                if ($query->pay_date = '1970-01-01 00:00:00') {
                    $query->pay_date = $query->created_at->toDateTimeString();
                }
                return $query;
            });

        $billnumber = Billmodel::with(['roomunion', 'store', 'resident', 'employee'])
            ->where($where)->whereIn('store_id', $store_ids)
            ->whereBetween('pay_date', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->orWhereHas('resident', function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                })->orWhereHas('employee', function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                })->orWhereHas('roomunion', function ($query) use ($search) {
                    $query->where('number', 'like', "%$search%");
                });
            })->get()->count();

        $total_page = ceil($billnumber / PAGINATE);
        $this->api_res(0, ['bills' => $bills, 'total_page' => $total_page]);
    }

    /**
     * 查看流水下的账单信息
     */
    public function showBill()
    {
        $input   = $this->input->post(null, true);
        $bill_id = $input['id'];
        $bill    = Billmodel::find($bill_id);
        if (empty($bill)) {
            $this->api_res(1007);
            return;
        }

        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');
        $this->load->model('residentmodel');
        $sequence = $bill->sequence_number;
        $orders = Ordermodel::with(['coupon'=>function($query){
            $query->with('coupon_type');
        }])->where('sequence_number',$sequence)->get();
        $sumMoney   = number_format($orders->sum('money'),2,'.','');
        $sumPaid    = number_format($orders->sum('paid'),2,'.','');
        $sumDiscount    = number_format($sumMoney-$sumPaid,2,'.','');
        $resident   = $bill->resident;
        $discount   = array_merge($orders->where('coupon','!=',null)->toArray(),[]);

        $this->api_res(0,['sumMoney'=>$sumMoney,'sumPaid'=>$sumPaid,'sumDiscount'=>$sumDiscount,'resident'=>$resident,'discount'=>$discount,'orders'=>$orders]);

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

//        $data['lists'] = Ordermodel::where('sequence_number', $sequence)->get()->toArray();
//        //        获取money
//        $data['sum'] = $bill->money;
//
//        $this->api_res(0, $data);

    }

    public function test() {

        $this->load->model('roomtypemodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $post  = $this->input->post(null, true);
        $where = [];
        if (!empty($post['building_id'])) {$where['building_id'] = intval($post['building_id']);};
        if (!empty($post['status'])) {$where['status'] = trim($post['status']);};
        if (!empty($post['store_id'])) {$where['store_id'] = intval($post['store_id']);}
        if (!empty($post['number'])) {$where['number'] = trim($post['number']);}
        $filed     = ['id', 'layer', 'status', 'room_type_id', 'number', 'rent_price', 'resident_id'];
        $roomunion = new Roomunionmodel();
        if (!empty($post['BLANK_days'])) {
            $days            = $post['BLANK_days'];
            $where['status'] = "BLANK";
            switch ($days) {
                case 1:
                    $time = [date('Y-m-d H:i:s', strtotime('-10 day', time())), date('Y-m-d H:i:s', time())];
                    $list = $roomunion->room_details($where, $filed, $time);
                    break;
                case 2:
                    $time = [date('Y-m-d H:i:s', strtotime('-20 day', time())), date('Y-m-d H:i:s', strtotime('-10 day', time()))];
                    $list = $roomunion->room_details($where, $filed, $time);
                    break;
                case 3;
                    $time = [date('Y-m-d H:i:s', strtotime('-30 day', time())), date('Y-m-d H:i:s', strtotime('-20 day', time()))];
                    $list = $roomunion->room_details($where, $filed, $time);
                    break;
                case 4:
                    $time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
                    $list = $roomunion->room_details($where, $filed, $time);
                    break;
                default:
                    $time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
                    $list = $roomunion->room_details($where, $filed, $time);
                    break;
            }
        } else {
            $time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
            $list = $roomunion->room_details($where, $filed, $time);
        }
        $this->api_res(0, $list);
    }

    /**
     * 流水数据
     */
    public function billArray($store_id, $begin, $end) {
        $filed = ['id', 'store_id', 'employee_id', 'resident_id', 'room_id', 'money', 'type', 'pay_type',
            'pay_date', 'status', 'sequence_number', 'remark'];
        $bill = Billmodel::with(['roomunion_s', 'store_s', 'resident_s', 'employee_s', 'order'])
            ->where('store_id', $store_id)
            ->whereBetween('pay_date', [$begin, $end])->orderBy('pay_date', 'DESC')
            ->orderBy('resident_id', 'DESC')
            ->get($filed)->toArray();
        $bill_array = [];
        foreach ($bill as $key => $value) {
            $res                      = [];
            $res['pay_date']          = $bill[$key]['pay_date'];
            $res['number']            = $bill[$key]['roomunion_s']['number'];
            $res['resident']          = $bill[$key]['resident_s']['name'];
            $res['money']             = $bill[$key]['money'];
            $res['pay_type']          = $this->getBillPayType($bill[$key]['pay_type']);
            $res['ROOM_money']        = 0;
            $res['MANAGEMENT_money']  = 0;
            $res['DEPOSIT_R_money']   = 0;
            $res['DEPOSIT_O_money']   = 0;
            $res['WATER_money']       = 0;
            $res['HOT_WATER_money']   = 0;
            $res['ELECTRICITY_money'] = 0;
            $res['REFUND_money']      = 0;
            $res['other_money']       = 0;
            $res['remark']            = $bill[$key]['remark'];
            if (!empty($bill[$key]['order'])) {
                $order              = $bill[$key]['order'];
                $res['other_money'] = 0;
                foreach ($order as $key_o => $value_o) {
                    if ($order[$key_o]['type'] == 'ROOM') {
                        $res['ROOM_money'] += $order[$key_o]['paid'];
                    } elseif ($order[$key_o]['type'] == 'MANAGEMENT') {
                        $res['MANAGEMENT_money'] += $order[$key_o]['paid'];
                    } elseif ($order[$key_o]['type'] == 'DEPOSIT_R') {
                        $res['DEPOSIT_R_money'] += $order[$key_o]['paid'];
                    } elseif ($order[$key_o]['type'] == 'DEPOSIT_O') {
                        $res['DEPOSIT_O_money'] += $order[$key_o]['paid'];
                    } elseif ($order[$key_o]['type'] == 'WATER') {
                        $res['WATER_money'] += $order[$key_o]['paid'];
                    } elseif ($order[$key_o]['type'] == 'HOT_WATER') {
                        $res['HOT_WATER_money'] += $order[$key_o]['paid'];
                    } elseif ($order[$key_o]['type'] == 'ELECTRICITY') {
                        $res['ELECTRICITY_money'] += $order[$key_o]['paid'];
                    } elseif ($order[$key_o]['type'] == 'REFUND') {
                        $res['REFUND_money'] += $order[$key_o]['paid'];
                    } else {
                        $res['other_money'] += $order[$key_o]['paid'];
                    }
                }
            }
            $bill_array[] = $res;
        }
        return $bill_array;
    }

    public function billexcel() {
        $post     = $this->input->post(null, true);
        $store_id = trim($post['store_id']);
        $begin    = empty($post['begin_time']) ? date('Y-m-d H:i:s', 0) : trim($post['begin_time']);
        $end      = empty($post['end_time']) ? date('Y-m-d H:i:s', time()) : trim($post['end_time']);
        if (!isset($post['store_id']) || empty($post['store_id'])) {
            $this->api_res(1002, []);
            return;
        }
        $this->load->model('billmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');
        $bill     = $this->billArray($store_id, $begin, $end);
        $row      = count($bill) + 3;
        $filename = date('Y-m-d-H:i:s') . '导出' . $begin . ' _ ' . $end . '_流水数据.Xlsx';
        $store    = Storemodel::findOrFail($store_id);
        $store    = $store->name;
        $phpexcel = new Spreadsheet();
        $sheet    = $phpexcel->getActiveSheet();
        $this->createPHPExcel($phpexcel, $filename); //创建excel
        $this->setExcelTitle($phpexcel, $store, $begin, $end); //设置表头
        $this->setExcelFirstRow($phpexcel); //设置各字段名称
        $sheet->fromArray($bill, null, 'A4'); //想excel中写入数据
        $this->setExcelColumnWidth($phpexcel); //设置Excel每列宽度
        $this->setAlignCenter($phpexcel, $row); //设置记录值居中
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpexcel, 'Xlsx');
        header("Pragma: public");
        header("Expires: 0");
        header("Content-Type:application/octet-stream");
        header("Content-Transfer-Encoding:binary");
        header('Cache-Control: max-age=0');
        header("Content-Disposition:attachment;filename=$filename");
        $writer->save('php://output');
        exit;
    }

    private function createPHPExcel(Spreadsheet $phpexcel, $filename) {
        $phpexcel->getProperties()
            ->setCreator('梵响数据')
            ->setLastModifiedBy('梵响数据')
            ->setTitle($filename)
            ->setSubject($filename)
            ->setDescription($filename)
            ->setKeywords($filename)
            ->setCategory($filename);
        $phpexcel->setActiveSheetIndex(0);
        return $phpexcel;
    }

    private function setExcelTitle(Spreadsheet $phpexcel, $store, $start, $end) {
        $phpexcel->getActiveSheet()
            ->mergeCells('A1:O2')
            ->setCellValue('A1', "$store" . "$start" . ' - ' . "$end" . '流水统计')
            ->getStyle("A1:O2")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $phpexcel->getActiveSheet()->getCell('A1')->getStyle()->getFont()->setSize(16);
    }

    private function setAlignCenter(Spreadsheet $phpexcel, $row) {
        $phpexcel->getActiveSheet()
            ->getStyle("A3:N{$row}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    private function setExcelFirstRow(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->setCellValue('A3', '支付时间')
            ->setCellValue('B3', '房间号')
            ->setCellValue('C3', '住户姓名')
            ->setCellValue('D3', '支付总金额')
            ->setCellValue('E3', '支付方式')
            ->setCellValue('F3', '房租')
            ->setCellValue('G3', '物业')
            ->setCellValue('H3', '住宿押金')
            ->setCellValue('I3', '其他押金')
            ->setCellValue('J3', '水费')
            ->setCellValue('K3', '热水费')
            ->setCellValue('L3', '电费')
            ->setCellValue('M3', '退租')
            ->setCellValue('N3', '其它费用')
            ->setCellValue('O3', '备注');
    }

    private function setExcelColumnWidth(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(22);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('M')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('N')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
    }

    private function getBillPayType($payType) {
        switch ($payType) {
            case Ordermodel::PAYWAY_JSAPI:
                return '微信';
                break;
            case Ordermodel::PAYWAY_BANK:
                return '刷卡';
                break;
            case Ordermodel::PAYWAY_ALIPAY:
                return '支付宝';
                break;
            case Ordermodel::PAYWAY_DEPOSIT:
                return '押金抵扣';
                break;
            default:
                return '';
                break;
        }
    }
    /********************************************生成账单******************************************/

    /**
     * 生成账单
     */
    public function generate() {

        $input    = $this->input->post(null, true);
        $store_id = $input['store_id'];
        $year     = $this->checkAndGetYear($input['year'], false);
        $month    = $this->checkAndGetMonth($input['month'], false);

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
            $c = Roomunionmodel::with([
                'resident',
                'orders'  => function ($query) use ($month, $year) {
                    $query->where('year', $year)->where('month', $month);
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
     * 查询数据库并生成当月账单
     */
    private function queryAndGenerateOrders($room, $year, $month, $payDate) {
        if (empty($room->resident_id)) {
            return false;
        }

        $resident = $room->resident;
        $endTime           = $resident->end_time;
        $dataCheckoutYear  = $endTime->year;
        $dataCheckoutMonth = $endTime->month;

        if (empty($resident)) {
            log_message('error', 'no-resident' . $room->number);
            return false;
        }

        $orders = $room->orders->where('resident_id', $resident->id);
//        var_dump($orders->toArray());exit;
        $number = Ordermodel::newNumber();
        if ($year == $dataCheckoutYear && $month == $dataCheckoutMonth) {
            $rentMoney       = $orders->where('type', Ordermodel::PAYTYPE_ROOM)->sum('money');
            $managementMoney = $orders->where('type', Ordermodel::PAYTYPE_MANAGEMENT)->sum('money');
            $daysOfMonth = $endTime->copy()->endOfMonth()->day;
            $rent     = ceil($resident->real_rent_money * ($endTime->day) / $daysOfMonth);
            $property = ceil($resident->real_property_costs * ($endTime->day) / $daysOfMonth);
            if ($rent > 0 && $rentMoney==0) {
                $numberRoom = $number;
                $this->newBill($room, $resident, Ordermodel::PAYTYPE_ROOM, $rent, $numberRoom, $year, $month, $payDate, 0);
            }
            if ($property > 0 && $managementMoney==0) {
                $numberProperty = $number;
                $this->newBill($room, $resident, Ordermodel::PAYTYPE_MANAGEMENT, $property, $numberProperty, $year, $month, $payDate, 0);
            }
        } else {
            $bills = [
                Ordermodel::PAYTYPE_ROOM => [
                    'price' => $resident->real_rent_money,
                    'id' => 0,
                ],
                Ordermodel::PAYTYPE_MANAGEMENT => [
                    'price' => $resident->real_property_costs,
                    'id' => 0,
                ],
            ];

            if ($room->devices->count()) {
                $bills[Ordermodel::PAYTYPE_DEVICE] = [
                    'price' => $room->devices->sum('money'),
                    'id' => $room->devices->first()->id,
                ];
            }
            $pay_frequency  = $resident->pay_frequency;
            if($pay_frequency>1){

                foreach ($bills as $type => $bill) {
                    $moneyPaid = $orders->where('type', $type)->sum('money');
                    if ($moneyPaid > 0) {
                        continue;
                    } else {
                        for($i=0;$i<$pay_frequency;$i++){
                            $year2  = $year;
                            $month2  = $month+$i;
                            if($month2>12){
                                $year2  = $year2+1;
                                $month2 = $month2-12;
                            }
                            $this->newBill($room, $resident, $type, $bill['price'], $number, $year2, $month2, $payDate, $bill['id']);
                        }
                    }
                }
            }else{
                foreach ($bills as $type => $bill) {
                    $moneyPaid = $orders->where('type', $type)->sum('money');
                    if ($moneyPaid > 0) {
                        continue;
                    } else {
                        $this->newBill($room, $resident, $type, $bill['price'], $number, $year, $month, $payDate, $bill['id']);
                    }
                }
            }
        }
        return true;
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
            'employee_id'  => $this->employee->id,
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
}
