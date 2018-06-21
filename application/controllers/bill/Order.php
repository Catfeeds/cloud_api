<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Style\Alignment;
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
        empty($input['status'])?:$where['status']=$input['status'];
        empty($input['year'])?:$where['year']=$input['year'];
        empty($input['month'])?:$where['month']=$input['month'];
        $search = empty($input['search'])?'':$input['search'];
        $page   = isset($input['page'])?$input['page']:1;
        $offset = ($page-1)*PAGINATE;

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
                })->count())/PAGINATE);

        if($count<$page){
            $this->api_res(0,[]);
            return;
        }

        $orders = Ordermodel::with('store','roomunion','resident','employee')
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
            ->orderBy('created_at','DESC')->offset($offset)->limit(PAGINATE)
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
        $order->room_type_id   = $room->room_type_id;
        $order->employee_id   = $this->employee->id;
        $order->resident_id   = $resident_id;
        $order->customer_id   = $resident->customer_id;
        $order->uxid   = $resident->uxid;
        $order->money   = $money;
        $order->paid   = $money;
        $order->year   = $year;
        $order->month   = $month;
        $order->type   = $type;
        $order->status   = Ordermodel::STATE_PENDING;
        $order->remark   = '后台添加账单';
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
        $store_id       = $this->input->post('store',true);
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


    private function validateStore()
    {
        return array(
            array(
                'field' => 'store_id',
                'label' => '',
                'rules' => '',
            ),
        );

    }



}
