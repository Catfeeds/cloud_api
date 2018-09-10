<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/7 0007
 * Time:        14:14
 * Describe:    分布式房间管理
 */
class Roomdot extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * 创建分布式房间
	 */
	public function addDot()
	{
		$field = [
			'store_id', 'community_id', 'building_name', 'unit', 'number', 'layer', 'layer_total', 'room_number',
			'hall_number', 'toilet_number', 'area', 'rooms', 'toward'
		];
		$post  = $this->input->post(null, true);
		//验证房屋
		if (!$this->validationText($this->validateHouseConfig())) {
			$this->api_res(1002, ['error' => $this->form_first_error($field)]);
			return;
		}
		$images = isset($post['images']) ? $post['images'] : null;
		if (!$images || !is_array($images)) {
			$this->api_res(1002, ['error' => '房屋图片不能为空']);
			return;
		}
		//遍历验证房间
		$rooms = isset($post['rooms']) ? $post['rooms'] : null;
		if (!$rooms || !is_array($rooms)) {
			$this->api_res(1002, ['error' => '房间信息不能为空或字符串']);
			return;
		}
		if ($post['room_number'] != count($rooms)) {
			$this->api_res(1002, ['error' => '房间数目不匹配']);
			return;
		}
		for ($i = 0, $count = count($rooms); $i < $count; $i++) {
			if (!$rooms[$i] || !is_array($rooms[$i])) {
				$this->api_res(1002, ['error' => '房间信息不能为空或字符串']);
				return;
			}
			if (!$this->validationText($this->validateDotConfig(), $rooms[$i])) {
				$this->api_res(1002, ['error' => $this->form_first_error($field)]);
				return;
			}
		}
		//加载房屋和房间模型
		$this->load->model('housemodel');
		$this->load->model('roomunionmodel');
		//判断该小区是否存在该房间
		$where['store_id']      = $post['store_id'];
		$where['community_id']  = $post['community_id'];
		$where['building_name'] = $post['building_name'];
		$where['unit']          = $post['unit'];
		$where['layer']         = $post['layer'];
		$where['number']        = $post['number'];
		if (Housemodel::where($where)->first()) {
			$this->api_res(1008);
			return;
		}
		//保存到数据库
		$house = new Housemodel();
		$room  = new Roomunionmodel();
		try {
			DB::beginTransaction();
			//存入房屋信息
			$house->fill($post);
			$house->images = json_encode($this->splitAliossUrl($images, true));
			$a             = $house->save();
			//存入房间信息
			$store_id     = $post['store_id'];
			$community_id = $post['community_id'];
			$house_id     = $house->id;
			$room_insert  = [];
			foreach ($rooms as $key => $value) {
				$room_insert[] = [
					'company_id'   => COMPANY_ID,
					'store_id'     => $store_id,
					'community_id' => $community_id,
					'house_id'     => $house_id,
					'number'       => $value['the_room_number'],
					'area'         => $value['room_area'],
					'feature'      => $value['room_feature'],
					'provides'     => $value['room_provides'],
					'layer'        => $post['layer'],
					'created_at'   => date('Y-m-d H:i:s', time()),
					'updated_at'   => date('Y-m-d H:i:s', time()),
				];
			}
			$b = $room->insert($room_insert);
			if ($a && $b) {
				DB::commit();
				$this->api_res(0);
			} else {
				DB::rollBack();
				$this->api_res(1009);
			}
		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}
	}
	
	/**
	 * 获取小区下空房间
	 */
	public function blankRoomOfCommunity()
	{
		$this->load->model('housemodel');
		$this->load->model('roomunionmodel');
		$community_id = $this->input->post('community_id');
		if (!$community_id) {
			$this->api_res(1005);
			return;
		}
		$rooms = Roomunionmodel::with('house')->where('community_id',$community_id)
			->where('status','BLANK')
			->get(['id','number','rent_price','feature','house_id']);
		$this->api_res(0, ['community' => $rooms]);
	}
	
	/**
	 * 分布式房间列表
	 */
	public function listDot()
	{
		$field  = ['boss_room_dot.id as room_id', 'boss_room_dot.store_id', 'boss_store.name as store_name',
		           'boss_community.name as community_name', 'boss_community.province', 'boss_community.city',
		           'boss_community.district', 'boss_community.address', 'boss_house.id as house_id', 'boss_house.building_name',
		           'boss_house.unit', 'boss_house.number as house_number', 'boss_room_dot.feature', 'boss_room_dot.rent_price', 'boss_room_dot.property_price',
		           'boss_room_dot.keeper', 'boss_room_dot.status', 'boss_room_dot.number as room_number',
		];
		$post   = $this->input->post(null, true);
		$page   = isset($post['page']) ? intval(strip_tags(trim($post['page']))) : 1;
		$offset = PAGINATE * ($page - 1);
		$where  = [];
		(isset($post['store_id']) && !empty($post['store_id'])) ? $where['store_id'] = intval(strip_tags(trim($post['store_id']))) : null;
		(isset($post['community_id']) && !empty($post['community_id'])) ? $where['community_id'] = intval(strip_tags(trim($post['community_id']))) : null;
		$this->load->model('roomdotmodel');
		$count = ceil(Roomdotmodel::where($where)->count() / PAGINATE);
		if ($page > $count) {
			$this->api_res(0, ['count' => $count, 'rooms' => []]);
			return;
		}
		$rooms = Roomdotmodel::leftJoin('boss_store', 'boss_store.id', '=', 'boss_room_dot.store_id')
			->leftJoin('boss_community', 'boss_community.id', '=', 'boss_room_dot.community_id')
			->leftJoin('boss_house', 'boss_house.id', '=', 'boss_room_dot.house_id')
			->select($field)->offset($offset)->limit(PAGINATE)->orderBy('boss_room_dot.id')->where($where)
			->get();
		$this->api_res(0, ['count' => $count, 'rooms' => $rooms]);
	}
	
	/**
	 * 查看分布式房间信息
	 */
	public function getDot()
	{
		$field   = ['id', 'store_id', 'community_id', 'house_id', 'rent_price', 'property_price', 'sort', 'area',
		            'contract_template_long_id', 'contract_template_short_id', 'contract_template_reserve_id',
		            'provides',
		            //            'contract_min_time','contract_max_time','deposit_type','pay_frequency_allow'
		];
		$post    = $this->input->post(null, true);
		$room_id = isset($post['room_id']) ? intval(strip_tags(trim($post['room_id']))) : null;
		if (!$room_id) {
			$this->api_res(1005);
			return;
		}
		//需要关联的 房屋 门店 合同模板
		$this->load->model('roomdotmodel');
		$this->load->model('storemodel');
		$this->load->model('communitymodel');
		$this->load->model('housemodel');
		$this->load->model('contracttemplatemodel');
		$room = Roomdotmodel::with('store')
			->with('house')
			->with('community')
			->with('long_template')
			->with('short_template')
			->with('reserve_template')
			->select($field)->find($room_id);
		if (!$room) {
			$this->api_res(1007);
		} else {
			$all_room      = Roomdotmodel::where('house_id', $room->house_id)
				->with('long_template')
				->with('short_template')
				->with('reserve_template')
				->orderBy('sort')->get($field);
			$room['rooms'] = $all_room;
			$this->api_res(0, ['room' => $room]);
		}
	}
	
	/**
	 * 批量删除分布式房间
	 */
	public function destroy()
	{
		$id = $this->input->post('room_id', true);
		if (!is_array($id)) {
			$this->api_res(1005);
			return;
		}
		$this->load->model('roomunionmodel');
		if (Roomunionmodel::destroy($id)) {
			$this->api_res(0);
		} else {
			$this->api_res(1009);
		}
	}
	
	/**
	 * 提交分布式查看信息编辑
	 */
	public function submitDot()
	{
		$field = [];
		$post  = $this->input->post(null, true);
		
	}
	
	/**
	 * 提交编辑验证house信息
	 */
	private function validateSubimitHouse()
	{
		
		return [
			[
				'field' => 'house_id',
				'label' => '房屋id',
				'rules' => 'trim|required|integer',
			],
		];
	}
	
	/**
	 *
	 */
	private function validateSubimitDot()
	{
		
		return [
		
		];
	}
	
	/**
	 * 添加分布式房间的验证规则
	 */
	public function validateHouseConfig()
	{
		$config = [
			[
				'field' => 'store_id',
				'label' => '门店id',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'community_id',
				'label' => '小区id',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'building_name',
				'label' => '楼栋名称',
				'rules' => 'trim|required',
			],
			[
				'field' => 'unit',
				'label' => '单元号',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'layer',
				'label' => '所在楼层',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'layer_total',
				'label' => '总楼层',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'number',
				'label' => '房屋号',
				'rules' => 'trim|required',
			],
			[
				'field' => 'room_number',
				'label' => '房间数量',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'hall_number',
				'label' => '客厅数量',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'toilet_number',
				'label' => '卫生间数量',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'area',
				'label' => '房屋面积',
				'rules' => 'trim|required|numeric',
			],
			[
				'field' => 'toward',
				'label' => '朝向',
				'rules' => 'trim|required|in_list[E,W,S,N,EW,SN]',
			],
		];
		return $config;
	}
	
	/**
	 * 验证分布式房间信息
	 */
	public function validateDotConfig()
	{
		$config = [
			[
				'field' => 'the_room_number',
				'label' => '房间号',
				'rules' => 'required|trim',
			],
			[
				'field' => 'room_area',
				'label' => '房间面积',
				'rules' => 'required|trim|numeric',
			],
			[
				'field' => 'room_feature',
				'label' => '房间特色',
				'rules' => 'required|trim|in_list[M,S,MT]',
			],
			[
				'field' => 'room_provides',
				'label' => '房间配套',
				'rules' => 'required|trim',
			],
		];
		return $config;
	}
	
	/**
	 * 验证支付周期
	 */
	public function validatePayConfig()
	{
		$config = [
			[
				'field' => 'pay_frequency_allow',
				'label' => '允许的支付周期',
				'rules' => 'trim|required|integer|in_list[1,2,3,6,12,24]',
			],
		];
		return $config;
	}
	
	/**
	 * 批量编辑分布式验证规则
	 */
	public function validateBatchDotConfig()
	{
		$config = [
			[
				'field' => 'store_id',
				'label' => '门店id',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'community_id',
				'label' => '小区id',
				'rules' => 'trim|integer|required',
			],
			[
				'field' => 'contract_template_long_id',
				'label' => '选择长租合同模板',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'contract_template_short_id',
				'label' => '选择短租合同模板',
				'rules' => 'trim|required|integer',
			],
			[
				'field' => 'contract_template_reserve_id',
				'label' => '选择预定合同模板',
				'rules' => 'trim|required|integer',
			],
			//            array(
			//                'field' => 'contract_min_time',
			//                'label' => '合同最少签约期限（以月份计）',
			//                'rules' => 'trim|required|integer'
			//            ),
			//            array(
			//                'field' => 'contract_max_time',
			//                'label' => '合同最多签约期限（以月份计）',
			//                'rules' => 'trim|required|integer'
			//            ),
			//            array(
			//                'field' => 'deposit_type',
			//                'label' => '押金信息',
			//                'rules' => 'trim|required|in_list[FREE]'
			//            ),
		];
		return $config;
	}
	
}
