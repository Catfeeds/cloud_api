<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/23
 * Time:        10:11
 * Describe:    房间管理
 */
class Room extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('employeemodel');
        $this->load->model('roomunionmodel');
    }

    /**
     * 展示房间列表
     *
    select u.`id`, u.`layer`, u.`status`, u.`number`, u.`room_type_id`
    from `boss_room_union` as u

    inner join `boss_order` as oo
    on u.`id` = oo.`room_id`



    where (u.`store_id` = 8) and u.`deleted_at` is null
    and oo.`status` in ('GENERATE', 'AUDITED', 'PENDING')
    and oo.`deleted_at` is null
    order by u.`number` asc
     */
    public function listRoom() {
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $post  = $this->input->post(null, true);
        $where = [];
        if (!empty($post['building_id'])) {$where['building_id'] = intval($post['building_id']);};
        if (!empty($post['status'])) {$status = $post['status'];} else { $status = null;};
        $where['store_id'] = $this->employee->store_id;
        $filed = ['id', 'layer', 'status', 'number', 'room_type_id', 'resident_id'];
        $this->load->model('roomtypemodel');
        if ($status == 'ARREARS') {
            $room = Roomunionmodel::with('room_type')->with('pendOrder')
                ->where($where)
                ->orderBy('number', 'ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room) {
                    $roominfo                  = $room->toArray();
                    $roominfo['count_total']   = count($room);
                    $roominfo['count_rent']    = 0;
                    $roominfo['count_blank']   = 0;
                    $roominfo['count_arrears'] = 0;
                    $roominfo['count_repair']  = 0;
                    $roominfo['count_due']     = 0;
                    for ($i = 0; $i < $roominfo['count_total']; $i++) {
                        $status = $roominfo[$i]['status'];
                        if (!empty($roominfo[$i]['pend_order'])) {
                            $room[$i]->order = $roominfo[$i]['pend_order'];
                            $roominfo['count_arrears'] += 1;
                        }
                    }
                    return [$room, 'count' => [
                        'count_total'   => $roominfo['count_total'],
                        'count_rent'    => $roominfo['count_rent'],
                        'count_blank'   => $roominfo['count_blank'],
                        'count_arrears' => $roominfo['count_arrears'],
                        'count_repair'  => $roominfo['count_repair'],
                        'count_due'     => $roominfo['count_due'],
                    ]];
                })
                ->toArray();
        } elseif ($status == 'DUE') {
            $room = Roomunionmodel::with('room_type')->with('due') //->with('order')
            ->where($where)->orderBy('number', 'ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room) {
                    $roominfo                  = $room->toArray();
                    $roominfo['count_total']   = count($room);
                    $roominfo['count_rent']    = 0;
                    $roominfo['count_blank']   = 0;
                    $roominfo['count_arrears'] = 0;
                    $roominfo['count_repair']  = 0;
                    $roominfo['count_due']     = 0;
                    for ($i = 0; $i < $roominfo['count_total']; $i++) {
                        if (!empty($roominfo[$i]['due'])) {
                            $roominfo['count_due'] += 1;
                        }
                    }
                    return [$room, 'count' => [
                        'count_total'   => $roominfo['count_total'],
                        'count_rent'    => $roominfo['count_rent'],
                        'count_blank'   => $roominfo['count_blank'],
                        'count_arrears' => $roominfo['count_arrears'],
                        'count_repair'  => $roominfo['count_repair'],
                        'count_due'     => $roominfo['count_due'],
                    ]];
                })
                ->toArray();
        } elseif ($status == null) {
            $room = Roomunionmodel::with('room_type')->with('due')->with('pendOrder')
                ->where($where)->orderBy('number', 'ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room) {
                    $roominfo                  = $room->toArray();
                    $roominfo['count_total']   = count($room);
                    $roominfo['count_rent']    = 0;
                    $roominfo['count_blank']   = 0;
                    $roominfo['count_arrears'] = 0;
                    $roominfo['count_repair']  = 0;
                    $roominfo['count_due']     = 0;
                    for ($i = 0; $i < $roominfo['count_total']; $i++) {
                        $status = $roominfo[$i]['status'];
                        if ($status == 'RENT') {
                            $roominfo['count_rent'] += 1;
                        }
                        if ($status == 'BLANK') {
                            $roominfo['count_blank'] += 1;
                        }
                        if (!empty($roominfo[$i]['pend_order'])) {
                            $room[$i]->order = $roominfo[$i]['pend_order'];
                            $roominfo['count_arrears'] += 1;
                        }
                        if (!empty($roominfo[$i]['due'])) {
                            $roominfo['count_due'] += 1;
                        }
                        if ($status == 'REPAIR') {
                            $roominfo['count_repair'] += 1;
                        }
                    }
                    return [$room, 'count' => [
                        'count_total'   => $roominfo['count_total'],
                        'count_rent'    => $roominfo['count_rent'],
                        'count_blank'   => $roominfo['count_blank'],
                        'count_arrears' => $roominfo['count_arrears'],
                        'count_repair'  => $roominfo['count_repair'],
                        'count_due'     => $roominfo['count_due'],
                    ]];
                })
                ->toArray();
        } else {
            $room = Roomunionmodel::with('room_type')
                ->where($where)->where('status', $status)->orderBy('number', 'ASC')
                ->get($filed)->groupBy('layer')
                ->map(function ($room) {
                    $roominfo                  = $room->toArray();
                    $roominfo['count_total']   = count($room);
                    $roominfo['count_rent']    = 0;
                    $roominfo['count_blank']   = 0;
                    $roominfo['count_arrears'] = 0;
                    $roominfo['count_repair']  = 0;
                    $roominfo['count_due']     = 0;
                    for ($i = 0; $i < $roominfo['count_total']; $i++) {
                        $status = $roominfo[$i]['status'];
                        if ($status == 'RENT') {
                            $roominfo['count_rent'] += 1;
                        }
                        if ($status == 'BLANK') {
                            $roominfo['count_blank'] += 1;
                        }
                        if (!empty($roominfo[$i]['order'])) {
                            $roominfo['count_arrears'] += 1;
                        }
                        if (!empty($roominfo[$i]['due'])) {
                            $roominfo['count_due'] += 1;
                        }
                        if ($status == 'REPAIR') {
                            $roominfo['count_repair'] += 1;
                        }
                    }
                    return [$room, 'count' => [
                        'count_total'   => $roominfo['count_total'],
                        'count_rent'    => $roominfo['count_rent'],
                        'count_blank'   => $roominfo['count_blank'],
                        'count_arrears' => $roominfo['count_arrears'],
                        'count_repair'  => $roominfo['count_repair'],
                        'count_due'     => $roominfo['count_due'],
                    ]];
                })
                ->toArray();
        }
        $this->api_res(0, ['list' => $room]);
    }
	
	/**
	 * 处理上传读数
	 */
    public function details()
    {
	    $this->load->model('storemodel');
	    $post     = $this->input->post(null, true);
	    $where    = [];
	    $store_id = $this->employee->store_id;
	    //房间状态
	    $status = trim($post['status']);
	    $where['boss_room_union.store_id'] = $store_id;
	    //判断房间类型
	    $type = Storemodel::where('id', $store_id)->first(['rent_type']);
	    if ($type->rent_type == 'DOT') {
		    if (!empty($post['community_id'])) {
			    $where['boss_room_union.community_id'] = intval($post['community_id']);
		    }
		    $this->dotRooms($where, $status);
		    return;
	    } elseif ($type->rent_type == 'UNION') {
		    $this->unionRooms($where, $status);
		    return;
	    } else {
		    $this->api_res(0);
	    }
    }
	
	/**
	 * 获取分布式房间列表
	 */
    public function unionRooms($where, $status)
    {
        $rooms = Roomunionmodel::where($where);
    }
	
	/**
	 * 获取分布式房间列表
	 */
	public function dotRooms()
	{
		$post     = $this->input->post(null, true);
		$where    = [];
		$store_id = $this->employee->store_id;
		//房间状态
		if (!empty($post['status'])){
			if(!in_array(trim($post['status']),['ARREARS','DUE','RENT','BLANK','OCCUPY'])){
				$this->api_res(1002);
				return;
			}
		}
		$status = trim($post['status']);
		$where['boss_room_union.store_id'] = $store_id;
		if (!empty($post['community_id'])) {
			$where['boss_room_union.community_id'] = intval($post['community_id']);
		}
		$filed = [
			'boss_room_union.id as room_id', 'boss_room_union.number as room_number',
			'boss_room_union.house_id as house_id','boss_room_union.status',
			'boss_room_union.rent_price as room_price','boss_room_union.end_time',
			'boss_order.status as order_status',
			'boss_community.name as c_name','boss_house.building_name','boss_house.unit',
			'boss_house.number as house_number', 'boss_room_union.feature',
		];
		if ($status == 'ARREARS') {
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
				->where('boss_room_union.status','RENT')
				->groupBy('boss_room_union.id')
				->get()->map(function ($room) {
					$room->status = Roomunionmodel::STATE_ARREARS;
					$room->address = $room->c_name.$room->building_name.'(栋)'.$room->unit.'(单元)'.$room->house_number;
					return $room;
				})
				->groupBy('address');
		}
		elseif ($status == 'DUE'){
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
				->where('boss_room_union.status','RENT')
				->where('boss_room_union.end_time','<=',date('Y-m-d H:i:s',strtotime('+30days')))
				->groupBy('boss_room_union.id')
				->get()->map(function ($room) {
					$room->address = $room->c_name.$room->building_name.'(栋)'.$room->unit.'(单元)'.$room->house_number;
					return $room;
				})
				->groupBy('address');
		}else{
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
				->groupBy('boss_room_union.id')
				->get()->map(function ($room) {
					if ($room->status == Roomunionmodel::STATE_RENT && $room->order_status == 'PENDING') {
						$room->status = Roomunionmodel::STATE_ARREARS;
					}
					if ($room->status == Roomunionmodel::STATE_RENT && $room->end_time != NULL && $room->end_time <= date('Y-m-d H:i:s',strtotime('+30days'))) {
						$room->status = 'DUE';
					}
					$room->address = $room->c_name.$room->building_name.'(栋)'.$room->unit.'(单元)'.$room->house_number;
					return $room;
				})
				->groupBy('address');
		}
		$this->api_res(0,['list'=>$rooms]);
	}
    
    public function listOrderRoom(){
        $this->load->model('ordermodel');
        $this->load->model('residentmodel');
        $post  = $this->input->post(null, true);
        $where = [];
        if (!empty($post['building_id'])) {$where['building_id'] = intval($post['building_id']);};
        if (!empty($post['status'])) {$status = $post['status'];} else { $status = null;};
        $where['store_id'] = $this->employee->store_id;
        if(empty($post['building_id'])){
            $post['building_id'] = "4,5";
        }
        $this->load->model('roomtypemodel');
        if ($status == 'ARREARS') {
            $room   =  DB::select("select u.`id`, u.`layer`, u.`status`,u.`building_id` ,u.`number`, u.`room_type_id`, ty.`id` as `room_type_id`,ty.`name`  ".
                " from `boss_room_union` as u ".
                " left join `boss_order` as oo on u.`id` = oo.`room_id`".
                " left join `boss_room_type` as ty on u.`room_type_id` = ty.`id`".
                " where (u.`store_id` = ?) and u.`deleted_at` is null and (oo.`company_id` = ".$this->company_id.")".
                "          and oo.`status` in ('PENDING')  ".
                "          and oo.`deleted_at` is null ".
                "          and u.`building_id` in(".$post['building_id'].")".
                "          group by u.`id`".
                " order by u.`number` asc ",[$where['store_id']]);
        } elseif ($status == 'DUE') {
            $room   =  DB::select("select u.`id`, u.`layer`, u.`status`, u.`number`, u.`room_type_id`, ty.`name`,".
                "re.`id` as `resident_id`, re.`end_time`, re.`room_id` ".
                " from `boss_room_union` as u ".
                " left join `boss_resident` as re on u.`id` = re.`room_id`".
                " left join `boss_room_type` as ty on u.`room_type_id` = ty.`id`".
                " where (u.`store_id` = ?) and u.`deleted_at` is null ".
                " and re.`end_time` between '".date('Y-m-d H:i:s', time())."' and '". date('Y-m-d H:i:s', strtotime('+1month'))."'  ".
                "         and re.`deleted_at` is null ".
                "          and u.`building_id` in(".$post['building_id'].")".
                "         group by u.`id`".
                " order by u.`number` asc ",[$where['store_id']]);
        } else {
            $room   =  DB::select("select u.`id`, u.`layer`, u.`status`, u.`number`, u.`room_type_id`, ty.`name`".
                " from `boss_room_union` as u ".
                " left join `boss_room_type` as ty on u.`room_type_id` = ty.`id`".
                " where (u.`store_id` = ?) and u.`deleted_at` is null ".
                "         and u.`building_id` in(".$post['building_id'].")".
                "         and u.`status` = '".$status."' ".
                "         group by u.`id`".
                " order by u.`number` asc ",[$where['store_id']]);
        }
        $this->api_res(0, ['list' => $room]);

    }
    /**
     *  门店下的房间状态统计
     */
    public function countRoom() {
//        $post = $this->input->post(null,true);
        //        if ($post['store_id']){
        $store_id               = $this->employee->store_id;
        $room                   = Roomunionmodel::where('store_id', $store_id)->get(['id', 'status'])->toArray();
        $count                  = [];
        $count['count_total']   = count($room);
        $count['count_blank']   = 0;
        $count['count_rent']    = 0;
        $count['count_arrears'] = 0;
        for ($i = 0; $i < $count['count_total']; $i++) {
            $status = $room[$i]['status'];
            if ($status == 'RENT') {
                $count['count_rent'] += 1;
            }
            if ($status == 'BLANK') {
                $count['count_blank'] += 1;
            }
            if ($status == 'ARREARS') {
                $count['count_arrears'] += 1;
            }
        }
        $this->api_res(0, $count);

//        }else{
        //            $this->api_res(1002);
        //        }
    }

    /**
     * 房间详情
     */
    public function detailsRoom() {
        $post    = $this->input->post(null, true);
        $id      = isset($post['id']) ? intval($post['id']) : null;
        $details = Roomunionmodel::where('id', $id)->get(['people_count', 'resident_id', 'arrears']);
        $this->api_res(0, $details);
    }

    public function building() {
        $this->load->model('buildingmodel');
        $post = $this->input->post(null, true);
        if ($post['store_id']) {
            $store_id = intval($post['store_id']);
            $building = Buildingmodel::where('store_id', $store_id)->get(['id', 'name'])->toArray();
            $this->api_res(0, $building);
        } else {
            $this->api_res(0, []);
        }
    }

}
