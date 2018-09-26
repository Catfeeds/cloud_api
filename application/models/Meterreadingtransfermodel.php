<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        14:49
 * Describe:
 */

/**
 * 记录水电费的临时数据，主要用于计算水电费
 */
class Meterreadingtransfermodel extends Basemodel
{
	protected $table = 'boss_meter_reading_transfer';
	
	protected $dates = [];
	
	protected $fillable = [
		'store_id',
		'building_id',
		'room_id',
		'serial_number',
		'resident_id',
		'year',
		'month',
		'this_time',
		'status',
		'image',
		'weight',
		'type',
		'last_reading',
		'this_reading',
		'confirmed',
		'created_at',
		'updated_at',
	];
	
	protected $casts = [
		'confirmed' => 'boolean',
	];
//表类型
	const TYPE_WATER_H = 'HOT_WATER_METER'; //冷水表
	const TYPE_WATER_C = 'COLD_WATER_METER'; //热水表
	const TYPE_ELECTRIC = 'ELECTRIC_METER'; //电表
	//状态
	const NORMAL = 'NORMAL'; //正常状态（生成整月账单）
	const OLD_METER = 'CHANGE_OLD'; //旧表
	const NEW_METER = 'CHANGE_NEW'; //新表
	const NEW_RENT = 'NEW_RENT'; //月中入住
	const REFUND = 'REFUND'; //退房
	
	const  UNCONFIRMED = 0;
	const  CONFIRMED = 1;
	
	/**
	 * 该记录所属房间
	 */
	public function roomunion()
	{
		return $this->belongsTo(Roomunionmodel::class, 'room_id');
	}
	
	public function building()
	{
		return $this->belongsTo(BuildingModel::class, 'building_id')
			->select('id', 'name');
	}
	
	public function store()
	{
		return $this->belongsTo(Storemodel::class, 'store_id')
			->select('id', 'name', 'water_price', 'hot_water_price', 'electricity_price');
	}
	
	public function resident()
	{
		return $this->belongsTo(Residentmodel::class, 'resident_id')
			->select('id', 'name', 'customer_id', 'uxid');
	}
	
	public function room_s()
	{
		return $this->belongsTo(Roomunionmodel::class, 'room_id')
			->select('id', 'number');
	}
	
	/**
	 * 检测上传读数的正确性，并返回错误信息
	 * 0 ID 1 number 2 name 3 reading 4 weight
	 */
	public function checkInputData($sheetArray)
	{
		$data = [];
		foreach ($sheetArray as $key => $item) {
			$error = '';
			//ID
			$id = $item[0];
			//检查房间号
			$number = trim($item[1]);
			//检查表读数
			$read = trim($item[3]);
			if (!is_numeric($read) || 0 > $read || 1e8 < $read) {
				$error .= '-读数有误-';
				log_message('debug', '请检查房间：' . $item[1] . '的表读数');
			}
			//检查权重
			$weight = isset($item[4]) ? (int)$item[4] : 100;
			if (!$weight) {
				$weight = 100;
			} elseif (100 < $weight || 0 > $weight) {
				$error .= '-均摊比例有误-';
				log_message('debug', '请检查房间：' . $item[1] . '的均摊比例');
			}
			$data[] = ['id' => $id, 'number' => $number, 'this_reading' => $read, 'weight' => $weight, 'error' => $error];
		}
		return $data;
	}
	
	/**
	 * 批量更新读数
	 */
	public function updateReading($data = [])
	{
		$this_time = date('Y-m-d', time());
		foreach ($data as $k => $v) {
			$transfer               = Meterreadingtransfermodel::where('id',$v['id'])->first();
			if (empty($transfer)){
				continue;
			}
			$transfer->this_reading = $v['this_reading'];
			$transfer->weight       = $v['weight'];
			$transfer->this_time    = $this_time;
			$transfer->save();
		}
		return true;
	}
	
	/**
	 * 处理并插入数据库
	 */
	public function writeReading($data = [], $store_id, $type, $year, $month)
	{
		$error = [];
		//获取所有房间号(number)
		$number = [];
		foreach ($data as $key => $value) {
			$number[] = $data[$key]['number'];
		}
		//根据房间号获取住户id(resident_id)，房间id(room_id)
		$arr = Roomunionmodel::where('store_id', $store_id)/*->whereIn('number',$number)*/
		->orderBy('number')
			->get(['id', 'number', 'resident_id', 'building_id'])->groupBy('number')->toArray();
		//重组插入数据库所需数组
		foreach ($data as $key => $value) {
			$number   = $value['number'];
			$transfer = new Meterreadingtransfermodel();
			if (isset($arr[$value['number']])) {
				$data[$key]['resident_id']   = $arr[$value['number']][0]['resident_id'];
				$data[$key]['room_id']       = $arr[$value['number']][0]['id'];
				$data[$key]['building_id']   = $arr[$value['number']][0]['building_id'];
				$data[$key]['month']         = $month;
				$data[$key]['year']          = $year;
				$data[$key]['type']          = $type;
				$data[$key]['store_id']      = $store_id;
				$serial_number               = Smartdevicemodel::where('type', $type)->where('room_id', $arr[$value['number']][0]['id'])->first();
				$data[$key]['serial_number'] = isset($serial_number->serial_number) ? $serial_number->serial_number : "";
				$data[$key]                  = array_except($data[$key], ['number', 'error']);
				$transfer->fill($data[$key]);
				try {
					$transfer->save();
				} catch (Exception $e) {
					log_message("error", '房间' . $number . '读数导入失败');
					$error[] = '房间' . $number . '读数已存在';
				}
			} else {
				log_message("error", '房间' . $number . '不存在');
				$error[] = '房间' . $number . '不存在';
				continue;
			}
		}
		return $error;
	}
	
	/**
	 * 在transfer表中按照门店房间创建读数记录等待读数导入
	 */
	public function fillReading()
	{
		$filed = ['boss_room_union.store_id', 'boss_room_union.id as room_id', 'boss_room_union.resident_id',
		          'boss_smart_device.serial_number', 'boss_smart_device.type',
		];
		$res   = Roomunionmodel::rightJoin('boss_smart_device', 'boss_room_union.id', '=', 'boss_smart_device.room_id')
			->select($filed)
			->whereIn('boss_smart_device.type', ['COLD_WATER_METER', 'HOR_WATER_METER', 'ELECTRIC_METER'])
			->orderBy('store_id')
			->orderBy('type')
			->orderBy('room_id')
			->get()
			->map(function ($s) {
				$s->year  = date('Y', strtotime('+1 month'));
				$s->month = date('m', strtotime('+1 month'));
				return $s;
			})
			->toArray();
		$res   = Meterreadingtransfermodel::insert($res);
		return $res;
	}
	
	/**
	 * 按条件返回水电详细列表
	 */
	public function readingDetails($where)
	{
		$filed = ['boss_meter_reading_transfer.id', 'boss_room_union.number', 'boss_resident.name'];
		$res   = Meterreadingtransfermodel::leftJoin('boss_room_union', 'boss_room_union.id', '=', 'boss_meter_reading_transfer.room_id')
			->leftJoin('boss_resident', 'boss_resident.id', '=', 'boss_meter_reading_transfer.resident_id')
			->select($filed)
			->where($where)
			->get()
			->map(function ($s) {
				if ($s->name == null) {
					$s->name = '---空置---';
				}
				$s->this_reading = '';
				$s->weight       = 100;
				return $s;
			})
			->toArray();
		return $res;
	}
	
	
}



