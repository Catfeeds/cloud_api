<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/9
 * Time:        15:06
 * Describe:    住户
 */
class Resident extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('residentmodel');
    }

    /**
     * 展示住户列表
     */
    public function showResident() {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('customermodel');
        $post      = $this->input->post(null, true);
        $page      = isset($post['page']) ? intval($post['page']) : 1;
        $offset    = PAGINATE * ($page - 1);
        $filed     = ['id', 'name', 'customer_id', 'phone', 'room_id', 'card_number', 'created_at', 'status'];
        $where     = [];
        $store_ids = explode(',', $this->employee->store_ids);
        if (!empty($post['store_id'])) {$where['store_id'] = intval($post['store_id']);};
        if (!empty($post['name'])) {$search = trim($post['name']);} else { $search = '';};
        $count = $count = ceil(Residentmodel::whereIn('store_id', $store_ids)
                ->where($where)->where(function ($query) use ($search) {
                    $query->orwhere('name', 'like', "%$search%")
                        ->orWhereHas('roomunion', function ($query) use ($search) {
                            $query->where('number', 'like', "%$search%");
                        });
                })->count() / PAGINATE);
        if ($page > $count || $page < 1) {
            $this->api_res(0, ['list' => []]);
            return;
        } else {
            $resident = Residentmodel::with('room')->with('customer_s')->whereIn('store_id', $store_ids)
                ->where($where)->where(function ($query) use ($search) {
                    $query->orwhere('name', 'like', "%$search%")
                        ->orWhereHas('roomunion', function ($query) use ($search) {
                            $query->where('number', 'like', "%$search%");
                        });
                })->orderBy('created_at', 'DESC')
                ->take(PAGINATE)->skip($offset)->get($filed)
                ->map(function ($s) {
                    $s->room->store_name = (Storemodel::where('id', $s->room->store_id)->get(['name']))[0]['name'];
                    $s->createdat        = date('Y-m-d', strtotime($s->created_at->toDateTimeString()));
                    return $s;
                })->toArray();
            $this->api_res(0, ['list' => $resident, 'count' => $count]);
        }
    }

    /**
     * 住户基本信息
     */
    public function residentInfo() {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('customermodel');
        $post = $this->input->post(null, true);
        if (isset($post['id'])) {
            $resident_id = intval($post['id']);
            $filed       = ['id', 'name', 'customer_id', 'phone', 'card_type', 'card_number', 'card_one', 'card_two', 'card_three', 'alternative', 'alter_phone'];
            $resident    = Residentmodel::with('customer_s')
                ->where('id', $resident_id)->get($filed)
                ->map(function ($s) {
                    $s->card_one = $this->fullAliossUrl($s->card_one);
                    //var_dump($s->card_one);
                    $s->card_two   = $this->fullAliossUrl($s->card_two);
                    $s->card_three = $this->fullAliossUrl($s->card_three);
                    return $s;
                })
                ->toArray();
            $this->api_res(0, $resident);
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * 修改住户信息
     */
    public function updateResident() {
        $this->load->model('customermodel');
        $post        = $this->input->post(null, true);
        $id          = intval($post['id']);
        $customer_id = intval($post['customer_id']);
        if (!$this->validation()) {
            $fieldarr = ['name', 'gender', 'phone', 'card_type', 'card_number', 'card_one', 'card_two', 'card_three', 'alternative', 'alter_phone'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $resident = Residentmodel::findOrFail($id);
        $customer = Customermodel::findOrFail($customer_id);
        $resident->fill($post);
        $card_one             = $this->splitAliossUrl($post['card_one']);
        $resident->card_one   = $card_one;
        $card_two             = $this->splitAliossUrl($post['card_two']);
        $resident->card_two   = $card_two;
        $card_three           = $this->splitAliossUrl($post['card_three']);
        $resident->card_three = $card_three;
        $customer->gender     = $post['gender'];
        $customer->save();
        if ($resident->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 住户合同信息
     */
    public function contract() {
        $this->load->model('roomunionmodel');
        $this->load->model('contractmodel');
        $this->load->model('couponmodel');
        $this->load->model('activitymodel');
        $this->load->model('storemodel');
        $post     = $this->input->post(NULL, true);
        $serial   = intval($post['id']);
        $filed    = ['id', 'contract_id', 'resident_id', 'store_id', 'room_id', 'status', 'created_at'];
        $resident = Contractmodel::where('id', $serial)->with('store')->with('roomunion')->with('residents')->get($filed);
        $this->api_res(0, ['resident' => $resident]);
    }
    /*
     * 住户信息导出
     * */
   public function residentToExcel(){
       $this->load->model('roomunionmodel');
       $this->load->model('storemodel');
       $this->load->model('customermodel');
       $store_id = $this->input->post('store_id');
       $data = $this->input->post('data');
       if(!$store_id || !$data){
           $this->api_res(1002);
           return false;
       }
       $filed     = ['id', 'name', 'customer_id', 'phone', 'room_id', 'card_number', 'created_at', 'status', 'name_two', 'phone_two', 'card_type', 'alternative', 'alter_phone'];
       $store_ids = explode(',', $this->employee->store_ids);
       $resident = Residentmodel::with('room')->with('customer_s')->whereIn('store_id', $store_ids)
           ->where('created_at','>', $data)
           ->where('store_id', $store_id)
           ->orderBy('created_at', 'DESC')->get($filed)
           ->map(function ($s) {
               $s->room->store_name = (Storemodel::where('id', $s->room->store_id)->get(['name']))[0]['name'];
               $s->createdat        = date('Y-m-d', strtotime($s->created_at->toDateTimeString()));
               return $s;
           });
       if(!$resident){
           $this->api_res(1007);
           return false;
       }
       $result = new Residentmodel();
       foreach ($resident as $order) {
           $res                = [];
           $res['name']        = $order->name;
           $res['phone']       = $order->phone;
           $res['card_type']   = $result->is_cardType($order->card_type);
           $res['card_number'] = $order->card_number;
           $res['store_name']  = $order->room->store_name;
           $res['number']      = $order->room->number;
           $res['created_at']  = $order->created_at;
           $res['status']      = $result->is_status($order->status);
           $res['name_two']    = empty($order->alternative) ? '' : $order->alternative;
           $res['phone_two']   = empty($order->alter_phone) ? '' : $order->alter_phone;
           $resident_excel[]   = $res;
           $store              =  $order->room->store_name;
       }
       $filename = date('Y-m-d-H:i:s') . '导出' . $data  . '_住户数据.Xlsx';
       $row      = count($resident_excel) + 3;
       $phpexcel = new Spreadsheet();
       $sheet    = $phpexcel->getActiveSheet();
       $this->createPHPExcel($phpexcel, $filename); //创建excel
       $this->setExcelTitle($phpexcel, $store, $data); //设置表头
       $this->setExcelFirstRow($phpexcel); //设置各字段名称
       $sheet->fromArray($resident_excel, null, 'A4'); //想excel中写入数据
       $this->setExcelColumnWidth($phpexcel); //设置Excel每列宽度
       $this->setAlignCenter($phpexcel, $row); //设置记录值居中
       $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpexcel, 'Xlsx');
       if(!headers_sent()){
           header("Pragma: public");
           header("Expires: 0");
           header("Content-Type:application/octet-stream");
           header("Content-Transfer-Encoding:binary");
           header('Cache-Control: max-age=0');
           header("Content-Disposition:attachment;filename=$filename");
       }
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
    private function setExcelTitle(Spreadsheet $phpexcel, $store, $data) {
        $phpexcel->getActiveSheet()
            ->mergeCells('A1:O2')
            ->setCellValue('A1', "$store" . "$data" . '住户统计')
            ->getStyle("A1:O2")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $phpexcel->getActiveSheet()->getCell('A1')->getStyle()->getFont()->setSize(16);
    }

    private function setExcelColumnWidth(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
    }
    private function setAlignCenter(Spreadsheet $phpexcel, $row) {
        $phpexcel->getActiveSheet()
            ->getStyle("A3:N{$row}")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    private function setExcelFirstRow(Spreadsheet $phpexcel) {
        $phpexcel->getActiveSheet()->setCellValue('A3' , '姓名')
       ->setCellValue('B3' , '联系方式')
       ->setCellValue('C3' , '证件类型')
       ->setCellValue('D3' , '证件号码')
       ->setCellValue('E3' , '门店名称')
       ->setCellValue('F3' , '房间号')
       ->setCellValue('G3' , '创建时间')
       ->setCellValue('H3' , '状态')
       ->setCellValue('I3' , '紧急联系人')
       ->setCellValue('J3' , '紧急联系方式');
    }
    /**
     * 住户账单信息
     */
    public function bill() {
        //账单表
        $this->load->model('ordermodel');
        $this->load->model('testbillmodel');
        $post        = $this->input->post(null, true);
        $resident_id = intval($post['id']);
        $filed       = ['money', 'type'];
        $order       = Ordermodel::where('resident_id', $resident_id)->whereIn('status', ['PENDING', 'AUDITED'])
            ->get($filed)->toArray();
        if (!empty($order)) {
            var_dump($order);
        }
        //流水表
        $bill = Ordermodel::where('resident_id', $resident_id)->whereIn('status', ['COMPLATE', 'CONFIRM'])
            ->get($filed)->toArray();
        $this->api_res(0, ['order' => $order, 'bill' => $bill]);
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
    public function getResidentOrder() {

        $this->load->model('ordermodel');
        $resident_id = $this->input->post('resident_id', true);
        $resident    = Residentmodel::findOrFail($resident_id);
        //未支付的列表
        $unpaid = $resident->orders()
            ->whereIn('status', [Ordermodel::STATE_PENDING, Ordermodel::STATE_GENERATED, Ordermodel::STATE_AUDITED])
            ->orderBy('year', 'DESC')
            ->orderBy('month', 'DESC')
            ->get()
            ->map(function ($order) {
                $order->date = $order->year . '-' . $order->month;
                return $order;
            });
        $paid = $resident->orders()
            ->whereIn('status', [Ordermodel::STATE_CONFIRM, Ordermodel::STATE_COMPLETED])
            ->orderBy('year', 'DESC')
            ->orderBy('month', 'DESC')
            ->get()
            ->map(function ($order) {
                $order->date = $order->year . '-' . $order->month;
                return $order;
            });
        $unpaid_money   = number_format($unpaid->sum('money'), 2, '.', '');
        $paid_money     = number_format($paid->sum('money'), 2, '.', '');
        $discount_money = number_format($paid->sum('discount_money'), 2, '.', '');
        $unpaid         = $unpaid->groupBy('date')->map(function ($unpaid) {
            $a                = [];
            $a['orders']      = $unpaid->toArray();
            $a['total_money'] = number_format($unpaid->sum('money'), 2, '.', '');
            return $a;
        });
        $paid = $paid->groupBy('date')->map(function ($paid) {
            $a                   = [];
            $a['orders']         = $paid->toArray();
            $a['total_money']    = number_format($paid->sum('money'), 2, '.', '');
            $a['total_paid']     = number_format($paid->sum('paid'), 2, '.', '');
            $a['discount_money'] = number_format($paid->sum('discount'), 2, '.', '');
            return $a;
        });
        $this->api_res(0, compact('unpaid_money', 'paid_money', 'discount_money', 'unpaid', 'paid'));
    }
    /*
     * 住户优惠券信息
     * */
    public function getCoupon(){
        $post = $this->input->post(null,true);
        $this->load->model('couponmodel');
        $this->load->model('coupontypemodel');
        $customer = Residentmodel::where('id',$post['resident_id'])->select(['customer_id'])->first();
        $coupon = Couponmodel::with('coupon_type')
            ->where('resident_id',$post['resident_id'])
            ->where('customer_id',$customer->customer_id)
            ->get();

        $this->api_res(0,['coupon' => $coupon]);
    }
    /**
     * @return mixed
     * 表单验证
     */
    private function validation() {
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
        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }
}
