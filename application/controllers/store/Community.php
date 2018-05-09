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
        $field  = [
            'store_id','name','province','city','district','address','describe','history','shop','relax','bus'
        ];
        if(!$this->validationText($this->validateConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post   = $this->input->post(null,true);
        $community  = new Communitymodel();
        $community->fill($post);
//        if(!isset($post['images']))
//        {
//            $this->api_res(1002,['error'=>'没有上传小区图片']);
//            return;
//        }
//        $community->images  = json_encode($this->splitAliossUrl($post['images'],true));
        if($community->save())
        {
            $this->api_res(0,['community_id'=>$community->id]);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 小区列表
     */
    public function listCommunity()
    {
        $field  = [
            'id','store_id','name','province','city','district','address','describe','history','shop','relax','bus'
        ];
        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = $offset = PAGINATE*($page-1);
        isset($post['store_id'])?$where['store_id']=$post['store_id']:$where=[];
        $count  = ceil(Communitymodel::where($where)->count()/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'community'=>[]]);
            return;
        }
        $this->load->model('roomdotmodel');
        $this->load->model('storemodel');
        $communitys  = Communitymodel::with('room')->with('store')->where($where)->offset($offset)->limit(PAGINATE)
            ->get($field)
            ->map(function($community)
            {
               $community['count'] = $community->room->count();
               return $community;
            });
        $this->api_res(0,['count'=>$count,'community'=>$communitys]);
    }

    /**
     * 搜索小区（按名称）
     */
    public function searchCommunity()
    {
        $field  = [
            'id','store_id','name','province','city','district','address'
        ];
        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = $offset = PAGINATE*($page-1);
        isset($post['store_id'])?$where['store_id']=$post['store_id']:$where=[];
        $name   = isset($post['name'])?$post['name']:'';
        $count  = ceil(Communitymodel::where($where)->where('name','like',"%$name%")->count()/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'community'=>[]]);
            return;
        }
        $this->load->model('roomdotmodel');
        $this->load->model('storemodel');
        $communitys  = Communitymodel::with('room')->with('store')->where($where)->where('name','like',"%$name%")->offset($offset)->limit(PAGINATE)
            ->get($field)
            ->map(function($community)
            {
                $community['count'] = $community->room->count();
                return $community;
            });
        $this->api_res(0,['count'=>$count,'community'=>$communitys]);
    }

    /**
     * 查看小区信息
     */
    public function getCommunity()
    {
        $community_id   = $this->input->post('community_id',true);
        $this->load->model('storemodel');
        if($community  = Communitymodel::with('store')->find($community_id)){
            $this->api_res(0,['community'=>$community]);
        }else{
            $this->api_res(1007);
        }
    }

    /**
     * 编辑小区信息
     */
    public function updateCommunity()
    {
        $field  = [
            'store_id','name','province','city','district','address','describe','history','shop','relax','bus'
        ];
        if(!$this->validationText($this->validateConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post   = $this->input->post(null,true);
        $community_id   = $this->input->post('community_id',true);
        $community  = Communitymodel::findOrFail($community_id);
        $community->fill($post);
//        if(!isset($post['images']))
//        {
//            $this->api_res(1002,['error'=>'没有上传小区图片']);
//            return;
//        }
//        $community->images  = json_encode($this->splitAliossUrl($post['images'],true));
        if($community->save())
        {
            $this->api_res(0,['community_id'=>$community->id]);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 删除小区
     */
    public function deleteCommunity()
    {
        $community_id   = $this->input->post('company_id',true);
        if(Communitymodel::find($community_id)->delete())
        {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 获取门店下的小区
     */
    public function showCommunity(){
        $store_id   = $this->input->post('store_id');
        if(!$store_id){
            $this->api_res(1005);
            return;
        }
        $where['store_id']  = $store_id;
        $community  = Communitymodel::where($where)->get(['id','name']);
        $this->api_res(0,['community'=>$community]);
    }

    /**
     * @return array
     * 小区字段的验证规则
     */
    private function validateConfig()
    {
        $config = [
            array(
                'field' => 'store_id',
                'label' => '门店id',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'name',
                'label' => '小区名称',
                'rules' => 'required|trim|max_length[20]',
                'errors'=> array(
                    'required' => '小区名称不能为空.',
                )
            ),

            array(
                'field' => 'province',
                'label' => '省份',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'city',
                'label' => '城市',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'district',
                'label' => '区',
                'rules' => 'trim',
            ),
            array(
                'field' => 'address',
                'label' => '地址',
                'rules' => 'required|trim',
            ),
            /*array(
                'field' => 'images',
                'label' => '门店图片',
                'rules' => 'required|trim',
            ),*/
            array(
                'field' => 'describe',
                'label' => '门店描述',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'history',
                'label' => '配套医院',
                'rules' => 'trim',
            ),
            array(
                'field' => 'shop',
                'label' => '配套商场',
                'rules' => 'trim',
            ),
            array(
                'field' => 'relax',
                'label' => '配套休闲',
                'rules' => 'trim',
            ),
            array(
                'field' => 'bus',
                'label' => '配套交通',
                'rules' => 'trim',
            ),
        ];
        return $config;
    }

}
