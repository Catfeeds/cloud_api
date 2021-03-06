<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/28
 * Time:        14:21
 * Describe:    财务-水电
 * 包括展示水电记录,修改读数及换表逻辑
 */
class Utility extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('meterreadingmodel');
		$this->CI = &get_instance();
	}
	
	/**
	 * 水电列表
	 */
	public function listUtility()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('storemodel');
		$this->load->model('buildingmodel');
		$this->load->model('roomunionmodel');
		$post      = $this->input->post(null, true);
		$page      = !empty($post['page']) ? intval($post['page']) : 1;
		$offset    = PAGINATE * ($page - 1);
		$where     = [];
		$store_ids = $this->employee_store->store_ids;
		if (!empty($post['building_id'])) {
			$where['building_id'] = intval($post['building_id']);
		};
		if (!empty($post['store_id'])) {
			$where['store_id'] = intval($post['store_id']);
		}
		if (!empty($post['status'])) {
			$where['confirmed'] = intval($post['status']);
		}
		if (!empty($post['type'])) {
			$where['type'] = $post['type'];
		}
		$filed    = ['id', 'store_id', 'building_id', 'room_id', 'type', 'last_reading',
		             'last_time', 'this_reading', 'updated_at', 'confirmed'];
		$room_ids = [];
		if (!empty($post['number'])) {
			$number  = trim($post['number']);
			$room_id = Roomunionmodel::where('number', $number)->whereIn('store_id', $store_ids)->get(['id'])->toArray();
			if ($room_id) {
				foreach ($room_id as $key => $value) {
					array_push($room_ids, $room_id[$key]['id']);
				}
			}
			$count = ceil(Meterreadingtransfermodel::where($where)
					->whereIn('store_id', $store_ids)
					->whereIn('room_id', $room_ids)
					->count() / PAGINATE);
			if ($page > $count || $page < 1) {
				$this->api_res(0, ['list' => []]);
				return;
			} else {
				$utility = Meterreadingtransfermodel::where($where)->whereIn('store_id', $store_ids)->whereIn('room_id', $room_ids)
					->with('store', 'building', 'roomunion')->take(PAGINATE)->skip($offset)
					->get($filed)->map(function ($s) {
						switch ($s->type) {
							case 'ELECTRIC_METER':
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = number_format($s->diff * $s->store->electricity_price, 2, '.', '');
								break;
							case 'COLD_WATER_METER':
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = number_format($s->diff * $s->store->water_price, 2, '.', '');
								break;
							case 'HOT_WATER_METER':
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = number_format($s->diff * $s->store->hot_water_price, 2, '.', '');
								break;
							default:
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = 0;
								break;
						}
						return $s;
					})->toArray();
			}
		} else {
			$count = ceil(Meterreadingtransfermodel::where($where)->whereIn('store_id', $store_ids)->count() / PAGINATE);
			if ($page > $count || $page < 1) {
				$this->api_res(0, ['list' => []]);
				return;
			} else {
				$utility = Meterreadingtransfermodel::where($where)->whereIn('store_id', $store_ids)
					->with('store', 'building', 'roomunion')->take(PAGINATE)->skip($offset)
					->get($filed)->map(function ($s) {
						switch ($s->type) {
							case 'ELECTRIC_METER':
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = number_format($s->diff * $s->store->electricity_price, 2, '.', '');
								break;
							case 'COLD_WATER_METER':
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = number_format($s->diff * $s->store->water_price, 2, '.', '');
								break;
							case 'HOT_WATER_METER':
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = number_format($s->diff * $s->store->hot_water_price, 2, '.', '');
								break;
							default:
								$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
								$s->price = 0;
								break;
						}
						return $s;
					})->toArray();
			}
		}
		$this->api_res(0, ['list' => $utility, 'count' => $count]);
	}
	
	public function listUtility1()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('storemodel');
		$this->load->model('buildingmodel');
		$this->load->model('roomunionmodel');
		//$store_ids = explode(',',$this->employee->store_ids);
		$filed      = ['id', 'store_id', 'building_id', 'room_id', 'type', 'last_reading', 'last_time', 'this_reading', 'updated_at'];
		$utility    = Meterreadingtransfermodel::orderBy('store_id')
			->with('store', 'building', 'roomunion')
			->get($filed)->map(function ($s) {
				switch ($s->type) {
					case 'ELECTRIC_METER':
						$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
						$s->price = number_format($s->diff * $s->store->electricity_price, 2, '.', '');
						break;
					case 'COLD_WATER_METER':
						$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
						$s->price = number_format($s->diff * $s->store->water_price, 2, '.', '');
						break;
					case 'HOT_WATER_METER':
						$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
						$s->price = number_format($s->diff * $s->store->hot_water_price, 2, '.', '');
						break;
					default:
						$s->diff  = number_format($s->this_reading - $s->last_reading, 2, '.', '');
						$s->price = 0;
						break;
				}
				return $s;
			})->toArray();
		$newUtility = [];
		foreach ($utility as $key => $value) {
			$res                 = [];
			$res['store']        = $utility[$key]['store']['name'];
			$res['building']     = $utility[$key]['building']['name'];
			$res['number']       = $utility[$key]['roomunion']['number'];
			$res['type']         = $utility[$key]['type'];
			$res['last_reading'] = $utility[$key]['last_reading'];
			$res['this_reading'] = $utility[$key]['this_reading'];
			$res['diff']         = $utility[$key]['diff'];
			$res['price']        = $utility[$key]['price'];
			$res['updated_at']   = $utility[$key]['updated_at'];
			$newUtility[]        = $res;
		}
		$this->api_res(0, $newUtility);
		
		$objPHPExcel = new Spreadsheet();
		$sheet       = $objPHPExcel->getActiveSheet();
		$i           = 1;
		$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, '门店');
		$objPHPExcel->getActiveSheet()->setCellValue('B' . $i, '楼栋');
		$objPHPExcel->getActiveSheet()->setCellValue('C' . $i, '房间号');
		$objPHPExcel->getActiveSheet()->setCellValue('D' . $i, '设备类型');
		$objPHPExcel->getActiveSheet()->setCellValue('E' . $i, '上次读数');
		$objPHPExcel->getActiveSheet()->setCellValue('F' . $i, '本次读数');
		$objPHPExcel->getActiveSheet()->setCellValue('G' . $i, '差值');
		$objPHPExcel->getActiveSheet()->setCellValue('H' . $i, '价格');
		$objPHPExcel->getActiveSheet()->setCellValue('I' . $i, '更新時間');
		$sheet->fromArray($newUtility, null, 'A2');
		$writer = new Xlsx($objPHPExcel);
		if(!headers_sent()){
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
			header("Content-Type:application/force-download");
			header("Content-Type:application/vnd.ms-excel");
			header("Content-Type:application/octet-stream");
			header("Content-Type:application/download");
			header('Content-Disposition:attachment;filename="meterReadingTemplate.xlsx"');
			header("Content-Transfer-Encoding:binary");
		}
		$writer->save('php://output');
	}
	/******************************************************************/
	/**********************水电记录，换表，修改读数************************/
	/******************************************************************/
	/**
	 * 水电记录
	 */
	public function record()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('storemodel');
		$this->load->model('roomunionmodel');
		$this->load->model('buildingmodel');
		$post          = $this->input->post(null, true);
		$page          = !empty($post['page']) ? intval($post['page']) : 1;
		$offset        = PAGINATE * ($page - 1);
		$where         = [];
		$where_special = [];
		$where_public  = [];
		$store_ids     = $this->employee_store->store_ids;
		if (!empty($post['building_id'])) {
			$where_special['boss_meter_reading_transfer.building_id'] = intval($post['building_id']);
		};
		if (!empty($post['store_id'])) {
			$where_special['boss_meter_reading_transfer.store_id'] = intval($post['store_id']);
		}
		if (!empty($post['building_id'])) {
			$where_public['building_id'] = intval($post['building_id']);
		};
		if (!empty($post['store_id'])) {
			$where_public['store_id'] = intval($post['store_id']);
		}
		if (!empty($post['status'])) {
			$where['order_status'] = trim($post['status']);
		}
		if (!empty($post['month'])) {
			$where['month'] = intval($post['month']);
		}
		if (!empty($post['year'])) {
			$where['year'] = intval($post['year']);
		}
		if (!empty($post['type'])) {
			$where_public['type']                              = $post['type'];
			$where_special['boss_meter_reading_transfer.type'] = $post['type'];
		}
		$number = empty($post['number']) ? '' : $post['number'];
		$count  = ceil(Meterreadingtransfermodel::whereIn('store_id', $store_ids)->where($where)->where($where_public)
				->where(function ($query) use ($number) {
					$query->WhereHas('room_s', function ($query) use ($number) {
						$query->where('number', 'like', "$number%");
					});
				})->count() / PAGINATE);
		$record = Meterreadingtransfermodel::rightJoin('boss_room_union', function ($join) use ($where_special) {
			$join->on('boss_meter_reading_transfer.room_id', '=', 'boss_room_union.id')
				->where($where_special);
		})
			->with(['building', 'store', 'room_s'])
			->where($where)
			->where(function ($query) use ($number) {
				$query->WhereHas('room_s', function ($query) use ($number) {
					$query->where('number', 'like', "$number%");
				});
			})
			->whereIn('boss_meter_reading_transfer.store_id', $store_ids)
			->orderBy('boss_meter_reading_transfer.year', 'DESC')
			->orderBy('boss_meter_reading_transfer.month', 'DESC')
			->orderBy('boss_meter_reading_transfer.store_id')
			->orderBy('boss_meter_reading_transfer.building_id')
			->orderBy('boss_room_union.number')
			->take(PAGINATE)->skip($offset)
			->get(['boss_meter_reading_transfer.*'])
			->map(function ($record) {
				if ($record->status == Meterreadingtransfermodel::OLD_METER) {
					$last_date            = $this->lastMonth($record->month, $record->year);
					$last                 = Meterreadingtransfermodel::where('resident_id', $record->resident_id)->where('type', $record->type)->where('room_id', $record->room_id)->where($last_date)->first(['this_reading', 'this_time', 'image']);
					$record->last_reading = !empty($last) ? ($last->this_reading) : '';
					$record->last_time    = !empty($last) ? (date('Y-m-d', strtotime($last->this_time))) : '';
					$record->this_image   = $this->fullAliossUrl($record->image);
					$record->last_image   = empty($last->image) ? '' : $this->fullAliossUrl($last->image);
					$record->this_time    = date('Y-m-d', strtotime($record->this_time));
					return $record;
				} elseif ($record->status == Meterreadingtransfermodel::NEW_RENT) {
					$record->last_reading = '';
					$record->last_time    = '';
					$record->this_image   = $this->fullAliossUrl($record->image);
					$record->last_image   = '';
					$record->this_time    = date('Y-m-d', strtotime($record->this_time));
					return $record;
				} elseif ($record->status == Meterreadingtransfermodel::NEW_METER) {
					$record->last_reading = '';
					$record->last_time    = '';
					$record->this_image   = $this->fullAliossUrl($record->image);
					$record->last_image   = '';
					$record->this_time    = date('Y-m-d', strtotime($record->this_time));
					return $record;
				} elseif ($record->status == Meterreadingtransfermodel::NORMAL) {
					$record = $this->lastReading($record);
					return json_decode($record);
				} elseif ($record->status == Meterreadingtransfermodel::REFUND) {
					$record = $this->lastReading($record);
					return json_decode($record);
				} else {
					return '';
				}
			})->toArray();
		$this->api_res(0, ['list' => $record, 'count' => $count]);
	}
	
	/**
	 * 获取上次读数
	 * 上次读数分为三类：
	 * 1.一般情况：上次读数即上个月月末读数
	 * 2.换表情况：上次读数即新表初始读数
	 * 3.月中入住：上次读数即入住时的读数
	 */
	public function lastReading($record)
	{
		//月中入住
		$new_rent = Meterreadingtransfermodel::where('resident_id', $record->resident_id)
			->where('room_id', $record->room_id)
			->where('type', $record->type)
			->where('status', Meterreadingtransfermodel::NEW_RENT)
			->first(['this_reading', 'this_time', 'image']);
		//换表
		$new_meter = Meterreadingtransfermodel::where('resident_id', $record->resident_id)
			->where('room_id', $record->room_id)
			->where('type', $record->type)
			->where('status', Meterreadingtransfermodel::NEW_METER)
			->first(['this_reading', 'this_time', 'image']);
		//上月
		$last_date    = $this->lastMonth($record->month, $record->year);
		$last_reading = Meterreadingtransfermodel::where('resident_id', $record->resident_id)
			->where('room_id', $record->room_id)
			->where('type', $record->type)
			->where($last_date)
			->first(['this_reading', 'this_time', 'image']);
		
		if (!empty($new_rent)) {
			$record->last_reading = $new_rent->this_reading;
			$record->last_time    = date('Y-m-d', strtotime($new_rent->this_time));
			$record->this_image   = empty($record->image) ? '' : $this->fullAliossUrl($record->image);
			$record->last_image   = $this->fullAliossUrl($new_rent->image);
			$record->this_time    = date('Y-m-d', strtotime($record->this_time));
		} elseif (!empty($new_meter)) {
			$record->last_reading = $new_meter->this_reading;
			$record->last_time    = date('Y-m-d', strtotime($new_meter->this_time));
			$record->this_image   = empty($record->image) ? '' : $this->fullAliossUrl($record->image);
			$record->last_image   = $this->fullAliossUrl($new_meter->image);
			$record->this_time    = date('Y-m-d', strtotime($record->this_time));
		} else {
			if (!empty($last_reading)) {
				$record->last_reading = $last_reading->this_reading;
				$record->last_time    = date('Y-m-d', strtotime($last_reading->this_time));
				$record->this_image   = empty($record->image) ? '' : $this->fullAliossUrl($record->image);
				$record->last_image   = empty($last_reading->image) ? '' : $this->fullAliossUrl($last_reading->image);
				$record->this_time    = date('Y-m-d', strtotime($record->this_time));
			} else {
				$record->last_reading = '';
				$record->last_time    = '';
				$record->this_image   = '';
				$record->last_image   = '';
			}
		}
		if (!empty($record->this_time)) {
			$record->this_time = date('Y-m-d', strtotime($record->this_time));
		}
		if (!empty($record->last_time)) {
			$record->last_time = date('Y-m-d', strtotime($record->last_time));
		}
		return $record;
	}
	
	/**
	 * 计算上个月的年月
	 */
	public function lastMonth($month = '', $year = '')
	{
		if (!empty($month) && !empty($year)) {
			if ($month == 1) {
				$month = 12;
				$year  = $year - 1;
			} else {
				$month = $month - 1;
			}
			$date = ['month' => $month, 'year' => $year];
		} else {
			$date = [];
		}
		return $date;
	}
	
	/**
	 * 修改水電讀數
	 */
	public function updateNumber()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('logofwaterelectricmodel');
		$post  = $this->input->post(null, true);
		$field = ['this_reading', 'image', 'reason'];
		if (!$this->validationText($this->validateUpdatenumber())) {
			$this->api_res(1002, ['error' => $this->form_first_error($field)]);
			return;
		}
		//修改表读数
		$id                    = $post['id'];
		$reading               = Meterreadingtransfermodel::find($id);
		if ($reading->order_status == 'HASORDER'){
			$this->api_res(10053);
			return;
		}
		$original_record       = $reading->this_reading;
		$this_reading          = floatval($post['this_reading']);
		$reading->this_reading = $this_reading;
		$reading->image        = $this->splitAliossUrl(($post['image']));
		if ($reading->save()) {
			//记录修改日志
			$log = new Logofwaterelectricmodel();
			$arr = [
				'transfer_id'     => intval($post['id']),
				'employee_id'     => $this->employee->id,
				'original_record' => $original_record,
				'now_record'      => $this_reading,
				'reason'          => $post['reason'],
			];
			$log->fill($arr);
			$log->save();
			$this->api_res(0);
		} else {
			$this->api_res(1009);
		}
	}
	
	private function validateUpdatenumber()
	{
		return [
			[
				'field' => 'this_reading',
				'label' => '本次读数',
				'rules' => 'required|trim',
			],
			[
				'field' => 'image',
				'label' => '图片路径',
				'rules' => 'required|trim',
			],
			[
				'field' => 'reason',
				'label' => '修改原因',
				'rules' => 'required|trim',
			],
		];
	}
	
	/**
	 * 换表
	 */
	public function changeMeter()
	{
		$this->load->model('meterreadingtransfermodel');
		$this->load->model('logofwaterelectricmodel');
		$post  = $this->input->post(null, true);
		$field = ['old_meter_reading', 'old_meter_image', 'new_meter_reading', 'new_meter_image', 'time'];
		if (!$this->validationText($this->validateChange())) {
			$this->api_res(1002, ['error' => $this->form_first_error($field)]);
			return;
		}
		$id       = intval($post['id']);
		$transfer = Meterreadingtransfermodel::find($id);
		$change   = new Meterreadingtransfermodel();
		$arr      = [
			'store_id'      => $transfer->store_id,
			'building_id'   => $transfer->building_id,
			'serial_number' => $transfer->serial_number,
			'room_id'       => $transfer->room_id,
			'resident_id'   => $transfer->resident_id,
			'year'          => $transfer->year,
			'month'         => $transfer->month,
			'type'          => $transfer->type,
			'weight'        => $transfer->weight,
			'this_reading'  => $post['old_meter_reading'],
			'this_time'     => date('Y-m-d H:i:s', strtotime($post['time'])),
			'status'        => Meterreadingtransfermodel::OLD_METER,
			'image'         => $this->splitAliossUrl($post['old_meter_image']),
		];
		$change->fill($arr);
		if ($change->save()) {
			$this->addUtilityOrder($transfer, $post['old_meter_reading']);
			$change_new = new Meterreadingtransfermodel();
			$new        = [
				'store_id'     => $transfer->store_id,
				'building_id'  => $transfer->building_id,
				'room_id'      => $transfer->room_id,
				'resident_id'  => $transfer->resident_id,
				'year'         => $transfer->year,
				'month'        => $transfer->month,
				'type'         => $transfer->type,
				'weight'       => $transfer->weight,
				'this_reading' => $post['new_meter_reading'],
				'this_time'    => date('Y-m-d H:i:s', strtotime($post['time'])),
				'status'       => Meterreadingtransfermodel::NEW_METER,
				'image'        => $this->splitAliossUrl($post['new_meter_image']),
			];
			$change_new->fill($new);
			$change_new->save();
		}
		$this->api_res(0);
	}
	
	/**
	 * 生成水电账单
	 */
	private function addUtilityOrder($transfer, $this_reading)
	{
		$this->load->model('ordermodel');
		$this->load->model('storemodel');
		$this->load->model('residentmodel');
		if ($transfer->resident_id == 0) {
			return null;
		}
		switch ($transfer->type) {
			case Meterreadingtransfermodel::TYPE_ELECTRIC:
				$type  = Ordermodel::PAYTYPE_ELECTRIC;
				$price = $transfer->roomunion->electricity_price;
				break;
			case Meterreadingtransfermodel::TYPE_WATER_H:
				$type  = Ordermodel::PAYTYPE_WATER_HOT;
				$price = $transfer->roomunion->hot_water_price;
				break;
			case Meterreadingtransfermodel::TYPE_WATER_C:
				$type  = Ordermodel::PAYTYPE_WATER;
				$price = $transfer->roomunion->cold_water_price;
				break;
			case Meterreadingtransfermodel::TYPE_GAS:
				$type  = Ordermodel::PAYTYPE_GAS;
				$price = $transfer->roomunion->gas_price;
				break;
			default:
				throw new Exception('未识别的账单类型！');
				break;
		}
		
		$money = ($this_reading - $transfer->this_reading) * $price;
		if (0.01 > $money) {
			return null;
		}
		
		//分进角，比如 1.01 元，计为 1.1 元
		$money = ceil($money * $transfer->weight / 10) / 10;
		$this->load->helper('string');
		$order = new Ordermodel();
		$arr   = [
			'number'      => date('YmdHis') . random_string('numeric', 10),
			'type'        => $type,
			'year'        => $transfer->year,
			'month'       => $transfer->month,
			'money'       => $money,
			'paid'        => $money,
			'store_id'    => $transfer->store_id,
			'resident_id' => $transfer->resident_id,
			'room_id'     => $transfer->room_id,
			'employee_id' => $this->employee->id,
			'customer_id' => $transfer->resident->customer_id,
			'uxid'        => $transfer->resident->uxid,
			'status'      => Ordermodel::STATE_GENERATED,
			'deal'        => Ordermodel::DEAL_UNDONE,
			'pay_status'  => Ordermodel::PAYSTATE_RENEWALS,
		];
		$order->fill($arr);
		if ($order->save()) {
			return true;
		} else {
			return false;
		}
	}
	
	private function validateChange()
	{
		return [
			[
				'field' => 'old_meter_reading',
				'label' => '旧表读数',
				'rules' => 'required|trim',
			],
			[
				'field' => 'old_meter_image',
				'label' => '旧表图片',
				'rules' => 'required|trim',
			],
			[
				'field' => 'new_meter_reading',
				'label' => '新表读数',
				'rules' => 'required|trim',
			],
			[
				'field' => 'new_meter_image',
				'label' => '新表图片',
				'rules' => 'required|trim',
			],
			[
				'field' => 'time',
				'label' => '换表时间',
				'rules' => 'required|trim',
			],
		];
	}
}
