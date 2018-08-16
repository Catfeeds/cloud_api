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
                'rules' => 'required|trim|in_list[ROOM,DEIVCE,UTILITY,REFUND,DEPOSIT_R,DEPOSIT_O,MANAGEMENT,OTHER,RESERVE,CLEAN,WATER,ELECTRICITY,COMPENSATION,REPAIR,HOT_WATER,OVERDUE]',
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
     * 向住户发送模板消息, 通知缴费
     */
    public function notify() {
        $input    = $this->input->post(null, true);
        $store_id = $input['store_id'];
        $this->load->helper('common');
        $app    = new Application(getWechatCustomerConfig());
        $failed = [];
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $this->load->model('roomunionmodel');
        $pendingOrders = Ordermodel::where('store_id', $store_id)
            ->where('status', Ordermodel::STATE_PENDING)
//            ->where('is_notify', 0)
            ->get()
            ->groupBy('resident_id');

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
        empty($input['store_id']) ?: $where['store_id'] = $input['store_id'];
        empty($input['year']) ?: $where['year']         = $input['year'];
        empty($input['month']) ?: $where['month']       = $input['month'];
        $filed                                          = ['id', 'room_id', 'resident_id', 'money', 'paid', 'type', 'status', 'pay_date',
            'remark', 'discount_money'];
        $orders      = Ordermodel::with('roomunion')->with('resident')->where($where)->get($filed);
        $store       = Storemodel::where('id', $where['store_id'])->first();
        $store       = $store->name;
        $order_excel = [];
        foreach ($orders as $order) {
            $res             = [];
            $res['number']   = $order->roomunion->number;
            $res['name']     = isset($order->resident->name) ? $order->resident->name : '';
            $res['type']     = $order->type;
            $res['paid']     = $order->paid;
            $res['money']    = $order->money;
            $res['status']   = $order->status;
            $res['pay_date'] = $order->pay_date;
            $res['discount'] = $order->discount_money;
            $res['remark']   = $order->remark;
            $order_excel[]   = $res;

        }
        $objPHPExcel = new Spreadsheet();
        $sheet       = $objPHPExcel->getActiveSheet();
        $i           = 1;
        $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, '房间号');
        $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, '姓名');
        $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, '起始日');
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, '届满日');
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, '支付类型');
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, '支付金额');
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, '定价');
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, '支付状态');
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $i, '支付时间');
        $objPHPExcel->getActiveSheet()->setCellValue('J' . $i, '优惠金额');
        $objPHPExcel->getActiveSheet()->setCellValue('K' . $i, '备注');
        $sheet->fromArray($order_excel, null, 'A2');
        $writer = new Xlsx($objPHPExcel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=$store" . $where['year'] . '年' . $where['month'] . '月' . ".'.xlsx'");
        header("Content-Transfer-Encoding:binary");
        $writer->save('php://output');
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
