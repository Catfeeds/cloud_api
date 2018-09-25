<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        17:17
 * Describe:    订单表 一个订单分为多个流水
 */
class Order extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('ordermodel');
    }

    /**
     * BOSS端订单列表
     */
    public function listOrder() {
        $input                                          = $this->input->post(null, true);
        $where                                          = [];
        $store_ids                                      = explode(',', $this->employee->store_ids);
        empty($input['store_id']) ?: $where['store_id'] = $input['store_id'];
        empty($input['type']) ?: $where['type']         = $input['type'];
        empty($input['year']) ?: $where['year']         = $input['year'];
        empty($input['month']) ?: $where['month']       = $input['month'];
        $search                                         = empty($input['search']) ? '' : $input['search'];
        $page                                           = isset($input['page']) ? $input['page'] : 1;
        $offset                                         = ($page - 1) * PAGINATE;
        $status                                         = $input['status'];
        if ($status == 'PAY') {
            $status = [Ordermodel::STATE_COMPLETED];
        } elseif ($status == 'NOTPAY') {
            $status = array_diff($this->ordermodel->getAllStatus(), [Ordermodel::STATE_COMPLETED]);
        } else {
            $status = $this->ordermodel->getAllStatus();
        }

        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');

        $count = ceil((Ordermodel::with('store', 'roomunion', 'resident', 'employee')
                ->whereIn('store_id', $store_ids)
                ->where(function ($query) use ($search) {
                    $query->orWhereHas('resident', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    })->orWhereHas('roomunion', function ($query) use ($search) {
                        $query->where('number', 'like', "%$search%");
                    }) /*->orWhereHas('employee',function($query) use($search){
                $query->where('name','like',"%$search%");
                })*/    ;
                })
                ->where($where)
                ->whereIn('status', $status)
                ->count()) / PAGINATE);

        if ($count < $page) {
            $this->api_res(0, ['orders' => [], 'total_page' => $count]);
            return;
        }

        $orders = Ordermodel::with(['store', 'roomunion', 'resident', 'employee'])
            ->whereIn('store_id', $store_ids)
            ->where(function ($query) use ($search) {
                $query->orWhereHas('resident', function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                })->orWhereHas('roomunion', function ($query) use ($search) {
                    $query->where('number', 'like', "%$search%");
                }) /*->orWhereHas('employee',function($query) use($search){
            $query->where('name','like',"%$search%");
            })*/    ;
            })
            ->where($where)
            ->whereIn('status', $status)
            ->orderBy('created_at', 'DESC')
            ->orderBy('room_id', 'ASC')
            ->offset($offset)->limit(PAGINATE)
            ->get()->toArray();

        $this->api_res(0, ['orders' => $orders, 'total_page' => $count]);

    }

    /**
     * boss端创建账单
     */
    public function addOrder() {
        $field = ['room_id', 'resident_id', 'month', 'year', 'type', 'money'];
        if (!$this->validationText($this->validateStore())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $input       = $this->input->post();
        $room_id     = $input['room_id'];
        $resident_id = $input['resident_id'];
        $month       = $input['month'];
        $year        = $input['year'];
        $type        = $input['type'];
        $money       = $input['money'];
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
//        $this->load->model('storemodel');
        //        $this->load->model('roomtypemodel');
        $room     = Roomunionmodel::where('resident_id', $resident_id)->findOrFail($room_id);
        $resident = $room->resident;
        $this->load->model('ordermodel');
        $order               = new Ordermodel();
        $order->number       = $order->getOrderNumber();
        $order->store_id     = $room->store_id;
        $order->room_id      = $room_id;
        $order->room_type_id = $room->room_type_id;
        $order->employee_id  = $this->employee->id;
//        $order->employee_id   = 118;
        $order->resident_id = $resident_id;
        $order->customer_id = $resident->customer_id;
        $order->uxid        = $resident->uxid;
        $order->money       = $money;
        $order->paid        = $money;
        $order->year        = $year;
        $order->month       = $month;
        $order->type        = $type;
        $order->status      = Ordermodel::STATE_PENDING;
        $order->data        = array_merge((array) $order->data, [date('Y-m-d H:i:s', time()) => $this->employee->name . '通过后台添加了账单']);
        if ($order->save()) {
            $this->api_res(0, ['order_id' => $order->id]);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 通过门店和房间号获取住户和房间信息
     */
    public function getResidentByRoom() {
        $input       = $this->input->post(null, true);
        $room_number = $this->input->post('room_number', true);
        $store_id    = $this->input->post('store_id', true);
        if (empty($store_id) || empty($room_number)) {
            $this->api_res(10032);
            return;
        }
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $room = Roomunionmodel::with('resident', 'store')
            ->where(['store_id' => $store_id, 'number' => $room_number])
            ->first();
        if (empty($room)) {
            $this->api_res(10032);
            return;
        }
        if (empty($room->resident)) {
            $this->api_res(10033);
            return;
        }

        $this->api_res(0, [$room]);
    }

    /**
     * 编辑账单
     */
    public function editOrder() {
        $input = $this->input->post(null, true);
        $field = ['order_id', 'money', 'remark'];
        if (!$this->validationText($this->validateEdit())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $order_id = $input['order_id'];
        $money    = $input['money'];
        $remark   = $input['remark'];
        $this->load->model('ordermodel');
        $order = Ordermodel::whereIn('status', [Ordermodel::STATE_GENERATED, Ordermodel::STATE_PENDING, Ordermodel::STATE_AUDITED])
            ->findOrFail($order_id);

        $order->employee_id = $this->employee->id;
        $order->money       = $money;
        $order->paid        = $money;
        $order->remark      = $remark;
        $order->data        = array_merge((array) $order->data, [date('Y-m-d H:i:s', time()) => $this->employee->name . '修改了账单，' . '修改原因：' . $remark]);

        if ($order->save()) {
            $this->api_res(0, ['order_id' => $order->id]);
        } else {
            $this->api_res(1009);
        }

    }

    /**
     *  关闭账单
     */
    public function closeOrder()
    {
        $order_id   = $this->input->post('order_id',true);
        $order  = Ordermodel::find($order_id);
        if (empty($order)) {
            $this->api_res(1007);
            return;
        }
        if (!in_array($order->status,[Ordermodel::STATE_GENERATED,Ordermodel::STATE_PENDING])) {
            $this->api_res(10042);
            return;
        }

        $order->status  = Ordermodel::STATE_CLOSE;
        $order->employee_id = $this->employee->id;
        if ($order->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    private function validateStore() {
        return array(
            array(
                'field' => 'room_id',
                'label' => '房间id',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'resident_id',
                'label' => '住户id',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'year',
                'label' => '账单周期--年',
                'rules' => 'required|trim|integer|greater_than[2000]|less_than[2030]',
            ),
            array(
                'field' => 'month',
                'label' => '账单周期--月',
                'rules' => 'required|trim|integer|greater_than[1]|less_than[12]',
            ),
            array(
                'field' => 'type',
                'label' => '账单类型',
                'rules' => 'required|trim|in_list[ROOM,DEIVCE,REFUND,DEPOSIT_R,DEPOSIT_O,MANAGEMENT,OTHER,RESERVE,CLEAN,WATER,ELECTRICITY,COMPENSATION,REPAIR,HOT_WATER,OVERDUE]',
            ),
            array(
                'field' => 'money',
                'label' => '账单金额',
                'rules' => 'required|trim|numeric',
            ),
        );

    }

    private function validateEdit() {
        return array(
            array(
                'field' => 'money',
                'label' => '账单金额',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'order_id',
                'label' => '账单id',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'remark',
                'label' => '修改原因',
                'rules' => 'required|trim',
            ),
        );

    }

    /**
     * 推送账单
     */
    public function push() {
        $input    = $this->input->post(null, true);
        $year     = $input['year'];
        $month    = $input['month'];
        $store_id = $input['store_id'];
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $unPushOrders = Ordermodel::where('store_id', $store_id)
            ->where('status', Ordermodel::STATE_GENERATED)
            ->where(function ($query) use ($year, $month) {
                $query->where(function ($a) use ($year, $month) {
                    $a->where('year', $year)->where('month', '>=', $month);
                })->orWhere('year', '>', $year);
            })
            ->get()->groupBy('resident_id');
//        var_dump($unPushOrders->toArray());exit;
        $this->load->helper('common');
        $app = new Application(getWechatCustomerConfig());
        foreach ($unPushOrders as $resident_id => $orders) {
            $resident = Residentmodel::find($resident_id);
            if (empty($resident)) {
                log_message('error', $resident_id . '没有住户resident信息');
                continue;
            }
            $customer = $resident->customer;
            if (empty($customer)) {
                log_message('error', $resident_id . '没有用户customer信息');
                $orders = Ordermodel::where('resident_id', $resident_id)
                    ->where('status', Ordermodel::STATE_GENERATED)
                    ->where(function ($query) use ($year, $month) {
                        $query->where(function ($a) use ($year, $month) {
                            $a->where('year', $year)->where('month', '>=', $month);
                        })->orWhere('year', '>', $year);
                    })
                    ->update(['status' => Ordermodel::STATE_PENDING]);
                continue;
            }
            $amount = $orders->sum('money');
            try {
                DB::beginTransaction();
                $orders = Ordermodel::where('resident_id', $resident_id)
                    ->where('status', Ordermodel::STATE_GENERATED)
                    ->where(function ($query) use ($year, $month) {
                        $query->where(function ($a) use ($year, $month) {
                            $a->where('year', $year)->where('month', '>=', $month);
                        })->orWhere('year', '>', $year);
                    })
                    ->update(['status' => Ordermodel::STATE_PENDING]);

                if (0 == $customer->subscribe) {
                    log_message('error', $resident_id . '未关注公众号，未推送账单');

                } else {
                    $app->notice->uses(config_item('tmplmsg_customer_paynotice'))
                        ->withUrl(config_item('wechat_base_url') . '#/myBill')
                        ->andData([
                            'first'    => '温馨提示, 您有未支付的账单, 如已支付, 请忽略！',
                            'keyword1' => $amount . '元',
                            'keyword2' => '请尽快缴费！',
                            'remark'   => '如有疑问，请与工作人员联系',
                        ])
                        ->andReceiver($customer->openid)
                        ->send();
                    log_message('debug', $resident_id . '推送成功');
                }

                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
        $this->api_res(0);
    }

    /**
     * @param store_id room_id
     * 向住户发送模板消息, 通知缴费
     */
    public function notify() {
        $input    = $this->input->post(null, true);
        $where  = [];
        empty($input['store_id'])?null:$where['store_id']=$input['store_id'];
        empty($input['room_id'])?null:$where['room_id']=$input['room_id'];
        $this->load->helper('common');
        $app    = new Application(getWechatCustomerConfig());
        $failed = [];
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $this->load->model('roomunionmodel');
        if (empty($where)) {
            $this->api_res(1005);
            return;
        }
        $pendingOrders = Ordermodel::where($where)
            ->where('status', Ordermodel::STATE_PENDING)
//            ->where('is_notify', 0)
            ->get()
            ->groupBy('resident_id');
        log_message("debug", "准备推送账单 门店: $store_id, 总计: ".count($pendingOrders));
        foreach ($pendingOrders as $resident_id => $orders) {
            $resident = Residentmodel::find($resident_id);
            if (empty($resident)) {
                log_message('error', $resident_id . '没有住户resident信息');
                $failed[] = $resident_id . '没有住户resident信息';
                continue;
            }
            $customer = $resident->customer;
            if (empty($customer) || (0 == $customer->subscribe)) {
                log_message('error', $resident_id . '没有用户customer信息或未关注');
                $failed[] = $resident_id . '没有用户customer信息或未关注';
                continue;
            }

            $amount = $orders->sum('money');

            $app->notice->uses(config_item('tmplmsg_customer_paynotice'))
                ->withUrl(config_item('wechat_base_url') . '#/myBill')
                ->andData([
                    'first'    => '温馨提示, 您有未支付的账单, 如已支付, 请忽略！',
                    'keyword1' => $amount . '元',
                    'keyword2' => '请尽快缴费！',
                    'remark'   => '如有疑问，请与工作人员联系',
                ])
                ->andReceiver($customer->openid)
                ->send();
//            $resident->orders()
//                ->where('status', Ordermodel::STATE_PENDING)
//                ->where('is_notify', 0)
//                ->update(['is_notify' => 1]);
            log_message('debug', $resident_id . '推送成功');
        }
        $this->api_res(0, ['failed' => $failed]);
    }

    public function orderToExcel() {
        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $input                                          = $this->input->post();
        $where                                          = [];
        $year                                           = [];
        $month                                          = [];
        empty($input['store_id']) ?: $where['store_id'] = $input['store_id'];
        empty($input['startYear']) ?: $year['startYear']         = $input['startYear'];
        empty($input['startMonth']) ?: $month['startMonth']         = $input['startMonth'];
        empty($input['endYear']) ?: $year['endYear']       = $input['endYear'];
        empty($input['endMonth']) ?: $month['endMonth']       = $input['endMonth'];
        $strat = $year['startYear'].$month['startMonth'];
        $end = $year['endYear'].$month['endMonth'];
        $orders      = DB::select("select date_format(str_to_date(CONCAT(o.year,'/', o.month), ?), ?) as b , r.`number` as `rnumber`,
         s.`name` as `sname` , o.`type`, o.`paid`, o.`money`, o.`status`, o.`pay_date`, o.`discount_money`, o.`remark`".
        " from boss_order as o ".
        " left join `boss_room_union` as r on o.`room_id` = r.`id`".
        " left join `boss_resident` as s on o.`resident_id` = s.`id`".
        " where (o.store_id = ?) having b >= ?".
        " and b <= ? ",['%Y/%m','%Y%m' , $where['store_id'], $strat, $end]);
        $store       = Storemodel::where('id', $where['store_id'])->first();
        $store       = $store->name;
        $order_excel = [];

        foreach ($orders as $order) {
            $res             = [];
            $res['number']   = $order->rnumber;
            $res['name']     = isset($order->sname) ? $order->sname : '';
            $res['type']     = $order->type;
            $res['paid']     = $order->paid;
            $res['money']    = $order->money;
            $res['status']   = $order->status;
            $res['pay_date'] = $order->pay_date;
            $res['discount'] = $order->discount_money;
            $res['remark']   = $order->remark;
            $order_excel[]   = $res;

        }
        $filename = date('Y-m-d-H:i:s') . '导出' .$year['startYear']. '-' .$month['startMonth'] .'至'.$year['endYear']. '-' .$month['endMonth'] .'_账单数据.Xlsx';
        $row      = count($order_excel) + 3;
        $phpexcel = new Spreadsheet();
        $sheet    = $phpexcel->getActiveSheet();
        $this->createPHPExcel($phpexcel, $filename); //创建excel
        $this->setExcelTitle($phpexcel, $store, $year, $month); //设置表头
        $this->setExcelFirstRow($phpexcel); //设置各字段名称
        $sheet->fromArray($order_excel, null, 'A4'); //想excel中写入数据
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
    private function setExcelTitle(Spreadsheet $phpexcel, $store, $year, $month) {
        $phpexcel->getActiveSheet()
            ->mergeCells('A1:O2')
            ->setCellValue('A1', "$store" . $year['startYear']. '-' .$month['startMonth'] .'至'.$year['endYear']. '-' .$month['endMonth'] . '账单统计')
            ->getStyle("A1:O2")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $phpexcel->getActiveSheet()->getCell('A1')->getStyle()->getFont()->setSize(16);
    }

    private function setExcelColumnWidth(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(22);
    }
    private function setAlignCenter(Spreadsheet $phpexcel, $row) {
        $phpexcel->getActiveSheet()
            ->getStyle("A3:N{$row}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    private function setExcelFirstRow(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->setCellValue('A3' , '房间号')
        ->setCellValue('B3' , '姓名')
        ->setCellValue('C3' , '订单类型')
        ->setCellValue('D3' , '实付金额')
        ->setCellValue('E3' , '支付金额')
        ->setCellValue('F3' , '支付状态')
        ->setCellValue('G3' , '支付时间')
        ->setCellValue('H3' , '优惠金额')
        ->setCellValue('I3' , '备注');
    }
    /**
     * 确认账单（审核通过）
     * 考虑到有支付周期大于1的账单，需要一起推送当前月和之后月份的账单
     * 参数 store_id, year, month
     */
    public function approve()
    {
        $input    = $this->input->post(null, true);
        $year     = $input['year'];
        $month    = $input['month'];
        $store_id = $input['store_id'];
        Ordermodel::where('store_id', $store_id)
            ->where('status', Ordermodel::STATE_GENERATED)
            ->where(function ($query) use ($year, $month) {
                $query->where(function ($a) use ($year, $month) {
                    $a->where('year', $year)->where('month', '>=', $month);
                })->orWhere('year', '>', $year);
            })
            ->update(['status'=>Ordermodel::STATE_PENDING]);
        $this->api_res(0);
    }

    private function payStatus($status)
    {
        switch ($status)
        {
            case Ordermodel::STATE_GENERATED :
                $pay_status = '生成账单';
                break;
            case Ordermodel::STATE_AUDITED   :
                $pay_status = '生成账单';
                break;
            case Ordermodel::STATE_PENDING   :
                $pay_status = '等待付款';
                break;
            case Ordermodel::STATE_CONFIRM   :
                $pay_status = '付款等待确认';
                break;
            case Ordermodel::STATE_COMPLETED :
                $pay_status = '   完成';
                break;
            case Ordermodel::STATE_REFUND    :
                $pay_status = '   退单';
                break;
            case Ordermodel::STATE_EXPIRE    :
                $pay_status = '   过期';
                break;
            case Ordermodel::STATE_CLOSE     :
                $pay_status = '   关闭';
                break;
            default :
                $pay_status = '';
                break;
        }
        return $pay_status;
    }

    private function payDate($date)
    {
        if (substr($date,0,4) == '1970'){
            return $time = '';
        }
        else{
            return $date;
        }
    }
    
}
