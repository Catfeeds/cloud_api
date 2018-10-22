<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as DB;

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
	private $CI;
	
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
		'order_status',
	];
	
	protected $casts = [
		'confirmed' => 'boolean',
	];
//表类型
	const TYPE_WATER_H = 'HOT_WATER_METER'; //冷水表
	const TYPE_WATER_C = 'COLD_WATER_METER'; //热水表
	const TYPE_ELECTRIC = 'ELECTRIC_METER'; //电表
	const TYPE_GAS = 'GAS_METER'; //电表
	//状态
	const NORMAL = 'NORMAL'; //正常状态（生成整月账单）
	const OLD_METER = 'CHANGE_OLD'; //旧表
	const NEW_METER = 'CHANGE_NEW'; //新表
	const NEW_RENT = 'NEW_RENT'; //月中入住
	const REFUND = 'REFUND'; //退房
	
	const ORDER_HASORDER = 'HASORDER';//已经生成账单
	const ORDER_NORESIDENT = 'NORESIDENT';//无住户
	const ORDER_NOORDER = 'NOORDER';//未生成账单
	const ORDER_NOREADING = 'NOREADING';//未录入读数
	
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
			$transfer = Meterreadingtransfermodel::where('id', $v['id'])
				->whereIn('order_status', ['NOREADING', 'NOORDER', 'NORESIDENT'])
				->first();
			if (empty($transfer)) {
				continue;
			}
			if ($transfer->resident_id == 0) {
				$transfer->order_status = 'NORESIDENT';
			} else {
				$transfer->order_status = 'NOORDER';
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
		//重组插入数据库所需数组
		if (empty($data)) {
			return false;
		}
		$where     = ['store_id'     => $store_id,
		              'type'         => $type,
		              'year'         => $year,
		              'month'        => $month,
		              'order_status' => 'NOREADING',
		              'status'       => 'NORMAL'];
		$this_time = date('Y-m-d', time());
		foreach ($data as $key => $value) {
			$where['room_id'] = $value[0];
			log_message('debug', 'writeReading查询条件' . json_encode($where));
			$transfer = Meterreadingtransfermodel::where($where)->first();
			if (empty($transfer)) {
				log_message('debug', 'writeReading-transfer');
				continue;
			}
			if ($transfer->resident_id == 0) {
				$transfer->order_status = 'NORESIDENT';
			} else {
				$transfer->order_status = 'NOORDER';
			}
			$transfer->this_reading = $value[1];
			$transfer->this_time    = $this_time;
			if ($transfer->save()) {
				log_message('debug', 'writeReading 更新成功');
				
			} else {
				log_message('error', 'writeReading 更新失败');
			}
		}
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
			->whereIn('boss_smart_device.type', [self::TYPE_GAS, self::TYPE_ELECTRIC, self::TYPE_WATER_C, self::TYPE_WATER_H])
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
	
	public function fillHotWaterReading()
	{
		$filed = ['boss_room_union.store_id', 'boss_room_union.id as room_id', 'boss_room_union.resident_id',
		          'boss_smart_device.serial_number', 'boss_smart_device.type',
		];
		$res   = Roomunionmodel::rightJoin('boss_smart_device', 'boss_room_union.id', '=', 'boss_smart_device.room_id')
			->select($filed)
			->whereIn('boss_smart_device.type', ['HOT_WATER_METER'])
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
			->orderBy('boss_room_union.number')
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
	
	/**
	 * @param $this_reading => 本次读数及本次读数相关信息
	 * @param $last_reading => 上次读数及本次读数相关信息
	 * @param $price => 价格(数组)包括冷热水电气
	 */
	public function utility($this_reading, $last_reading, $price)
	{
		switch ($this_reading->type) {
			case self::TYPE_ELECTRIC:
				$type  = Ordermodel::PAYTYPE_ELECTRIC;
				$price = $price['electricity_price'];
				break;
			case self::TYPE_WATER_H:
				$type  = Ordermodel::PAYTYPE_WATER_HOT;
				$price = $price['hot_water_price'];
				break;
			case self::TYPE_WATER_C:
				$type  = Ordermodel::PAYTYPE_WATER;
				$price = $price['cold_water_price'];
				break;
			case self::TYPE_GAS:
				$type  = Ordermodel::PAYTYPE_GAS;
				$price = $price['gas_price'];
				break;
			default:
				throw new Exception('未识别的账单类型！');
				break;
		}
//		var_dump($price);die();
		if (!isset($last_reading->this_reading)) {
			return false;
		}
		$money = ($this_reading->this_reading - $last_reading->this_reading) * $price;
		if (0.01 > $money) {
			return false;
		}
		
		//分进角，比如 1.01 元，计为 1.1 元
		$money    = ceil($money * $this_reading->weight / 10) / 10;
		$this->CI = &get_instance();
		$this->CI->load->helper('string');
		$order    = new Ordermodel();
		$resident = Residentmodel::where('id', $this_reading->resident_id)->first(['id', 'customer_id', 'uxid']);
		$arr      = [
			'number'        => date('YmdHis') . random_string('numeric', 10),
			'type'          => $type,
			'year'          => $this_reading->year,
			'month'         => $this_reading->month,
			'money'         => $money,
			'paid'          => $money,
			'store_id'      => $this_reading->store_id,
			'resident_id'   => $this_reading->resident_id,
			'room_id'       => $this_reading->room_id,
			'employee_id'   => $this->CI->employee->id,
			'customer_id'   => $resident->customer_id,
			'uxid'          => $resident->uxid,
			'status'        => Ordermodel::STATE_GENERATED,
			'deal'          => Ordermodel::DEAL_UNDONE,
			'pay_status'    => Ordermodel::PAYSTATE_RENEWALS,
			'transfer_id_s' => $last_reading->id,
			'transfer_id_e' => $this_reading->id,
		];
		try {
			DB::beginTransaction();
			$order->fill($arr);
			$order->save();
			$transfer_last               = Meterreadingtransfermodel::where('id', $last_reading->id)->first();
			$transfer_last->order_status = 'HASORDER';
			$transfer_last->save();
			$transfer_this               = Meterreadingtransfermodel::where('id', $this_reading->id)->first();
			$transfer_this->order_status = 'HASORDER';
			$transfer_this->save();
			DB::commit();
			return true;
		} catch (Exception $e) {
			DB::rollBack();
			return false;
		}
	}
	
	/**
	 * 获取退租生成水电账单所需的数组
	 * @param $year
	 * @param $month
	 * @param $room_id
	 * @param $type
	 * @return array
	 * @throws Exception
	 */
	public function getUtilityArr($year, $month, $room_id, $type)
	{
		$room = Roomunionmodel::where('id', $room_id)
			->first(['resident_id', 'cold_water_price', 'hot_water_price',
			         'electricity_price', 'gas_price']);
		if ($month - 1 == 0) {
			$year_last  = $year - 1;
			$month_last = 12;
		} else {
			$year_last  = $year;
			$month_last = $month - 1;
		}
		//
		$condition_this = [$year, $month, $type, $room['resident_id'], self::REFUND, $room_id,];
		$condition_last = [$year_last, $month_last, $type, $room['resident_id'], "NORMAL", $room_id,];
		$condition_new  = [$year, $month, $type, $room['resident_id'], "CHANGE_NEW", $room_id,];
		$condition_rent = [$year, $month, $type, $room['resident_id'], "NEW_RENT", $room_id,];
		//sql
		$sql = "select t_reading.* " .
			"from boss_meter_reading_transfer as t_reading " .
			"LEFT JOIN boss_room_union as t_room ON t_reading.room_id = t_room.id " .
			"where t_reading.year = ? " .
			"and t_reading.month = ? " .
			"and t_reading.type = ? " .
			"and t_reading.resident_id = ? " .
			"and t_reading.status = ? " .
			"and t_reading.room_id = ? " .
			"and t_reading.deleted_at is null ";
		//账单状态
		$order_status    = "and t_reading.order_status = 'NOORDER' ";
		$order_noreading = "and t_reading.order_status <> 'NOREADING' ";
		//本次读数
		$this_reading = DB::select($sql . $order_status, $condition_this);
		//上月月底读数
		$last_reading = DB::select($sql . $order_noreading, $condition_last);
		//换表初始读数
		$new_reading = DB::select($sql . $order_noreading, $condition_new);
		//入住时读数
		$rent_reading = DB::select($sql . $order_noreading, $condition_rent);
		//
		switch ($type) {
			case self::TYPE_ELECTRIC:
				$type  = Ordermodel::PAYTYPE_ELECTRIC;
				$price = $room['electricity_price'];
				break;
			case self::TYPE_WATER_H:
				$type  = Ordermodel::PAYTYPE_WATER_HOT;
				$price = $room['hot_water_price'];
				break;
			case self::TYPE_WATER_C:
				$type  = Ordermodel::PAYTYPE_WATER;
				$price = $room['cold_water_price'];
				break;
			case self::TYPE_GAS:
				$type  = Ordermodel::PAYTYPE_GAS;
				$price = $room['gas_price'];
				break;
			default:
				throw new Exception('未识别的账单类型！');
				break;
		}
		$data = [];
		if (empty($this_reading)) {
			log_message('debug', 'getUtilityArr->未查询到的本次读数');
			$data['error'] = '未查询到的本次读数';
			return $data;
		}
		$this_reading = $this_reading[0];
		if (!empty($new_reading[0])) {
			$last_reading = $new_reading[0];
		} elseif (!empty($rent_reading[0])) {
			$last_reading = $rent_reading[0];
		} elseif (!empty($last_reading[0])) {
			$last_reading = $last_reading[0];
		} else {
			log_message('debug', 'getUtilityArr->未查询到的上次读数');
			$data['error'] = '未查询到的上次读数';
			return $data;
		}
		$money = ($this_reading->this_reading - $last_reading->this_reading) * $price;
		if (0.01 > $money) {
			log_message('debug', 'getUtilityArr->水电账单金额有误');
			$data['error'] = '水电账单金额有误';
			return $data;
		}
		$this->CI = &get_instance();
		$this->CI->load->helper('string');
		$resident = Residentmodel::where('id', $this_reading->resident_id)->first(['id', 'customer_id', 'uxid']);
		$data     = [
			'number'        => date('YmdHis') . random_string('numeric', 10),
			'type'          => $type,
			'year'          => $this_reading->year,
			'month'         => $this_reading->month,
			'money'         => $money,
			'paid'          => $money,
			'store_id'      => $this_reading->store_id,
			'resident_id'   => $this_reading->resident_id,
			'room_id'       => $this_reading->room_id,
			'employee_id'   => $this->CI->employee->id,
			'customer_id'   => $resident->customer_id,
			'uxid'          => $resident->uxid,
			'status'        => Ordermodel::STATE_GENERATED,
			'deal'          => Ordermodel::DEAL_UNDONE,
			'pay_status'    => Ordermodel::PAYSTATE_RENEWALS,
			'transfer_id_s' => $last_reading->id,
			'transfer_id_e' => $this_reading->id,
		];
		return $data;
	}
	
	/**
	 * 获取退租时插入水电读数所需的数组
	 * @param $this_reading
	 * @param $year
	 * @param $month
	 * @param $room_id
	 * @param $type
	 * @param string $image
	 * @param int $weight
	 * @return array
	 */
	public function getReadingArr($this_reading, $year, $month, $room_id, $type, $image = '', $weight = 100)
	{
		$data   = [];
		$room   = Roomunionmodel::where('id', $room_id)->first();
		$device = Smartdevicemodel::where('room_id', $room_id)->where('type', $type)->first();
		if (empty($room)) {
			$data['error'] = '未查询到room_id为' . $room_id . '的房间信息';
			log_message('error', $data['error']);
			return $data;
		}
		if (empty($device)) {
			$data['error'] = '未查询到room_id为' . $room_id . '的设备信息';
			log_message('error', $data['error']);
			return $data;
		}
		//
		$data['store_id']      = $room['store_id'];
		$data['room_id']       = $room_id;
		$data['building_id']   = 0;
		$data['resident_id']   = $room['resident_id'];
		$data['serial_number'] = $device['serial_number'];
		$data['type']          = $type;
		$data['year']          = $year;
		$data['month']         = $month;
		$data['this_reading']  = $this_reading;
		$data['this_time']     = date('Y-m-d', time());
		$data['status']        = self::REFUND;
		$data['order_status']  = self::ORDER_NOORDER;
		$data['weight']        = $weight;
		$data['image']         = $image;
		$data['created_at']    = date('Y-m-d H:i:s', time());
		$data['updated_at']    = date('Y-m-d H:i:s', time());
		return $data;
	}
}



