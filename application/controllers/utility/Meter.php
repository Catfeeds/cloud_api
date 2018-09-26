<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/7/31
 * Time:        9:25
 * Describe:    水电读数上传及账单生成逻辑
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
	
	/**
	 * 获取导入读数,并验证读数的正确性
	 */
	public function normalDeviceReading()
	{
		//转换excel读数为数组
		$url       = $this->input->post('url');
		$f_open    = fopen($url, 'r');
		$file_name = APPPATH . 'cache/test.xlsx';
		file_put_contents($file_name, $f_open);
		$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file_name);
		$reader        = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
		$reader->setReadDataOnly(true);
		$excel      = $reader->load($file_name);
		$sheetArray = $excel->getActiveSheet()->toArray();
		array_shift($sheetArray);
		var_dump($sheetArray);
		$transfer = new Meterreadingtransfermodel();
		$data     = $transfer->checkInputData($sheetArray);
		if (!empty($data['error'])) {
			$this->api_res(10052, ['error' => $data['error']]);
			return;
		}
		$this->api_res(0, $data);
	}
	
	/*public function saveReading()
	{
		$this->load->model('meterreadingmodel');
		$this->load->model('storemodel');
		$this->load->model('roomunionmodel');
		$this->load->model('smartdevicemodel');
		$type     = $this->input->post('type');
		$store_id = $this->input->post('store_id');
		//检查表计类型
		$type     = $this->checkAndGetReadingType($type);
		$transfer = new Meterreadingtransfermodel();
		//存储导入数据
		$res = $transfer->writeReading($data, $store_id, $type);
		if (!empty($res)) {
			$this->api_res(10051, ['error' => $res]);
		} else {
			$this->api_res(0);
		}
	}*/
	
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
	
	/**
	 * 转换表读数为数组
	 * excel数据数组格式：
	 * 序号 房间号 本次读数 楼栋ID 权重
	 *  0    1      2      3    4
	 * @return array
	 */
	private function uploadOssSheet()
	{
		
		return ;
	}
	
	/*******************************************************************************************/
	/***********************************生成水电账单逻辑*******************************************/
	/*******************************************************************************************/
	
	//水电账单生成
	public function utility()
	{
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
		$month = $this->checkAndGetMonth($input['month'], false);
		$year  = $this->checkAndGetYear($input['year'], false);
		if ($month - 1 == 0) {
			$year_last  = $year - 1;
			$month_last = 12;
		} else {
			$year_last  = $year;
			$month_last = $month - 1;
		}
		$type     = $input['type'];
		$store_id = $input['store_id'];
		
		$resident_ids = Roomunionmodel::where('store_id', $store_id)
			->where('status', Roomunionmodel::STATE_RENT)
			->get(['resident_id'])->toArray();
		$error        = [];
		$sum          = 0;
		$filed        = ['id', 'store_id', 'room_id', 'resident_id', 'type', 'year', 'month', 'this_reading', 'this_time', 'weight', 'status', 'order_id', 'confirmed'];
		foreach ($resident_ids as $k => $v) {
			$resident_id = $resident_ids[$k]['resident_id'];
			if ($resident_id == 0) {
				continue;
			}
			$sql      = Meterreadingtransfermodel::with('roomunion')->with('store')->where('year', $year)->where('month', $month)
				->where('type', $type)->where('resident_id', $resident_id);
			$sql_last = Meterreadingtransfermodel::with('roomunion')->with('store')->where('year', $year_last)->where('month', $month_last)
				->where('type', $type)->where('resident_id', $resident_id);
			//本月月末水电读数
			$this_reading = $sql->where('status', Meterreadingtransfermodel::NORMAL)->first($filed);
			//上月月末水电读数
			$last_reading = $sql_last->where('status', Meterreadingtransfermodel::NORMAL)->first($filed);
			//换表初始读数
			$new_reading = Meterreadingtransfermodel::with('roomunion')->with('store')->where('year', $year)->where('month', $month)
				->where('type', $type)->where('resident_id', $resident_id)
				->where('status', Meterreadingtransfermodel::NEW_METER)
				->first($filed);
			//入住时读数
			$rent_reading = Meterreadingtransfermodel::with('roomunion')->with('store')->where('year', $year)->where('month', $month)
				->where('type', $type)->where('resident_id', $resident_id)
				->where('status', Meterreadingtransfermodel::NEW_RENT)
				->first($filed);
			/**
			 * 不同的账单逻辑,处理不同情况下的水电数据包括:
			 * 1.正常情况(整月账单生成,即上月月底到本月月底);
			 * 2.换表(上月月底，本月换表读数，新表初始读数，月底读数)
			 * 3.中途入住(上月月底无读数，本月两次读数)
			 * 4.其它(暂未考虑)
			 */
			if (empty($this_reading)) {
				$number = DB::select("select `number` from boss_room_union WHERE `resident_id` = '$resident_id'");
				$number = $number[0]->number;
				log_message('debug', '房间' . "$number" . '的读数未上传');
				$error[] = '房间' . "$number" . '的读数未上传';
			} elseif ($this_reading) {
				if (!empty($new_reading)) {
					$order = $this->addUtilityOrder($this_reading, $new_reading, $year, $month);
					if ($order) {
						$this_reading->confirmed = 1;
						$this_reading->save();
						$new_reading->confirmed = 1;
						$new_reading->save();
						$sum += 1;
					} else {
						$number  = $this_reading->roomunion->number;
						$error[] = '房间' . "$number" . '的账单生成失败';
						log_message('error', '房间' . "$number" . '的账单生成失败');
					}
				} elseif (!empty($rent_reading)) {
					$order = $this->addUtilityOrder($this_reading, $rent_reading, $year, $month);
					if ($order) {
						$this_reading->confirmed = 1;
						$this_reading->save();
						$rent_reading->confirmed = 1;
						$rent_reading->save();
						$sum += 1;
					} else {
						$number  = $this_reading->roomunion->number;
						$error[] = '房间' . "$number" . '的账单生成失败';
						log_message('error', '房间' . "$number" . '的账单生成失败');
					}
				} else {
					$order = $this->addUtilityOrder($this_reading, $last_reading, $year, $month);
					if ($order) {
						$this_reading->confirmed = 1;
						$this_reading->save();
						$last_reading->confirmed = 1;
						$last_reading->save();
						$sum += 1;
					} else {
						$number  = $this_reading->roomunion->number;
						$error[] = '房间' . "$number" . '的账单生成失败';
						log_message('error', '房间' . "$number" . '的账单生成失败');
					}
				}
			}
		}
		$total = '成功生成' . $sum . '条账单';
		$this->api_res(0, ['error' => $error, 'correct' => $total]);
	}
	
	/**
	 * 生成水电订单
	 */
	private function addUtilityOrder($this_reading, $last_reading, $year, $month)
	{
		switch ($this_reading->type) {
			case Meterreadingtransfermodel::TYPE_ELECTRIC:
				$type  = Ordermodel::PAYTYPE_ELECTRIC;
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
		
		if (!isset($last_reading->this_reading)) {
			return null;
		}
		
		$money = ($this_reading->this_reading - $last_reading->this_reading) * $price;
		if (0.01 > $money) {
			return null;
		}
		
		//分进角，比如 1.01 元，计为 1.1 元
		$money = ceil($money * $this_reading->weight / 10) / 10;
		$this->load->helper('string');
		$order = new Ordermodel();
		$arr   = [
			'number'        => date('YmdHis') . random_string('numeric', 10),
			'type'          => $type,
			'year'          => $year,
			'month'         => $month,
			'money'         => $money,
			'paid'          => $money,
			'store_id'      => $this_reading->store_id,
			'resident_id'   => $this_reading->resident_id,
			'room_id'       => $this_reading->room_id,
			'employee_id'   => $this->employee->id,
			'customer_id'   => $this_reading->resident->customer_id,
			'uxid'          => $this_reading->resident->uxid,
			'status'        => Ordermodel::STATE_GENERATED,
			'deal'          => Ordermodel::DEAL_UNDONE,
			'pay_status'    => Ordermodel::PAYSTATE_RENEWALS,
			'transfer_id_s' => $last_reading->id,
			'transfer_id_e' => $this_reading->id,
		];
		log_message('debug', 'customer_id为-->' . "$this_reading->resident");
		$order->fill($arr);
		if ($order->save()) {
			return $order->id;
		} else {
			return false;
		}
	}
	
	private function validateConfirm()
	{
		return [
			[
				'field' => 'type',
				'label' => '费用类型',
				'rules' => 'required|trim|in_list[HOT_WATER_METER,COLD_WATER_METER,ELECTRIC_METER]',
			],
			[
				'field' => 'year',
				'label' => '年',
				'rules' => 'required|trim',
			],
			[
				'field' => 'month',
				'label' => '月',
				'rules' => 'required|trim',
			],
		];
	}
	
	/**
	 * 判断门店有哪些表
	 */
	public function meterOfStore()
	{
		$this->load->model('storemodel');
		$this->load->model('meterreadingtransfermodel');
		$post     = $this->input->post(null, true);
		$store_id = $post['store_id'];
		$meter    = Storemodel::where('id', $store_id)->first(['id', 'water_price', 'hot_water_price',
		                                                       'electricity_price'])->toArray();
		$arr      = [];
		if (floatval($meter['water_price']) > 0) {
			$arr[] = Meterreadingtransfermodel::TYPE_WATER_C;
		}
		if (floatval($meter['hot_water_price']) > 0) {
			$arr[] = Meterreadingtransfermodel::TYPE_WATER_H;
		}
		if (floatval($meter['electricity_price']) > 0) {
			$arr[] = Meterreadingtransfermodel::TYPE_ELECTRIC;
		}
		$this->api_res(0, ['meter' => $arr]);
	}
	
	/**
	 * 根据门店，设备类型返回excel模板
	 */
	public function readingTemplate()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('roomunionmodel');
		$this->load->model('residentmodel');
		$this->load->model('storemodel');
		$post                                              = $this->input->post(null, true);
		$where                                             = [];
		$where['boss_meter_reading_transfer.store_id']     = $post['store_id'];
		$where['boss_meter_reading_transfer.type']         = $post['type'];
		$where['boss_meter_reading_transfer.order_status'] = 'NOREADING';
		$where['boss_meter_reading_transfer.status']       = 'NORMAL';
		$transfer                                          = new Meterreadingtransfermodel();
		$res                                               = $transfer->readingDetails($where);
		$objPHPExcel                                       = new Spreadsheet();
		$sheet                                             = $objPHPExcel->getActiveSheet();
		$i                                                 = 1;
		$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, 'ID(注:不可更改)');
		$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, '房间号');
		$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, '租户姓名');
		$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, '读数(推荐带两位小数)');
		$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, '权重(取值范围:0~100)');
		$sheet->fromArray($res, null, 'A2');
		$writer = new Xlsx($objPHPExcel);
		header("Pragma: public");
		header("Expires: 0");
		header("Content-Type:application/octet-stream");
		header("Content-Transfer-Encoding:binary");
		header('Cache-Control: max-age=0');
		header("Content-Disposition:attachment;filename=a.Xlsx");
		$writer->save('php://output');
		exit;
	}
}