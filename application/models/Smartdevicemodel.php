<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:12
 * Describe:    BOSS
 *智能设备表
 */
class Smartdevicemodel extends Basemodel
{
	
	protected $table = 'boss_smart_device';
	protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
	
	public function room()
	{
		return $this->belongsTo(Roomunionmodel::class, 'room_id')
			->select('id', 'layer', 'number', 'store_id', 'building_name');
	}
	
	public function store()
	{
		return $this->belongsTo(Storemodel::class, 'store_id')->select('id');
	}
	
	//按房间门店和设备类型来补全设备
	public function addDevice($post)
	{
		$where                             = [];
		$where['boss_room_union.store_id'] = $post['store_id'];
		$where['boss_smart_device.type']   = $post['type'];
		//设备表中已有房间ID
		$room_ids     = Roomunionmodel::leftJoin('boss_smart_device', 'boss_room_union.id', '=', 'boss_smart_device.room_id')
			->where($where)
			->select(['boss_room_union.id'])
			->get()->toArray();
		$part_room_id = [];
		if (!empty($room_ids)) {
			foreach ($room_ids as $k => $v) {
				$part_room_id[] = $v['id'];
			}
		}
		//所有房间ID
		$room_ids    = Roomunionmodel::where('store_id', $post['store_id'])->get(['id'])->toArray();
		$all_room_id = [];
		if (!empty($room_ids)) {
			foreach ($room_ids as $k => $v) {
				$all_room_id[] = $v['id'];
			}
		}
		//设备表中没有的房间ID
		$room_ids    = array_diff($all_room_id, $part_room_id);
		$device_info = [];
		if (!empty($room_ids)) {
			foreach ($room_ids as $k) {
				$device                  = [];
				$device['room_id']       = $k;
				$device['type']          = $post['type'];
				$device['serial_number'] = $this->randomString();
				$device['supplier']      = 'FUNXDATA';
				$device['store_id']      = $post['store_id'];
				$device['created_at']    = date('Y-m-d H:i:s', time());
				$device['updated_at']    = date('Y-m-d H:i:s', time());
				$device_info[]           = $device;
			}
		}
		Smartdevicemodel::insert($device_info);
		return true;
	}
	
	public function randomString()
	{
		//使用uniqid mt_rand 生成随机不重复字符串的方法
		$prefix = 'funx';
		//字符串前缀
		$snKeys = $prefix . md5(uniqid(mt_rand(), true));
		return $snKeys;
	}
}
