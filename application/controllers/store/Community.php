<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/4 0004
 * Time:        10:39
 * Describe:    小区管理(分布式)
 */
class Community extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('communitymodel');
	}
	
	/**
	 * 添加小区
	 */
	public function addCommunity()
	{
		$post = $this->input->post(null, true);
		$field = [
			'store_id', 'name', 'province', 'city', 'district', 'address', 'describe', 'images'
		];
		$this->debug('传入参数为-->',$post);
		if (!$this->validationText($this->validateConfig())) {
			$this->api_res(1002, ['error' => $this->form_first_error($field)]);
			return;
		}
		if (Communitymodel::where('name', $post['name'])->first()) {
			$this->api_res(1008);
			return;
		}
		$community = new Communitymodel();
		$community->fill($post);
		$community->images = json_encode($this->splitAliossUrl($post['images'], true),true);
		if ($community->save()) {
			$this->api_res(0, ['community_id' => $community->id]);
		} else {
			$this->api_res(1009);
		}
	}
	
	/**
	 * 小区列表
	 */
	public function listCommunity()
	{
		$field  = [
			'id', 'store_id', 'name', 'province', 'city', 'district', 'sale', 'address','images'
		];
		$post   = $this->input->post(null, true);
		$page   = intval(isset($post['page']) ? $post['page'] : 1);
		$offset = $offset = PAGINATE * ($page - 1);
		$name   = !empty($post['name']) ? $post['name'] : '';
		(isset($post['store_id']) && !empty($post['store_id'])) ? $where['store_id'] = $post['store_id'] : $where = [];
		
		$count = ceil(Communitymodel::where($where)->where('name', 'like', "%$name%")->count() / PAGINATE);
		if ($page > $count) {
			$this->api_res(0, ['count' => $count, 'community' => []]);
			return;
		}
		$this->load->model('roomunionmodel');
		$communitys = Communitymodel::with('room')->where($where)->where('name', 'like', "%$name%")->offset($offset)->limit(PAGINATE)
			->get($field)
			->map(function ($community) {
				$community['count'] = $community->room->count();
				$community->images  = $this->fullAliossUrl(json_decode($community->images,true),true);
				return $community;
			});
		$this->api_res(0, ['count' => $count, 'community' => $communitys]);
	}
	
	/**
	 * 编辑小区信息
	 */
	public function updateCommunity()
	{
		$post         = $this->input->post(null, true);
		$field = [
			'store_id', 'name', 'province', 'city', 'district', 'address', 'describe', 'images'
		];
		if (!$this->validationText($this->validateConfig())) {
			$this->api_res(1002, ['error' => $this->form_first_error($field)]);
			return;
		}
		$community_id = $this->input->post('community_id', true);
		$community    = Communitymodel::findOrFail($community_id);
		$community->fill($post);
		$community->images = json_encode($this->splitAliossUrl($post['images'],true),true);
		if ($community->save()) {
			$this->api_res(0, ['community_id' => $community->id]);
		} else {
			$this->api_res(1009);
		}
	}
	
	/**
	 * 下架小区
	 */
	public function deleteCommunity()
	{
		$community_id = $this->input->post('community_id', true);
		if (!$community_id) {
			$this->api_res(1005);
			return;
		}
		if (!$community = Communitymodel::find($community_id)) {
			$this->api_res(1007);
			return;
		}
		$community->sale = "N";
		if ($community->save()) {
			$this->api_res(0);
		} else {
			$this->api_res(1009);
		}
	}
	
	/**
	 * 查看小区信息
	 */
	public function getCommunity()
	{
		$community_id = $this->input->post('community_id', true);
		$community = Communitymodel::where('id',$community_id)->get()->map(function ($s){
			$s->images = $this->fullAliossUrl(json_decode($s->images,true),true);
			return $s;
		});
		if ($community) {
			$this->api_res(0, ['community' => $community]);
		} else {
			$this->api_res(1007);
		}
	}
	
	/**
	 * 批量删除
	 */
	public function destroyCommunity()
	{
		$id = $this->input->post('community_id', true);
		if (!is_array($id) || empty($id)) {
			$this->api_res(1005);
			return;
		}
		if (Communitymodel::destroy($id)) {
			$this->api_res(0);
		} else {
			$this->api_res(1009);
		}
	}
	
	/**
	 * 获取门店下的小区
	 */
	public function showCommunity()
	{
		$store_id = $this->input->post('store_id');
		if (!$store_id) {
			$this->api_res(1005);
			return;
		}
		$where['store_id'] = $store_id;
		$community         = Communitymodel::where($where)->get(['id', 'name']);
		$this->api_res(0, ['community' => $community]);
	}
	
	/**
	 * @return array
	 * 小区字段的验证规则
	 */
	private function validateConfig()
	{
		$config = [
			[
				'field' => 'store_id',
				'label' => '门店id',
				'rules' => 'required|trim',
			],
			[
				'field'  => 'name',
				'label'  => '小区名称',
				'rules'  => 'required|trim|max_length[20]',
				'errors' => [
					'required' => '小区名称不能为空.',
				],
			],
			
			[
				'field' => 'province',
				'label' => '省份',
				'rules' => 'required|trim',
			],
			[
				'field' => 'city',
				'label' => '城市',
				'rules' => 'required|trim',
			],
			[
				'field' => 'district',
				'label' => '区',
				'rules' => 'trim',
			],
			[
				'field' => 'address',
				'label' => '地址',
				'rules' => 'required|trim',
			],
			[
				'field' => 'describe',
				'label' => '门店描述',
				'rules' => 'required|trim',
			],
			[
				'field' => 'images[]',
				'label' => '图片',
				'rules' => 'required|trim',
			],
		];
		return $config;
	}
	
}
