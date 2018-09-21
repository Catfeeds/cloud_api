<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Carbon\Carbon;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:17
 * Describe:    BOSS
 * 房间信息表(集中式)
 */
class Roomunionmodel extends Basemodel
{
	
	const HALF = 'HALF'; //合租
	const FULL = 'FULL'; //整租
	/**
	 * 房间的状态
	 */
	const STATE_BLANK = 'BLANK'; // 空
	const STATE_RESERVE = 'RESERVE'; // 预订
	const STATE_RENT = 'RENT'; // 出租
	const STATE_ARREARS = 'ARREARS'; // 欠费
	const STATE_REFUND = 'REFUND'; // 退房
	const STATE_OTHER = 'OTHER'; // 其他 保留
	const STATE_OCCUPIED = 'OCCUPIED'; // 房间被占用的状态, 可能是预约, 或者是办理入住后订单未确认之间的状态
	/*
	 * UNION 集中式；DOT 分布式
	 * */
	const TYPE_UNION = 'UNION';
	const TYPE_DOT = 'DOT';
	
	protected $table = 'boss_room_union';
	
	protected $fillable = [
		'area',
		'layer',
		'number',
		'status',
		'end_time',
		'device_id',
		'begin_time',
		'rent_price',
		'resident_id',
		'people_count',
		'apartment_id',
		'room_type_id',
		'property_price',
		'contract_template_short_id',
		'contract_template_long_id',
		'contract_template_reserve_id',
	];
	
	protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
	
	//房型展示
	public function room_type()
	{
		
		return $this->belongsTo(Roomtypemodel::class, 'room_type_id')
			->select('id', 'name', 'room_number', 'hall_number', 'toilet_number');
	}
	
	//房型展示
	public function roomtype()
	{
		
		return $this->belongsTo(Roomtypemodel::class, 'room_type_id');
	}
	
	//房间住户信息
	public function resident()
	{
		
		return $this->belongsTo(Residentmodel::class, 'resident_id');
	}
	
	public function residents()
	{
		
		return $this->belongsTo(Residentmodel::class, 'resident_id')->select('id');
	}
	
	//房间所属门店信息
	public function store()
	{
		
		return $this->belongsTo(Storemodel::class, 'store_id');
	}
	
	public function store_s()
	{
		
		return $this->belongsTo(Storemodel::class, 'store_id')->select('id', 'name');
	}
	
	public function community()
	{
		return $this->belongsTo(Communitymodel::class, 'community_id');
	}
	
	//房间所属楼栋信息
	public function building()
	{
		
		return $this->belongsTo(Buildingmodel::class, 'building_id');
	}
	
	public function building_s()
	{
		
		return $this->belongsTo(Buildingmodel::class, 'building_id')->select('id', 'name');
	}
	
	//房间的长租合同模板
	public function long_template()
	{
		return $this->belongsTo(Contracttemplatemodel::class, 'contract_template_long_id')
			->where('rent_type', 'LONG')->select(['id', 'name']);
	}
	
	//房间的短租合同模板
	public function short_template()
	{
		return $this->belongsTo(Contracttemplatemodel::class, 'contract_template_short_id')
			->where('rent_type', 'SHORT')->select(['id', 'name']);
	}
	
	//房间的预定合同模板
	public function reserve_template()
	{
		return $this->belongsTo(Contracttemplatemodel::class, 'contract_template_reserve_id')
			->where('rent_type', 'RESERVE')->select(['id', 'name']);
	}
	
	/**
	 * 表计临时读数
	 */
	public function meterreadingtransfer()
	{
		return $this->hasMany(Meterreadingtransfermodel::class, 'room_id');
	}
	
	public function orders()
	{
		return $this->hasMany(Ordermodel::class, 'room_id');
	}
	
	public function order()
	{
		return $this->hasMany(Ordermodel::class, 'room_id')
//            ->where('type','ROOM')
			->whereIn('status', ['GENERATE', 'AUDITED', 'PENDING'])
			->select('id', 'room_id', 'resident_id', 'type', 'status');
	}
	
	public function pendOrder()
	{
		return $this->hasMany(Ordermodel::class, 'room_id')
			->whereIn('status', ['PENDING'])
			->select('id', 'room_id', 'resident_id', 'type', 'status');
	}
	
	public function devices()
	{
		return $this->hasMany(Devicemodel::class, 'room_id');
	}
	
	/**
	 * 分布式房间的房屋信息
	 */
	public function house()
	{
		return $this->belongsTo(Housemodel::class, 'house_id');
	}
	
	/**
	 * 是否空闲
	 */
	public function isBlank()
	{
		if ($this->status == self::STATE_BLANK) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * 把房间状态更新为占用
	 */
	public function Occupie()
	{
		//$this->status   = self::OCCUPIED;
		return $this->update(['status' => self::STATE_OCCUPIED]);
	}
	
	/**
	 * 把房间状态更新为空闲
	 */
	public function Blank()
	{
		//$this->status   = self::BLANK;
		return $this->update(['status' => self::STATE_BLANK]);
	}
	
	public function due()
	{
		return $this->hasMany(Residentmodel::class, 'room_id')
			->whereBetween('end_time', [date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', strtotime('+1month'))])
			->select('id', 'room_id', 'end_time');
	}
	
	
	/********************************销控*************************************/
	
	/**
	 * 返回不同状态下房间数
	 */
	public function getRoomCount($where)
	{
		$count            = [];
		$count['total']   = Roomunionmodel::where($where)->count();
		$count['blank']   = Roomunionmodel::where($where)->where('status', 'BLANK')->get(['id'])->count();
		$count['reserve'] = Roomunionmodel::where($where)->where('status', 'RESERVE')->get(['id'])->count();
		$count['rent']    = Roomunionmodel::where($where)->where('status', 'RENT')->get(['id'])->count();
		$count['arrears'] = Roomunionmodel::leftJoin('boss_order', function ($jion) {
			$jion->on('boss_order.room_id', '=', 'boss_room_union.id')
				->on('boss_room_union.resident_id', '=', 'boss_order.resident_id')
				->where('boss_order.status', '=', 'PENDING');
		})
			->select('boss_room_union.id')
			->where($where)
			->where('boss_order.status', 'PENDING')
			->groupBy('boss_room_union.id')
			->get()->count();
		return $count;
	}
	
	/**
	 * 获取集中式销控房间列表
	 */
	public function uniuon_rooms($where, $status = '', $number = '', $time)
	{
		if ($status == Roomunionmodel::STATE_ARREARS) {
			$rooms = $this->union_arrears($where, $number);
			return $rooms;
		} else {
			if (!empty($status)) {
				$where['boss_room_union.status'] = $status;
			}
		}
		$filed = [
			'boss_room_union.id as room_id', 'boss_room_union.number as room_number', 'boss_room_union.layer',
			'boss_room_union.rent_price as room_price', 'boss_room_union.status',
			'boss_resident.name as name', 'boss_resident.id as resident_id', 'boss_order.status as order_status',
			'boss_room_type.room_number as count_room', 'boss_room_type.toilet_number as count_toilet',
		];
		$rooms = Roomunionmodel::leftJoin('boss_room_type', 'boss_room_type.id', '=', 'boss_room_union.room_type_id')
			->leftJoin('boss_resident', 'boss_resident.id', '=', 'boss_room_union.resident_id')
			->leftJoin('boss_order', function ($jion) {
				$jion->on('boss_order.room_id', '=', 'boss_room_union.id')
					->on('boss_room_union.resident_id', '=', 'boss_order.resident_id')
					->where('boss_order.status', '=', 'PENDING');
			})
			->select($filed)
			->orderBy('boss_room_union.number')
			->where($where)
			->where('boss_room_union.number', 'like', "%$number%")
			->whereBetween('boss_room_union.updated_at', $time)
			->groupBy('boss_room_union.id')
			->get()->map(function ($room) {
				if ($room->status == Roomunionmodel::STATE_RENT && $room->order_status == 'PENDING') {
					$room->status = Roomunionmodel::STATE_ARREARS;
				}
				$room->room_type = $room->count_room . '室' . $room->count_toilet . '厅';
				return $room;
			})
			->groupBy('layer');
		return $rooms;
	}
	
	/**
	 * 获取分布式销控房间列表
	 */
	public function dot_rooms($where, $status = '', $number = '', $time)
	{
		if ($status == Roomunionmodel::STATE_ARREARS) {
			$rooms = $this->dot_arrears($where, $number);
			return $rooms;
		} else {
			if (!empty($status)) {
				$where['boss_room_union.status'] = $status;
			}
		}
		$filed = [
			'boss_room_union.id as room_id', 'boss_room_union.number as room_number',
			'boss_room_union.house_id as house_id', 'boss_room_union.status',
			'boss_room_union.rent_price as room_price',
			'boss_resident.name as name', 'boss_resident.id as resident_id', 'boss_order.status as order_status',
			'boss_community.name as c_name', 'boss_house.building_name', 'boss_house.unit',
			'boss_house.number as house_number', 'boss_room_union.feature',
		];
		$rooms = Roomunionmodel::leftJoin('boss_community', 'boss_community.id', '=', 'boss_room_union.community_id')
			->leftJoin('boss_resident', 'boss_resident.id', '=', 'boss_room_union.resident_id')
			->leftJoin('boss_house', 'boss_house.id', '=', 'boss_room_union.house_id')
			->leftJoin('boss_order', function ($jion) {
				$jion->on('boss_order.room_id', '=', 'boss_room_union.id')
					->on('boss_room_union.resident_id', '=', 'boss_order.resident_id')
					->where('boss_order.status', '=', 'PENDING');
			})
			->select($filed)
			->orderBy('boss_room_union.number')
			->where($where)
			->where('boss_room_union.number', 'like', "%$number%")
			->whereBetween('boss_room_union.updated_at', $time)
			->groupBy('boss_room_union.id')
			->get()->map(function ($room) {
				if ($room->status == Roomunionmodel::STATE_RENT && $room->order_status == 'PENDING') {
					$room->status = Roomunionmodel::STATE_ARREARS;
				}
				$room->room_type = $this->feature($room->feature);
				$room->address   = $room->c_name . $room->building_name . '(栋)' . $room->unit . '(单元)' . $room->house_number;
				return $room;
			})
			->groupBy('address');
		return $rooms;
	}
	
	/**
	 * 返回集中式欠费房间列表
	 */
	private function union_arrears($where, $number = '')
	{
		$filed = [
			'boss_room_union.id as room_id', 'boss_room_union.number as room_number', 'boss_room_union.layer',
			'boss_room_union.rent_price as room_price', 'boss_room_union.status',
			'boss_resident.name as name', 'boss_resident.id as resident_id', 'boss_order.status as order_status',
			'boss_room_type.room_number as count_room', 'boss_room_type.toilet_number as count_toilet',
		];
		$rooms = Roomunionmodel::leftJoin('boss_room_type', 'boss_room_type.id', '=', 'boss_room_union.room_type_id')
			->leftJoin('boss_resident', 'boss_resident.id', '=', 'boss_room_union.resident_id')
			->leftJoin('boss_order', function ($jion) {
				$jion->on('boss_order.room_id', '=', 'boss_room_union.id')
					->on('boss_order.resident_id', '=', 'boss_room_union.resident_id')
					->where('boss_order.status', '=', 'PENDING');
			})
			->select($filed)
			->orderBy('boss_room_union.number')
			->where($where)
			->where('boss_order.status', 'PENDING')
			->where('boss_room_union.status', 'RENT')
			->where('boss_room_union.number', 'like', "%$number%")
			->groupBy('boss_room_union.id')
			->get()->map(function ($room) {
				$room->status = Roomunionmodel::STATE_ARREARS;
				return $room;
			})
			->groupBy('layer');
		return $rooms;
	}
	
	/**
	 * 返回分布式欠费房间列表
	 */
	private function dot_arrears($where, $number = '')
	{
		$filed = [
			'boss_room_union.id as room_id', 'boss_room_union.number as room_number',
			'boss_room_union.house_id as house_id', 'boss_room_union.status',
			'boss_room_union.rent_price as room_price',
			'boss_resident.name as name', 'boss_resident.id as resident_id', 'boss_order.status as order_status',
			'boss_community.name as c_name', 'boss_house.building_name', 'boss_house.unit',
			'boss_house.number as house_number', 'boss_room_union.feature',
		];
		$rooms = Roomunionmodel::leftJoin('boss_community', 'boss_community.id', '=', 'boss_room_union.community_id')
			->leftJoin('boss_resident', 'boss_resident.id', '=', 'boss_room_union.resident_id')
			->leftJoin('boss_house', 'boss_house.id', '=', 'boss_room_union.house_id')
			->leftJoin('boss_order', function ($jion) {
				$jion->on('boss_order.room_id', '=', 'boss_room_union.id')
					->on('boss_room_union.resident_id', '=', 'boss_order.resident_id')
					->where('boss_order.status', '=', 'PENDING');
			})
			->select($filed)
			->orderBy('boss_room_union.number')
			->where($where)
			->where('boss_order.status', 'PENDING')
			->where('boss_room_union.status', 'RENT')
			->where('boss_room_union.number', 'like', "%$number%")
			->groupBy('boss_room_union.id')
			->get()->map(function ($room) {
				$room->status    = Roomunionmodel::STATE_ARREARS;
				$room->room_type = $this->feature($room->feature);
				$room->address   = $room->c_name . $room->building_name . '(栋)' . $room->unit . '(单元)' . $room->house_number;
				return $room;
			})
			->groupBy('address');
		return $rooms;
	}
	
	/**
	 * 转换feature
	 */
	public function feature($feature)
	{
		switch ($feature) {
			case 'M' :
				return '主卧';
				break;
			case 'S' :
				return '次卧';
				break;
			case 'MT' :
				return '独卫主卧';
				break;
			default :
				return '';
				break;
		}
	}
	
	/**
	 * 查询
	 */
	public function room_details($where, $filed, $time, $number = '')
	{
		$arrears_count = 0;
		$Remove_status = array_except($where, 'status');
		
		$this->details       = Roomunionmodel::orderBy('number')->with('room_type')->with('resident')->with('pendOrder')
			->where($Remove_status)->whereBetween('updated_at', $time)
			->where('number', 'like', '%' . $number . '%')
			->get($filed)->groupBy('layer')
			->map(function ($s) {
				$s = $s->toArray();
				global $arrears_count;
				$count = 0;
				foreach ($s as $key => $value) {
					$s[$key]['order'] = $s[$key]['pend_order'];
					if (!empty($s[$key]['pend_order'])) {
						$count += 1;
					}
				}
				$arrears_count += $count;
				return [$s, 'arrears_count' => $arrears_count];
			})->toArray();
		$this->total_count   = Roomunionmodel::where($Remove_status)->whereBetween('updated_at', $time)->get($filed)->count();
		$this->blank_count   = Roomunionmodel::where($Remove_status)->where('status', 'BLANK')->whereBetween('updated_at', $time)->get($filed)->count();
		$this->reserve_count = Roomunionmodel::where($Remove_status)->where('status', 'RESERVE')->whereBetween('updated_at', $time)->get($filed)->count();
		$this->rent_count    = Roomunionmodel::where($Remove_status)->where('status', 'RENT')->whereBetween('updated_at', $time)->get($filed)->count();
		foreach ($this->details as $key => $value) {
			if (isset(($this->details)[$key]['arrears_count'])) {
				$arrears_count = ($this->details)[$key]['arrears_count'];
			}
		}
		$this->arrears_count = $arrears_count;
		if (isset($where['status']) && $where['status'] == 'ARREARS') {
			$this->details = Roomunionmodel::orderBy('number')->with('room_type')->with('resident')->with('pendOrder')
				->where($Remove_status)->whereBetween('updated_at', $time)->whereHas('order')
				->where('number', 'like', '%' . $number . '%')
				->get($filed)->groupBy('layer')
				->map(function ($s) {
					$s = $s->toArray();
					global $arrears_count;
					$count = 0;
					foreach ($s as $key => $value) {
						$s[$key]['order'] = $s[$key]['pend_order'];
						if (!empty($s[$key]['pend_order'])) {
							$count += 1;
						}
					}
					$arrears_count += $count;
					return [$s, 'arrears_count' => $arrears_count];
				})->toArray();
		} else {
			$this->details = Roomunionmodel::orderBy('number')->with('room_type')->with('resident')->with('pendOrder')
				->where($where)->whereBetween('updated_at', $time)
				->where('number', 'like', '%' . $number . '%')
				->get($filed)->groupBy('layer')
				->map(function ($s) {
					$s = $s->toArray();
					global $arrears_count;
					$count = 0;
					foreach ($s as $key => $value) {
						$s[$key]['order'] = $s[$key]['pend_order'];
						if (!empty($s[$key]['pend_order'])) {
							$count += 1;
						}
					}
					$arrears_count += $count;
					return [$s, 'arrears_count' => $arrears_count];
				})->toArray();
		}
		return $this;
	}
	
	/**
	 * 取消办理, 房间状态置空
	 */
	public function resetRoom($roomId)
	{
		$room = Room::find($roomId);
		
		if (!$room) {
			throw new \Exception('未找到该房间');
		}
		
		$room->update([
			'status'       => Roomunionmodel::STATE_BLANK,
			'people_count' => 0,
			'resident_id'  => 0,
		]);
		
		return true;
	}
	
	/*
	* 检查上传数组
	* */
	public function checkAndGetInputData($sheetArray, $store_id)
	{
		$error = [];
		$store = Storemodel::where('id', $store_id)->select(['name'])->first();
		foreach ($sheetArray as $key => $item) {
			$count = count($item);
			for ($i = 0; $i < $count; $i++) {
				if (empty($item[$i])) {
					$error = '上传数据不能为空';
					return $error;
				}
			}
			//门店名称
			$store_name = $item[0];
			if ($store_name != $store->name) {
				$error[] = '请检查门店名称:' . $store_name . ',与您选择的:' . $store->name . '不相符';
				return $error;
			}
			//房型
			$room_type = trim($item[1]);
			$room      = Roomtypemodel::where('store_id', $store_id)->where('name', $room_type)->select(['id'])->first();
			if (!$room) {
				$error[] = '请检查房型：' . $item[1] . ',查无此房型';
				return $error;
			}
			//租金
			$rent        = trim($item[3]);
			$hot_water   = trim($item[4]);
			$cold_water  = trim($item[5]);
			$property    = trim($item[6]);
			$electricity = trim($item[7]);
			$layer       = trim($item[9]);
			if (!is_numeric($rent) || !is_numeric($hot_water) || !is_numeric($cold_water) || !is_numeric($property) || !is_numeric($layer) || !is_numeric($electricity)) {
				$error[] = '请检查租金：' . $rent . '热水费：' . $hot_water . '冷水费：' . $cold_water . '电费：' . $electricity . '所在层：' . $layer .
					'物业费：' . $property . '必须为数字';
				return $error;
			}
		}
		return $error;
	}
	
	/*
	 * 导入数据
	 * */
	public function writeReading($sheetArray, $store_id)
	{
		$data  = [];
		$error = [];
		foreach ($sheetArray as $key => $value) {
			$arr = Roomunionmodel::where('store_id', $store_id)->where('number', $value[2])->count();
			if (0 == $arr) {
				$data[$key]['company_id']        = 1;
				$data[$key]['store_id']          = $store_id;
				$room                            = Roomtypemodel::where('store_id', $store_id)->where('name', $value[1])->select(['id'])->first();
				$data[$key]['room_type_id']      = $room->id;
				$data[$key]['layer']             = $value[9];
				$data[$key]['number']            = $value[2];
				$data[$key]['rent_price']        = $value[3];
				$data[$key]['property_price']    = $value[4];
				$data[$key]['status']            = Roomunionmodel::STATE_BLANK;
				$data[$key]['created_at']        = Carbon::now();
				$data[$key]['begin_time']        = Carbon::now();
				$data[$key]['end_time']          = Carbon::now();
				$data[$key]['area']              = $value[8];
				$data[$key]['cold_water_price']  = $value[5];
				$data[$key]['hot_water_price']   = $value[6];
				$data[$key]['electricity_price'] = $value[7];
				$data[$key]['type']              = Roomunionmodel::TYPE_UNION;
				try {
					Roomunionmodel::insert($data[$key]);
				} catch (Exception $e) {
					log_message('error', $e->getMessage());
					throw  $e;
				}
			} else {
				$error[] = '房间号：' . $value[2] . '出现重复';
			}
		}
		return $error;
	}
	
}
