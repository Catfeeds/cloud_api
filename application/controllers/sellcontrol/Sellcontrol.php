<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/25
 * Time:        18:37
 * Describe:    销控管理
 */
class Sellcontrol extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 集中式销控列表
	 */
	public function details1()
	{
		$this->load->model('roomtypemodel');
		$this->load->model('residentmodel');
		$this->load->model('ordermodel');
		$this->load->model('storemodel');
		$post     = $this->input->post(null, true);
		$where    = [];
		$store_id = $this->employee->store_id;
		if (!empty($post['status'])) {
			$where['status'] = trim($post['status']);
		};
		if (!empty($post['store_id'])) {
			$where['store_id'] = intval($post['store_id']);
		} else {
			$where['store_id'] = $store_id;
		}
		$type = Storemodel::where('id', $where['store_id'])->first(['rent_type']);
		if ($type->rent_type == 'DOT') {
			$this->dot($post);
			return;
		}
		$number    = isset($post['number']) ? trim($post['number']) : null;
		$filed     = ['id', 'layer', 'status', 'room_type_id', 'number', 'rent_price', 'resident_id', 'store_id'];
		$roomunion = new Roomunionmodel();
		if (!empty($post['BLANK_days'])) {
			$days            = $post['BLANK_days'];
			$where['status'] = "BLANK";
			switch ($days) {
				case 1:
					$time = [date('Y-m-d H:i:s', strtotime('-10 day', time())), date('Y-m-d H:i:s', time())];
					$list = $roomunion->room_details($where, $filed, $time, $number);
					break;
				case 2:
					$time = [date('Y-m-d H:i:s', strtotime('-20 day', time())), date('Y-m-d H:i:s', strtotime('-10 day', time()))];
					$list = $roomunion->room_details($where, $filed, $time, $number);
					break;
				case 3;
					$time = [date('Y-m-d H:i:s', strtotime('-30 day', time())), date('Y-m-d H:i:s', strtotime('-20 day', time()))];
					$list = $roomunion->room_details($where, $filed, $time, $number);
					break;
				case 4:
					$time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
					$list = $roomunion->room_details($where, $filed, $time, $number);
					break;
				default:
					$time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
					$list = $roomunion->room_details($where, $filed, $time, $number);
					break;
			}
		} else {
			$time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
			$list = $roomunion->room_details($where, $filed, $time, $number);
		}
		$this->api_res(0, $list);
	}
	
	/**
	 * 处理前端上传数据,并判断房间类型(分布式/集中式)
	 */
	public function details()
	{
		$this->load->model('storemodel');
		$post     = $this->input->post(null, true);
		$where    = [];
		$store_id = $this->employee->store_id;
		//房间状态
		$status = trim($post['status']);
		//房间空置时长
		$time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
		if ($status == 'BLANK') {
			if (!empty($post['days'])) {
				$time = $this->getTime($post['days']);
			}
		}
		//门店ID
		if (!empty($post['store_id'])) {
			$where['boss_room_union.store_id'] = intval($post['store_id']);
		} else {
			$where['boss_room_union.store_id'] = $store_id;
		}
		$number = isset($post['number']) ? trim($post['number']) : null;
		//判断房间类型
		$type = Storemodel::where('id', $post['store_id'])->first(['rent_type']);
		if ($type->rent_type == 'DOT') {
			if (!empty($post['community_id'])) {
				$where['boss_room_union.community_id'] = intval($post['community_id']);
			}
			$this->dotSellCon($where, $status, $number, $time);
			return;
		} elseif ($type->rent_type == 'UNION') {
			$this->unionSellCon($where, $status, $number, $time);
			return;
		} else {
			$this->api_res(0);
		}
	}
	
	/**
	 * 分布式销控列表
	 */
	private function dotSellCon($where, $status = '', $number = '', $day)
	{
		/*var_dump($where);
		var_dump($status);
		var_dump($number);
		var_dump($day);*/
		$this->load->model('roomunionmodel');
		$room = new Roomunionmodel();
		$rooms = $room->dot_rooms($where, $status, $number, $day);
		$count = $room->getRoomCount($where);
		$this->api_res(0, ['count'=>$count,'rooms' => $rooms]);
	}
	
	/**
	 * 集中式销控列表
	 */
	private function unionSellCon($where, $status = '', $number = '', $day)
	{
		$this->load->model('roomunionmodel');
		$room  = new Roomunionmodel();
		$rooms = $room->uniuon_rooms($where, $status, $number, $day);
		$count = $room->getRoomCount($where);
		$this->api_res(0, ['count'=>$count,'rooms' => $rooms]);
	}
	
	/**
	 * 按空置天数得到时间查询条件
	 */
	private function getTime($days)
	{
		switch ($days) {
			case 1:
				$time = [date('Y-m-d H:i:s', strtotime('-10 day', time())), date('Y-m-d H:i:s', time())];
				break;
			case 2:
				$time = [date('Y-m-d H:i:s', strtotime('-20 day', time())), date('Y-m-d H:i:s', strtotime('-10 day', time()))];
				break;
			case 3;
				$time = [date('Y-m-d H:i:s', strtotime('-30 day', time())), date('Y-m-d H:i:s', strtotime('-20 day', time()))];
				break;
			case 4:
				$time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
				break;
			default:
				$time = [date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time())];
				break;
		}
		return $time;
	}
}