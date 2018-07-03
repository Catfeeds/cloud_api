<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Database\Capsule\Manager as DB;
use EasyWeChat\Foundation\Application;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        17:17
 * Describe:    订单表 一个订单分为多个流水
 */
class Order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ordermodel');
    }

    /**
     * BOSS端订单列表
     */
    public function listOrder()
    {
        $input  = $this->input->post(null,true);
        $where  = [];
        empty($input['store_id'])?:$where['store_id']=$input['store_id'];
        empty($input['type'])?:$where['type']=$input['type'];

        empty($input['year'])?:$where['year']=$input['year'];
        empty($input['month'])?:$where['month']=$input['month'];
        $search = empty($input['search'])?'':$input['search'];
        $page   = isset($input['page'])?$input['page']:1;
        $offset = ($page-1)*PAGINATE;
        $status = $input['status'];
        if($status=='PAY')
        {
            $status = [Ordermodel::STATE_COMPLETED];
        }elseif($status=='NOTPAY')
        {
            $status = array_diff($this->ordermodel->getAllStatus(),[Ordermodel::STATE_COMPLETED]);
        }else{
            $status = $this->ordermodel->getAllStatus();
        }

        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');

        $count  = ceil((Ordermodel::with('store','roomunion','resident','employee')
                ->where(function ($query) use ($search){
                    $query->orWhereHas('resident',function($query) use($search){
                        $query->where('name','like',"%$search%");
                    })->orWhereHas('roomunion',function($query) use($search){
                        $query->where('number','like',"%$search%");
                    })->orWhereHas('employee',function($query) use($search){
                        $query->where('name','like',"%$search%");
                    });
                })
                ->where($where)
                ->whereIn('status',$status)
                ->count())/PAGINATE);

        if($count<$page){
            $this->api_res(0,['orders'=>[],'total_page'=>$count]);
            return;
        }

        $orders = Ordermodel::with(['store','roomunion','resident','employee'])
            ->where(function ($query) use ($search){
                $query->orWhereHas('resident',function($query) use($search){
                    $query->where('name','like',"%$search%");
                })->orWhereHas('roomunion',function($query) use($search){
                    $query->where('number','like',"%$search%");
                })->orWhereHas('employee',function($query) use($search){
                    $query->where('name','like',"%$search%");
                });
            })
            ->where($where)
            ->whereIn('status',$status)
            ->orderBy('created_at','DESC')
            ->orderBy('room_id','ASC')
            ->offset($offset)->limit(PAGINATE)
            ->get()->toArray();


        $this->api_res(0,['orders'=>$orders,'total_page'=>$count]);

    }

    public function download()
    {
        $input  = $this->input->post(null,true);

        $orders = Ordermodel::get()->toArray();

        var_dump($orders);




    }

    private function createPHPExcel($filename)
    {
        $phpexcel = new Spreadsheet();
        $phpexcel->getProperties()
            ->setCreator('梵响互动')
            ->setLastModifiedBy('梵响互动')
            ->setTitle($filename)
            ->setSubject($filename)
            ->setDescription($filename)
            ->setKeywords($filename)
            ->setCategory($filename);
        $phpexcel->setActiveSheetIndex(0);

        return $phpexcel;
    }

    private function setExcelTitle(Spreadsheet $phpexcel, $apartment, $start, $end)
    {
        $phpexcel->getActiveSheet()
            ->mergeCells('A1:N2')
            ->setCellValue('A1', $apartment->name . ' 订单流水统计' . $start .' - ' . $end)
            ->getStyle('A1')
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $phpexcel->getActiveSheet()->getCell('A1')->getStyle()->getFont()->setSize(16);
    }

    private function setExcelFirstRow(Spreadsheet $phpexcel)
    {
        $phpexcel->getActiveSheet()
            ->setCellValue('A3', '订单月份')
            ->setCellValue('B3', '确认时间')
            ->setCellValue('C3', '房号')
            ->setCellValue('D3', '住户')
            ->setCellValue('E3', '缴费金额（元）')
            ->setCellValue('F3', '住宿服务费（元）')
            ->setCellValue('G3', '物业服务费（元）')
            ->setCellValue('H3', '水电费（元）')
            ->setCellValue('I3', '物品租赁（元）')
            ->setCellValue('J3', '其他收费（元）')
            ->setCellValue('K3', '住宿押金（元）')
            ->setCellValue('L3', '其他押金（元）')
            ->setCellValue('M3', '缴费方式')
            ->setCellValue('N3', '备注');
    }

    /**
     * boss端创建账单
     */
    public function addOrder()
    {
        $field  = ['room_id','resident_id','month','year','type','money'];
        if(!$this->validationText($this->validateStore()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $input  = $this->input->post();
        $room_id    = $input['room_id'];
        $resident_id    = $input['resident_id'];
        $month    = $input['month'];
        $year    = $input['year'];
        $type    = $input['type'];
        $money    = $input['money'];
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
//        $this->load->model('storemodel');
//        $this->load->model('roomtypemodel');
        $room   = Roomunionmodel::where('resident_id',$resident_id)->findOrFail($room_id);
        $resident   = $room->resident;
        $this->load->model('ordermodel');
        $order  = new Ordermodel();
        $order->number  = $order->getOrderNumber();
        $order->store_id   = $room->store_id;
        $order->room_id   = $room_id;
        $order->room_type_id   = $room->room_type_id;
        $order->employee_id   = $this->employee->id;
//        $order->employee_id   = 118;
        $order->resident_id   = $resident_id;
        $order->customer_id   = $resident->customer_id;
        $order->uxid   = $resident->uxid;
        $order->money   = $money;
        $order->paid   = $money;
        $order->year   = $year;
        $order->month   = $month;
        $order->type   = $type;
        $order->status   = Ordermodel::STATE_PENDING;
        $order->data   = array_merge((array)$order->data,[date('Y-m-d H:i:s',time())=>$this->employee->name.'通过后台添加了账单']);
        if($order->save()){
            $this->api_res(0,['order_id'=>$order->id]);
        }else {
            $this->api_res(1009);
        }
    }

    /**
     * 通过门店和房间号获取住户和房间信息
     */
    public function getResidentByRoom()
    {
        $input  = $this->input->post(null,true);
        $room_number    = $this->input->post('room_number',true);
        $store_id       = $this->input->post('store_id',true);
        if(empty($store_id) || empty($room_number))
        {
            $this->api_res(10032);
            return;
        }
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $room   = Roomunionmodel::with('resident','store')
            ->where(['store_id'=>$store_id,'number'=>$room_number])
            ->first();
        if(empty($room)){
            $this->api_res(10032);
            return;
        }
        if(empty($room->resident)){
            $this->api_res(10033);
            return;
        }

        $this->api_res(0,[$room]);
    }

    /**
     * 编辑账单
     */
    public function editOrder()
    {
        $input  = $this->input->post(null,true);
        $field  = ['order_id','money','remark'];
        if(!$this->validationText($this->validateEdit())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $order_id    = $input['order_id'];
        $money       = $input['money'];
        $remark      = $input['remark'];
        $this->load->model('ordermodel');
        $order  = Ordermodel::whereIn('status',[Ordermodel::STATE_GENERATED,Ordermodel::STATE_PENDING,Ordermodel::STATE_AUDITED])
            ->findOrFail($order_id);

        $order->employee_id = $this->employee->id;
        $order->money   = $money;
        $order->paid    = $money;
        $order->remark  = $remark;
        $order->data   = array_merge((array)$order->data,[date('Y-m-d H:i:s',time())=>$this->employee->name.'修改了账单，'.'修改原因：'.$remark]);

        if($order->save()){
            $this->api_res(0,['order_id'=>$order->id]);
        }else{
            $this->api_res(1009);
        }

    }


    private function validateStore()
    {
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

    private function validateEdit()
    {
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
    public function push()
    {
        $input = $this->input->post(null,true);
        $store_id = $input['store_id'];
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $this->load->model('customermodel');
        $unPushOrders = Ordermodel::where('store_id', $store_id)
            ->where('status', Ordermodel::STATE_GENERATED)->get()->groupBy('resident_id');

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
                continue;
            }
            $amount = $orders->sum('money');
            try {
                DB::beginTransaction();
                $orders = Ordermodel::where('resident_id', $resident_id)
                    ->where('status', Ordermodel::STATE_GENERATED)
                    ->update(['status'=> Ordermodel::STATE_PENDING]);

                $app->notice->uses(config_item('tmplmsg_customer_paynotice'))
                    ->withUrl(config_item('wechat_base_url') . '#/myBill')
                    ->andData([
                        'first' => '温馨提示, 您有未支付的账单, 如已支付, 请忽略！',
                        'keyword1' => $amount . '元',
                        'keyword2' => '请尽快缴费！',
                        'remark' => '如有疑问，请与工作人员联系',
                    ])
                    ->andReceiver($customer->openid)
                    ->send();
                log_message('error',$resident_id.'推送成功');
                DB::commit();

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
        $this->api_res(0);
    }

    public function notify()
    {
        $residentIds    = $this->input->post('ids[]', true);
        $year           = $this->checkAndGetYear();
        $month          = $this->checkAndGetMonth();
        $apartmentId    = $this->apartmentIdFilter();
        $app            = new Application(getCustomerWechatConfig(false));
        $failed         = [];

        try {
            if (!is_array($residentIds) || 0 == count($residentIds)) {
                throw new Exception('请至少选择一笔账单！');
            }

            Ordermodel::with(['resident', 'room'])
                ->whereIn('resident_id', $residentIds)
                ->where('year', $year)
                ->where('month', $month)
                ->whereIn('status', Ordermodel::unpaidStatuses())
                ->get()
                ->groupBy('resident_id')->each(function ($items) use (&$failed, $app) {
                    $resident = $items->first()->resident;
                    $amount   = $items->sum('money');

                    try {
                        if (0 == $resident->customer_id) {
                            throw new Exception('未关联微信帐号');
                        }

                        $customer = $resident->customer;

                        if (0 == $customer->subscribe) {
                            throw new Exception('未关注公众帐号');
                        }
                    } catch (Exception $e) {
                        $reason = $e->getMessage();
                        $room   = $items->first()->room;
                        array_push($failed, "{$room->number} 房间 - {$resident->name} 由于 {$reason} 无法推送！");
                        return true;
                    }

                    Ordermodel::whereIn('id', $items->pluck('id')->toArray())->update([
                        'customer_id'   => $resident->customer_id,
                        'status'        => Ordermodel::STATE_PENDING,
                    ]);

                    $app->notice->uses(TMPLMSG_CUSTOMER_PAYNOTICE)
                        ->withUrl(wechat_url('order/status'))
                        ->andData([
                            'first'     => '温馨提示, 您有未支付的账单, 如已支付, 请忽略！',
                            'keyword1'  => $amount.'元',
                            'keyword2'  => '请尽快缴费！',
                            'remark'    => '如有疑问，请与工作人员联系',
                        ])
                        ->andReceiver($customer->openid)
                        ->send();
                });
        } catch (Exception $e) {
            Util::error($e->getMessage());
        }

        if (count($failed)) {
            log_message('error', json_encode($failed));
            Util::error('error', ['info' => $failed]);
        }

        Util::success('发送成功!');
    }


}
