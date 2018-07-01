<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        15:40
 * Describe:
 */
class Meter extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('meterreadingtransfermodel');
    }

    /**
     * 确定读数的上传并生成账单
     */
    public function confirm()
    {

        $field  = ['month','year','type'];

        $input  = $this->input->post(null,true);

        if(!$this->validationText($this->validateConfirm())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }

//        if($this->employee->position!='APARTMENT'){
//            $this->api_res(1011);
//            return;
//        }


        $month  = $this->checkAndGetMonth($input['month'],false);
        $year   = $this->checkAndGetYear($input['year'],false);

        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('utilityreadingmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $this->load->model('meterreadingmodel');
        try {

            DB::beginTransaction();

            $type   = $input['type'];

            $transfers = Meterreadingtransfermodel::with('roomunion')
                ->where('type', $type)
//                ->where('store_id',1)
//                ->where('store_id', $this->employee->store_id)
                ->where('store_id', $this->input->post('store_id',true))
                ->where('confirmed', Meterreadingtransfermodel::UNCONFIRMED)
                ->get();


            $transfers->map(function ($transfer) use ($year, $month) {
                if (0.01 > $transfer->this_reading - $transfer->last_reading) return true;

                if (0 == $transfer->roomunion->resident_id) {
                    return false;
                }

                $order  = $this->addUtilityOrder($transfer, $year, $month);
                $record = $this->logMeterReading($transfer);

                $this->recordUtilityReadings($order, $transfer);

                $transfer->confirmed = !$transfer->confirmed;
                $transfer->save();

                return true;
            });

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }


    /**
     * 生成水电订单
     */
    private function addUtilityOrder($transfer, $year, $month)
    {
        $roomunion = $transfer->roomunion;

        if (!$resident = $roomunion->resident) {
            return null;
        }

        switch ($transfer->type) {
            case Meterreadingtransfermodel::TYPE_ELECTRIC :
                $type   = Ordermodel::PAYTYPE_ELECTRIC;
                // $price  = $resident->electricity_price;
                $price  = $roomunion->store->electricity_price;
                break;
            case Meterreadingtransfermodel::TYPE_WATER_H :
                $type   = Ordermodel::PAYTYPE_WATER_HOT;
                $price  = $roomunion->store->hot_water_price;
                break;
            case Meterreadingtransfermodel::TYPE_WATER_C :
                $type   = Ordermodel::PAYTYPE_WATER;
                $price  = $roomunion->store->water_price;
                break;
            default:
                throw new Exception('未识别的账单类型！');
                break;
        }

        $money = ($transfer->this_reading - $transfer->last_reading) * $price;

        if (0.01 > $money) {

            return null;
        }

        //分进角，比如 1.01 元，计为 1.1 元
        $money = ceil($money * $transfer->weight / 10) / 10;

        $this->load->helper('string');
        $order = new Ordermodel();
        $order->fill([
            'number'        => date('YmdHis').random_string('numeric', 10),
            'type'          => $type,
            'year'          => $year,
            'month'         => $month,
            'money'         => $money,
            'paid'          => $money,
            'store_id    '  => $roomunion->store_id,
            'resident_id'   => $roomunion->resident_id,
            'room_id'       => $roomunion->id,
            'customer_id'   => $roomunion->resident->customer_id,
            'uxid'          => $roomunion->resident->uxid,
            'room_type_id'  => $roomunion->room_type_id,
            'status'        => Ordermodel::STATE_GENERATED,
            'deal'          => Ordermodel::DEAL_UNDONE,
            'pay_status'    => Ordermodel::PAYSTATE_RENEWALS,
        ]);


        $order->save();



        return $order;
    }


    /**
     * 记录表读数
     */
    private function logMeterReading($transfer)
    {
        $record = new Meterreadingmodel();
        $record->room_id    = $transfer->room_id;
        $record->type       = $transfer->type;
        $record->reading    = $transfer->last_reading;
        $record->save();

        return $record;
    }

    /**
     * 记录水电账单的读数
     */
    private function recordUtilityReadings($order, $transfer)
    {
        if (!$order) {
            return null;
        }

        $record = new Utilityreadingmodel();
        $record->fill([
            'start_reading' => $transfer->last_reading,
            'end_reading'   => $transfer->this_reading,
            'weight'        => $transfer->weight,
            'order_id'      => $order->id,
        ]);
        $record->save();

        return $record;
    }

    private function validateConfirm(){

        return array(

            array(
                'field' => 'type',
                'label' => '费用类型',
                'rules' => 'required|trim|in_list[HOT_WATER_METER,COLD_WATER_METER,ELECTRIC_METER]',
            ),
            array(
                'field' => 'year',
                'label' => '年',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'month',
                'label' => '月',
                'rules' => 'required|trim',
            ),
        );
    }

    /**
     * 上传读数
     */
    public function import()
    {
        $this->load->model('meterreadingmodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('buildingmodel');

        $type       = $this->input->post('type');
        $store_id   = $this->input->post('store_id');
        $type       = $this->checkAndGetReadingType($type);
        $sheetArray   = $this->uploadOssSheet();
        $data       = $this->checkAndGetInputData($sheetArray,$store_id);
        if(!empty($data['error'])){
            $this->api_res(10052,['error'=>$data['error']]);
            return;
        }

        $c  = $this->writeReading($data, $type);
        if(!empty($c['error'])){
            $this->api_res(10051,['error'=>$c['error']]);
            return;
        }

        $this->api_res(0);
    }

    /**
     * 处理上传的记录
     */
    private function writeReading($data = [], $type)
    {
        $transfers = [];

        foreach ($data as $item) {
            $room       = $item['room'];
            $transfer   = $room->meterreadingtransfer->where('type', $type)->first();

            if (count($transfer) && 0.01 <= $transfer->last_reading - $item['read']) {
                $data   = ['error'=>'错误：房间 ' . $room->number . ' 新导入读数低于上次记录!'];
                return $data;
            }

            //新读数
            if ($transfer && $transfer->confirmed) {

                $transfer->last_reading = $transfer->this_reading;
                $transfer->confirmed    = !$transfer->confirmed;
            } elseif (!$transfer) {
                $transfer   = new Meterreadingtransfermodel();
                $transfer->room_id      = $room->id;
                $transfer->building_id  = $room->building_id;
                $transfer->store_id     = $room->store_id;
                $transfer->last_reading = $item['read'];
                $transfer->type         = $type;
            }

            $transfer->weight = $item['weight'];
            $transfer->this_reading = $item['read'];
            $transfers[]    = $transfer;
        }
        foreach ($transfers as $transfer) {
            $transfer->save();
        }

        return true;
    }

    /**
     * 处理文件中上传的数据
     */
    private function checkAndGetInputData($sheetArray,$store_id)
    {
        $rooms  = Roomunionmodel::with('meterreadingtransfer')->where('store_id',$store_id)->get();
        $store  = Storemodel::find($store_id);
        $buildings  = $store->building;
        $buildCount = count($buildings);
        $building   = $buildings->first();

        $data   = [];

        $error  = '';

        foreach ($sheetArray as $key => $item)  {
            if (0 == $key || !$item[0] || !$item[1]) continue;

            $read   = trim($item[2]);

            if (!is_numeric($read) || 0 > $read || 1e8 < $read) {
                $error  = '请检查房间：' . $item[1] . '的表读数';
                return ['error'=>$error];
            }

            if (1 < $buildCount) {
                if (!isset($item[3])) {
                    $error  = '请检查楼幢 id';
                    return ['error'=>$error];
                }

                $buildingId = (int) trim($item[3]);
            } else {
                $buildingId = $building->id;
            }

            $room   = $rooms->where('number', strtoupper($item[1]))->where('building_id', $buildingId)->first();

            if (!$room) {

                $error  = '未找到房间：' ."$item[1]";
                return ['error'=>$error];
            }

            $weight = isset($item[4]) ? (int) $item[4] : 100;

            if (!$weight) {
                $weight = 100;
            } elseif (100 < $weight || 0 > $weight) {

                $error  = '请检查房间：' . $item[1] . '的均摊比例';
                return ['error'=>$error];
            }

            $data[] = ['read' => $read, 'room' => $room, 'weight' => $weight];
        }

        return $data;
    }

    /**
     * 检查表计读数类型
     */
    public function checkAndGetReadingType($type)
    {
        if (!in_array($type, [
            Meterreadingtransfermodel::TYPE_WATER_H,
            Meterreadingtransfermodel::TYPE_WATER_C,
            Meterreadingtransfermodel::TYPE_ELECTRIC,
        ])) {
            throw new Exception('表计类型值不正确！');
        }

        return $type;
    }


    private function uploadOssSheet(){

        $url    = $this->input->post('url');
        $f_open = fopen($url,'r');
        $file_name  = APPPATH.'cache/test.xlsx';
        file_put_contents($file_name,$f_open);
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file_name);
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($file_name);
        $sheet  = $excel->getActiveSheet();
        return $sheet->toArray();

    }


    /**
     * 处理文件的上传
     */
    private function uploadExcel()
    {
        $this->load->library('excel', [
            'allowed_types' => 'xls|xlsx',
            'max_size'  => 40*1024,
        ]);

        if(!$this->excel->do_upload('file')){

            $this->api_res(1004,array('error' => $this->excel->display_errors('','')));
            return;
        }else {
            //var_dump($this->excel->excel);
            $sheet  = $this->excel->excel->getActiveSheet();

        }
        return ($sheet->toArray());
    }

    /**
     * 输出水电表excel模板
     */
    public function outputTemplate(){
//
//        $spreadsheet    = new Spreadsheet();
//        $sheet  = $spreadsheet->getActiveSheet();
//        $data   = ['序号','房间号','起始读数','楼幢ID','均摊百分比'];
//        $sheet->fromArray($data,null,'A1');
//        $writer = new Xlsx($spreadsheet);
//
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="meterReadingTemplate.xlsx"');
        header("Content-Transfer-Encoding:binary");

        $file=file_get_contents('http://api.boss.strongberry.cn/水电读数导入模板.xlsx');

        echo $file;
//        $writer->save('php://output');
//        $this->api_res(0,['url'=>'http://api.boss.strongberry.com/水电读数导入模板.xlsx']);
    }

}