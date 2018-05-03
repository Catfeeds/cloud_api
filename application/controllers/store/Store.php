<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/26 0026
 * Time:        16:47
 * Describe:    门店管理
 */
class Store extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        /*if(!$this->position){
            throw new Exception();
        }*/
        $this->load->model('storemodel');
    }

    /**
     * 房间列表
     * post page 页码
     * return count 总页数，list 门店列表
     */
    public function listStore()
    {
        $post   = $this->input->post(null,true);
        $page   = isset($post['page'])?$post['page']:1;
        $where  = [];
        isset($post['city'])?$where['city']=$post['city']:null;
        isset($post['type'])?$where['rent_type']=$post['type']:null;
        isset($post['status'])?$where['status']=$post['status']:null;
        $offset = PAGINATE*($page-1);
        $field  = ['id','name','city','rent_type','address','contact_user','contact_phone','status'];
        $stores = Storemodel::offset($offset)->where($where)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $count  = ceil($stores->count()/PAGINATE);
        $cities = Storemodel::groupBy('city')->get(['city']);
        $types  = isset($post['city'])?Storemodel::where('city',$post['city'])->groupBy('rent_type')->get(['rent_type']):Storemodel::groupBy('rent_type')->get(['rent_type']);
        $status = Storemodel::where($where)->groupBy('status')->get(['status']);
        $this->api_res(0,['count'=>$count,'city'=>$cities,'type'=>$types,'status'=>$status,'list'=>$stores]);
    }

    /**
     * 查找门店（按名称模糊查询）
     */
    public function searchStore(){
        $name   = $this->input->post('name',true);
        if(!$name){
            $this->api_res(1005);
            return;
        }
        $page   = $this->input->post('page',true)?$this->input->post('page',true):1;
        $offset = PAGINATE*($page-1);
        $field  = ['id','name','city','rent_type','address','contact_user','contact_phone','status'];
        $stores = Storemodel::where('name','like',"%$name%")->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $count  = ceil($stores->count()/PAGINATE);
        if(!$count){
            $this->api_res(1007);
            return;
        }
        $cities = Storemodel::groupBy('city')->get(['city']);
        $types  = Storemodel::groupBy('rent_type')->get(['rent_type']);
        $status = Storemodel::groupBy('status')->get(['status']);
        $this->api_res(0,['count'=>$count,'city'=>$cities,'type'=>$types,'status'=>$status,'list'=>$stores]);
    }

    /**
     * 删除门店
     */
    public function deleteStore(){
        $store_id   = $this->input->post('store_id',true);
        if(Storemodel::find($store_id)->delete()){
            $this->api_res(0);
        }
    }

    /**
     * 获取门店名
     */
    public function showStore(){
        $city   = $this->input->post('city',true);
        $where  = $city?$city:[];
        $store  = Storemodel::where($where)->get(['id','name']);
        $this->api_res(0,['stores'=>$store]);
    }

    /**
     * 获取城市
     */
    public function showCity(){
        $city   = Storemodel::groupBy('city')->get(['city']);
        $this->api_res(0,['cities'=>$city]);
    }

    /**
     * 查看门店信息
     */
    public function getStore(){
        $store_id   = $this->input->post('store_id',true);
        if(!$store_id){
            $this->api_res(1005);
            return;
        }
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'counsel_phone','counsel_time','images','describe'
        ];
        $store  = Storemodel::select($field)->find($store_id);
        $store->images  = $this->fullAliossUrl(json_decode($store->images,true),true);
        $this->api_res(0,['store'=>$store]);
    }


    /**
     * 编辑门店
     */
    public function updateStore(){
        $store_id   = $this->input->post('store_id',true);
        if(!$store_id){
            $this->api_res(1005);
            return;
        }
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'counsel_phone','counsel_time','images','describe'
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $update  = Storemodel::find($store_id);
        $post    = $this->input->post(null,true);
        $update->fill($post);
        if(!isset($post['images']))
        {
            $this->api_res(1002,['error'=>'必须上传图片']);
            return;
        }
        $images  = $this->splitAliossUrl($post['images'],true);
        $images = json_encode($images);
        $update->images=$images;
        if($update->save()){
            $this->api_res(0,['store_id'=>$update->id]);
        }
    }


    /**
     * 添加分布式门店
     */
    public function addStoreDot()
    {
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'counsel_phone','counsel_time','images','describe'
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post    = $this->input->post(null,true);
        $insert  = new Storemodel();
        $insert->fill($post);
        if(!isset($post['images']))
        {
            $this->api_res(1002,['error'=>'必须上传图片']);
            return;
        }
        $images  = $this->splitAliossUrl($post['images'],true);
        $images = json_encode($images);
        $insert->images=$images;
        if($insert->save()){
            $this->api_res(0,['store_id'=>$insert->id]);
        }
    }

    /**
     * 添加集中式门店
     */
    public function addStoreUnion()
    {
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'contact_phone','counsel_phone','counsel_time','images','describe','history','shop','relax','bus'
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post   = $this->input->post(null,true);
        $insert  = new Storemodel();
        $insert->fill($post);
        if(!isset($post['images']))
        {
            $this->api_res(1002,['error'=>'必须上传图片']);
            return;
        }
        $images  = $this->splitAliossUrl($post['images'],true);
        $images = json_encode($images);
        $insert->images=$images;
        if($insert->save()){
            $this->api_res(0,['store_id'=>$insert->id]);
        }
    }



    /**
     * 添加门店的验证规则
     */
    public function validationAddConfig()
    {

        $config = [
            array(
                'field' => 'name',
                'label' => '门店名',
                'rules' => 'required|trim|max_length[20]',
                'errors'=> array(
                    'required' => '门店名不能为空.',
                )
            ),
            array(
                'field' => 'rent_type',
                'label' => '门店类型',
                'rules' => 'required|trim|in_list[DOT,UNION]',
            ),
            array(
                'field' => 'status',
                'label' => '门店状态',
                'rules' => 'required|trim|in_list[NORMAL,CLOSE,WAIT]',
            ),
            array(
                'field' => 'theme',
                'label' => '门店主题',
                'rules' => 'trim',
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
            array(
                'field' => 'contact_user',
                'label' => '联系人',
                'rules' => 'required|trim|min_length[2]|max_length[6]',
            ),
            array(
                'field' => 'counsel_phone',
                'label' => '咨询电话',
                'rules' => 'required|trim|max_length[14]',
            ),
            array(
                'field' => 'counsel_time',
                'label' => '咨询时间',
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
