<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/7/31
 * Time:        9:25
 * Describe:
 */
class Meter extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('meterreadingtransfermodel');
    }

/**********************************************************************************/
/***********************************水电逻辑重构*************************************/
/**********************************************************************************/
    //导入非智能表读数
    public function normalDeviceReading()
    {
        $this->load->model('meterreadingmodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $type       = $this->input->post('type');
        $store_id   = $this->input->post('store_id');
        $month      = $this->input->post('month');
        $year      = $this->input->post('year');
        $type       = $this->checkAndGetReadingType($type);                 //检查电表类型
        $sheetArray = $this->uploadOssSheet();                              //转换表读数
        $data = $this->checkAndGetInputData($sheetArray);                   //检查表读数
        if(!empty($data['error'])){
            $this->api_res(10052,['error'=>$data['error']]);
            return;
        }
        $this->writeReading($data,$store_id,$type,$year,$month);            //存储导入数据
        $this->api_res(0);
    }

    /**
     * 检查表计读数类型
     */
    public function checkAndGetReadingType($type) {
        if (!in_array($type, [
            Meterreadingtransfermodel::TYPE_WATER_H,
            Meterreadingtransfermodel::TYPE_WATER_C,
            Meterreadingtransfermodel::TYPE_ELECTRIC,
        ])) {
            throw new Exception('表计类型值不正确！');
        }
        return $type;
    }

    /**
     * 转换表读数为数组
     * @return array
     */
    private function uploadOssSheet() {
        $url       = $this->input->post('url');
        $f_open    = fopen($url, 'r');
        $file_name = APPPATH . 'cache/test.xlsx';
        file_put_contents($file_name, $f_open);
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file_name);
        $reader        = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $excel = $reader->load($file_name);
        $sheet = $excel->getActiveSheet();
        return $sheet->toArray();
    }

    /**
     * 处理文件中上传的数据
     */
    private function checkAndGetInputData($sheetArray) {
        $data       = [];
        $error      = [];
        foreach ($sheetArray as $key => $item) {
            if (0 == $key || !$item[0] || !$item[1]) {
                continue;
            }
            $read = trim($item[2]);
            if (!is_numeric($read) || 0 > $read || 1e8 < $read) {
                $error[] = '请检查房间：' . $item[1] . '的表读数';
                continue;
            }
            $number = $item[1];
            $weight = isset($item[4]) ? (int)$item[4] : 100;
            if (!$weight) {
                $weight = 100;
            } elseif (100 < $weight || 0 > $weight) {
                $error[] = '请检查房间：' . $item[1] . '的均摊比例';
                continue;
            }
            $data[] = ['this_reading' => $read, 'number' => $number, 'weight' => $weight,'this_time' =>$item[5], 'error' => $error];
        }
        return $data;
    }

    /**
     * 处理上传的记录
     */
    private function writeReading($data = [],$store_id,$type,$year,$month) {
        //获取所有房间号(number)
        $number = [];
        foreach ($data as $key=>$value)
        {
            $number[] = $data[$key]['number'];
        }
        //根据房间号获取住户id(resident_id)，房间id(room_id)
        $arr    = Roomunionmodel::where('store_id',$store_id)->whereIn('number',$number)->orderBy('number')
            ->get(['id','number','resident_id','building_id'])->groupBy('number')->toArray();
        //重组插入数据库所需数组
        foreach($data as $key=>$value){
            $data[$key]['resident_id']  = $arr[$data[$key]['number']][0]['resident_id'];
            $data[$key]['room_id']      = $arr[$data[$key]['number']][0]['id'];
            $data[$key]['building_id']  = $arr[$data[$key]['number']][0]['building_id'];
            $data[$key]['month']        = $month;
            $data[$key]['year']         = $year;
            $data[$key]['type']         = $type;
            $data[$key]['store_id']     = $store_id;
            $data[$key]                 = array_except($data[$key],['number','error']);
        }
        Meterreadingtransfermodel::insert($data);
        return true;
    }

/*******************************************************************************************/
/***************************************智能表逻辑*******************************************/
/******************************************************************************************/
    /**
     * 导入智能表读数
     */
    public function smartDeviceUpdate()
    {
        $this->load->model('meterreadingmodel');
        $this->load->model('smartdevicemodel');
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');

        $year       = $this->checkAndGetYear($this->input->post('year'),false);
        $month      = $this->checkAndGetMonth($this->input->post('month'),false);
        $day        = 28;
        $time       = $year.'-'.$month.'-'.$day.'%';
        $res_all    = Meterreadingmodel::where('created_at','like',$time)->get()
                    ->toArray();
        if ($month    == 12){
            $year   = $year + 1;
            $month  = 1;
        }else {
            $month  = $month + 1;
        }
        $this->dealData($res_all,$year,$month);
        $this->api_res(0);
    }

    public function dealData($data,$year,$month)
    {
        $room_ids = [];
        foreach ($data as $key=>$value)
        {
            $room_ids[] = $data[$key]['room_id'];
        }
        $arr    = Roomunionmodel::whereIn('id',$room_ids)->orderBy('id')
                ->get(['id','resident_id','store_id','building_id'])->groupBy('id')->toArray();
        foreach($data as $key=>$value){
            $data[$key]['store_id']     = $arr[$data[$key]['room_id']][0]['store_id'];
            $data[$key]['resident_id']  = $arr[$data[$key]['room_id']][0]['resident_id'];
            $room_id                    = $data[$key]['room_id'];
            $type                       = $data[$key]['type'];
            $sql                        = "select `serial_number` from boss_smart_device WHERE `room_id` = "."$room_id"." AND `type` ="."'".$type."'";
            $data[$key]['serial_number']= (DB::select($sql))[0]->serial_number;
            $data[$key]['building_id']  = $arr[$data[$key]['room_id']][0]['building_id'];
            $data[$key]['month']        = $month;
            $data[$key]['year']         = $year;
            $data[$key]['this_time']    = $data[$key]['created_at'];
            $data[$key]['this_reading'] = $data[$key]['reading'];
            $data[$key]                 = array_except($data[$key],['reading','created_at','updated_at','deleted_at','id']);
        }
        Meterreadingtransfermodel::insert($data);
        return true;
    }

/*******************************************************************************************/
/***********************************生成水电账单逻辑*******************************************/
/*******************************************************************************************/

    //水电账单生成
    public function utility() {
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('meterreadingmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $field = ['month', 'year', 'type'];
        $input = $this->input->post(null, true);
        if (!$this->validationText($this->validateConfirm())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $month          = $this->checkAndGetMonth($input['month'], false);
        $year           = $this->checkAndGetYear($input['year'], false);
        if ($month-1    == 0){
            $year_last  = $year-1;
            $month_last = 12;
        }else {
            $year_last  = $year;
            $month_last = $month - 1;
        }
        $type           = $input['type'];
        $store_id       = $input['store_id'];

        $resident_ids   = Roomunionmodel::where('store_id',$store_id)->get(['resident_id'])->toArray();
        $error          = [];
        $sum            = 0;
        $filed          = ['id','store_id','room_id','resident_id','type','year','month','this_reading','this_time','weight','status','confirmed'];
        foreach ($resident_ids as $k=>$v){
            $resident_id    = $resident_ids[$k]['resident_id'];
            if ($resident_id == 0){
                continue;
            }
            $sql            = Meterreadingtransfermodel::with('roomunion')->with('store')->where('year',$year)->where('month',$month)
                            ->where('type',$type)->where('resident_id',$resident_id);
            $sql_last       =Meterreadingtransfermodel::where('year',$year_last)->where('month',$month_last)
                            ->where('type',$type)->where('resident_id',$resident_id);
            //本月月末水电读数
            $this_reading   = $sql->where('status',Meterreadingtransfermodel::NORMAL)->first();
            //上月月末水电读数
            $last_reading   = $sql_last->where('status',Meterreadingtransfermodel::NORMAL)->first($filed);
            //换表初始读数
            $new_reading    = Meterreadingtransfermodel::where('year',$year)->where('month',$month)
                            ->where('type',$type)->where('resident_id',$resident_id)
                            ->where('status',Meterreadingtransfermodel::NEW_METER)
                            ->first($filed);
            //入住时读数
            $rent_rading    = Meterreadingtransfermodel::where('year',$year)->where('month',$month)
                            ->where('type',$type)->where('resident_id',$resident_id)
                            ->where('status',Meterreadingtransfermodel::NEW_RENT)
                            ->first($filed);
            /**
             * 不同的账单逻辑,处理不同情况下的水电数据包括:
             * 1.正常情况(整月账单生成,即上月月底到本月月底);
             * 2.换表(上月月底，本月换表读数，新表初始读数，月底读数)
             * 3.中途入住(上月月底无读数，本月两次读数)
             * 4.其它(暂未考虑)
             */
            if (empty($this_reading)){
                $number     =   DB::select("select `number` from boss_room_union WHERE `resident_id` = '$resident_id'");
                $number     = $number[0]->number;
                $error[]    = '房间'."$number".'的读数未上传';
            }else{
                if(!empty($new_reading)){
                    $order = $this->addUtilityOrder($this_reading,$new_reading,$year,$month);
                    if ($order){
                        $this_reading->confirmed = 1;$this_reading->save();
                        $new_reading->confirmed = 1;$new_reading->save();
                        $sum +=1;
                    }else{
                        $number = $this_reading->roomunion->number;
                        $error[]    = '房间'."$number".'的账单生成失败';
                        log_message('error','房间'."$number".'的账单生成失败');
                    }
                } elseif (!empty($rent_rading)){
                    $order = $this->addUtilityOrder($this_reading,$rent_rading,$year,$month);
                    if ($order){
                        $this_reading->confirmed = 1;$this_reading->save();
                        $last_reading->confirmed = 1;$last_reading->save();
                        $sum +=1;
                    }else{
                        $number = $this_reading->roomunion->number;
                        $error[]    = '房间'."$number".'的账单生成失败';
                        log_message('error','房间'."$number".'的账单生成失败');
                    }
                }else{
                    $order = $this->addUtilityOrder($this_reading,$last_reading,$year,$month);
                    if ($order){
                        $this_reading->confirmed = 1;$this_reading->save();
                        $this_reading->confirmed = 1;$this_reading->save();
                        $sum +=1;
                    }else{
                        $number = $this_reading->roomunion->number;
                        $error[]    = '房间'."$number".'的账单生成失败';
                        log_message('error','房间'."$number".'的账单生成失败');
                    }
                }
            }
        }
        $total = '成功生成'.$sum.'条账单';
        $this->api_res(0,['error'=>$error,'correct' =>$total]);
    }

    /**
     * 生成水电订单
     */
    private function addUtilityOrder($this_reading,$last_reading,$year,$month){
        switch ($this_reading->type) {
            case Meterreadingtransfermodel::TYPE_ELECTRIC:
                $type = Ordermodel::PAYTYPE_ELECTRIC;
                $price = $this_reading->store->electricity_price;
                break;
            case Meterreadingtransfermodel::TYPE_WATER_H:
                $type  = Ordermodel::PAYTYPE_WATER_HOT;
                $price = $this_reading->store->hot_water_price;
                break;
            case Meterreadingtransfermodel::TYPE_WATER_C:
                $type  = Ordermodel::PAYTYPE_WATER;
                $price = $this_reading->store->water_price;
                break;
            default:
                throw new Exception('未识别的账单类型！');
                break;
        }

        $money = ($this_reading->this_reading - $last_reading->last_reading) * $price;
        if (0.01 > $money) {
            return null;
        }

        //分进角，比如 1.01 元，计为 1.1 元
        $money = ceil($money * $this_reading->weight / 10) / 10;
        $this->load->helper('string');
        $order = new Ordermodel();
        $arr  =[
            'number'       => date('YmdHis') . random_string('numeric', 10),
            'type'         => $type,
            'year'         => $year,
            'month'        => $month,
            'money'        => $money,
            'paid'         => $money,
            'store_id'     => $this_reading->store_id,
            'resident_id'  => $this_reading->resident_id,
            'room_id'      => $this_reading->room_id,
            'employee_id'  => $this->employee->id,
            'customer_id'  => $this_reading->resident->customer_id,
            'uxid'         => $this_reading->resident->uxid,
            'status'       => Ordermodel::STATE_GENERATED,
            'deal'         => Ordermodel::DEAL_UNDONE,
            'pay_status'   => Ordermodel::PAYSTATE_RENEWALS,
        ];
        $order->fill($arr);
        if ($order->save()){
            return true;
        }else{
            return false;
        }
    }

    private function validateConfirm() {
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
}