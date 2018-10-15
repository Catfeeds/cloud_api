<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as DB;
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
		$input = $this->input->post(null, true);
		if (empty($input['url'])) {
			$this->api_res(1002);
			return;
		}
		$f_open    = fopen($input['url'], 'r');
		$file_name = APPPATH . 'cache/test.xlsx';
		file_put_contents($file_name, $f_open);
		$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file_name);
		$reader        = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
		$reader->setReadDataOnly(true);
		$excel      = $reader->load($file_name);
		$sheetArray = $excel->getActiveSheet()->toArray();
		array_shift($sheetArray);
		$transfer = new Meterreadingtransfermodel();
		$data     = $transfer->checkInputData($sheetArray);
		$this->api_res(0, $data);
	}
	
	/**
	 * 更新水电读数
	 */
	public function saveReading()
	{
		$transfer = new Meterreadingtransfermodel();
		$data     = $this->input->post('data');
		$data     = json_decode($data, true);
		//存储导入数据
		$res = $transfer->updateReading($data);
		$this->api_res(0);
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
	
	/*******************************************************************************************/
	/***********************************生成水电账单逻辑*******************************************/
	/*******************************************************************************************/
	
	/**
	 *  生成水电账单
	 */
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
			->get(['resident_id', 'number', 'cold_water_price', 'hot_water_price', 'electricity_price', 'gas_price'])->toArray();
		$error        = [];
		$sum          = 0;
		foreach ($resident_ids as $k => $v) {
			$resident_id = $v['resident_id'];
			if ($resident_id == 0) {
				continue;
			}
			$condition_this = [$year, $month, $type, $resident_id, "NORMAL", $store_id,];
			$condition_last = [$year_last, $month_last, $type, $resident_id, "NORMAL", $store_id,];
			$condition_new  = [$year, $month, $type, $resident_id, "CHANGE_NEW", $store_id,];
			$condition_rent = [$year, $month, $type, $resident_id, "NEW_RENT", $store_id,];
			//sql
			$sql = "select t_reading.* " .
				"from boss_meter_reading_transfer as t_reading " .
				"LEFT JOIN boss_room_union as t_room ON t_reading.room_id = t_room.id " .
				"LEFT JOIN boss_store as t_store ON t_reading.store_id = t_store.id " .
				"where t_reading.year = ? " .
				"and t_reading.month = ? " .
				"and t_reading.type = ? " .
				"and t_reading.resident_id = ? " .
				"and t_reading.status = ? " .
				"and t_store.id = ? " .
				"and t_reading.deleted_at is null ";
			//账单状态
			$order_status = "and t_reading.order_status = 'NOORDER' ";
			//本次读数
			$this_reading = DB::select($sql . $order_status, $condition_this);
			//上月月底读数
			$last_reading = DB::select($sql, $condition_last);
			//换表初始读数
			$new_reading = DB::select($sql, $condition_new);
			//入住时读数
			$rent_reading = DB::select($sql, $condition_rent);
			
			/**
			 * 不同的账单逻辑,处理不同情况下的水电数据包括:
			 * 1.正常情况(整月账单生成,即上月月底到本月月底);
			 * 2.换表(上月月底，本月换表读数，新表初始读数，月底读数)
			 * 3.中途入住(上月月底无读数，本月两次读数)
			 * 4.其它(暂未考虑)
			 */
			$number = $v['number'];
			if (empty($this_reading)) {
				log_message('debug', '房间' . "$number" . '的读数未上传');
				$error[] = '房间' . "$number" . '的读数未上传';
				continue;
			} else {
				$transfer     = new Meterreadingtransfermodel();
				$this_reading = $this_reading[0];
				$price        = $v;
				if (!empty($new_reading[0])) {
					$new_reading = $new_reading[0];
					$order       = $transfer->utility($this_reading, $new_reading, $price);
				} elseif (!empty($rent_reading[0])) {
					$rent_reading = $rent_reading[0];
					$order        = $transfer->utility($this_reading, $rent_reading, $price);
				} elseif (!empty($last_reading[0])) {
					$last_reading = $last_reading[0];
					$order        = $transfer->utility($this_reading, $last_reading, $price);
				} else {
					$order = false;
					log_message('debug', '房间' . "$number" . '的上次读数未上传');
					$error[] = '房间' . "$number" . '的上次读数未上传';
				}
				if ($order) {
					$sum += 1;
				}
			}
		}
		$total = '成功生成' . $sum . '条账单';
		$this->api_res(0, ['error' => $error, 'correct' => $total]);
	}
	
	private function validateConfirm()
	{
		return [
			[
				'field' => 'type',
				'label' => '费用类型',
				'rules' => 'required|trim|in_list[HOT_WATER_METER,COLD_WATER_METER,ELECTRIC_METER,GAS_METER]',
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
	 * 判断房间有哪些表
	 */
	public function meterOfStore()
	{
		$this->load->model('roomunionmodel');
		$this->load->model('meterreadingtransfermodel');
		$post    = $this->input->post(null, true);
		$room_id = $post['room_id'];
		$meter   = Roomunionmodel::where('id', $room_id)->first(['id', 'cold_water_price', 'hot_water_price',
		                                                         'electricity_price', 'gas_price'])->toArray();
		$arr     = [];
		if (floatval($meter['cold_water_price']) > 0) {
			$arr[] = Meterreadingtransfermodel::TYPE_WATER_C;
		}
		if (floatval($meter['hot_water_price']) > 0) {
			$arr[] = Meterreadingtransfermodel::TYPE_WATER_H;
		}
		if (floatval($meter['electricity_price']) > 0) {
			$arr[] = Meterreadingtransfermodel::TYPE_ELECTRIC;
		}
		if (floatval($meter['gas_price']) > 0) {
			$arr[] = Meterreadingtransfermodel::TYPE_GAS;
		}
		$this->api_res(0, ['meter' => $arr]);
	}
	
	/**
	 * 根据门店，设备类型返回excel模板
	 */
	public function readingTemplateL()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('roomunionmodel');
		$this->load->model('residentmodel');
		$this->load->model('storemodel');
		$post = $this->input->post(null, true);
		if (!isset($post['store_id']) || !isset($post['type'])) {
			$this->api_res(1002);
			return;
		}
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
		if (!headers_sent()) {
			header("Pragma: public");
			header("Expires: 0");
			header("Content-Type:application/octet-stream");
			header("Content-Transfer-Encoding:binary");
			header('Cache-Control: max-age=0');
			header("Content-Disposition:attachment;filename=a.Xlsx");
		}
		$writer->save('php://output');
		exit;
	}
	
	/**
	 * 导出excel模板,高配版
	 */
	public function readingTemplate()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('roomunionmodel');
		$this->load->model('residentmodel');
		$this->load->model('storemodel');
		$post = $this->input->post(null, true);
		if (!isset($post['store_id']) || !isset($post['type'])) {
			$this->api_res(1002);
			return;
		}
		$where                                             = [];
		$where['boss_meter_reading_transfer.store_id']     = $post['store_id'];
		$where['boss_meter_reading_transfer.type']         = $post['type'];
		$where['boss_meter_reading_transfer.order_status'] = 'NOREADING';
		$where['boss_meter_reading_transfer.status']       = 'NORMAL';
		//
		$transfer = new Meterreadingtransfermodel();
		$res      = $transfer->readingDetails($where);
		$row      = count($res) + 1;
		$filename = date('Y-m-d-H:i:s') . '导出' . '水电读数模板.Xlsx';
		$phpexcel = new Spreadsheet();
		$sheet    = $phpexcel->getActiveSheet();
		$this->createPHPExcel($phpexcel, $filename); //创建excel
		$this->setExcelFirstRow($phpexcel); //设置各字段名称
		$sheet->fromArray($res, null, 'A2'); //想excel中写入数据
		$this->setExcelColumnWidth($phpexcel); //设置Excel每列宽度
		$this->setAlignCenter($phpexcel, $row); //设置记录值居中
		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($phpexcel, 'Xlsx');
		if (!headers_sent()) {
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
	
	private function createPHPExcel(Spreadsheet $phpexcel, $filename)
	{
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
	
	private function setAlignCenter(Spreadsheet $phpexcel, $row)
	{
		$phpexcel->getActiveSheet()
			->getStyle("A1:E{$row}")
			->getAlignment()
			->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		// 为了使任何表保护，需设置为真
		$phpexcel->getActiveSheet()->getProtection()->setSheet(true);
		// 将A,B两列保护 加密密码是 PHPExcel
		$phpexcel->getActiveSheet()->protectCells("A1:A{$row}", 'PHPExcel');
		//去掉保护
		$phpexcel->getActiveSheet()->getStyle("B1:E{$row}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
	}
	
	private function setExcelFirstRow(Spreadsheet $phpexcel)
	{
		$phpexcel->getActiveSheet()->setCellValue('A1', 'ID(注:不可更改)');
		$phpexcel->getActiveSheet()->setCellValue('B1', '房间号');
		$phpexcel->getActiveSheet()->setCellValue('C1', '租户姓名');
		$phpexcel->getActiveSheet()->setCellValue('D1', '读数(推荐带两位小数)');
		$phpexcel->getActiveSheet()->setCellValue('E1', '权重(范围:0~100)');
	}
	
	private function setExcelColumnWidth(Spreadsheet $phpexcel)
	{
		$phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
		$phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(28);
		$phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
	}
}