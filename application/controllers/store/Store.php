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
        $page   = intval(isset($post['page'])?$post['page']:1);
        $where  = ['company_id'=>COMPANY_ID];
        empty($post['city'])?:$where['city']=$post['city'];
        empty($post['type'])?:$where['rent_type']=$post['type'];
        empty($post['status'])?:$where['status']=$post['status'];
        $offset = PAGINATE*($page-1);
        $field  = ['id','name','city','rent_type','address','contact_user','counsel_phone','status','created_at'];
        $count  = ceil(Storemodel::where($where)->count()/PAGINATE);
        $cities = Storemodel::where(['company_id'=>COMPANY_ID])->groupBy('city')->get(['city']);
        $types  = isset($post['city'])?Storemodel::where(['company_id'=>COMPANY_ID])->where('city',$post['city'])->groupBy('rent_type')->get(['rent_type']):Storemodel::groupBy('rent_type')->get(['rent_type']);
        $status = Storemodel::where($where)->groupBy('status')->get(['status']);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'city'=>$cities,'type'=>$types,'status'=>$status,'list'=>[]]);
            return;
        }
        $stores = Storemodel::offset($offset)->where($where)->limit(PAGINATE)->orderBy('id','asc')->get($field);
        $this->api_res(0,['count'=>$count,'city'=>$cities,'type'=>$types,'status'=>$status,'list'=>$stores]);
    }

    /**
     * 查找门店（按名称模糊查询）
     */
    public function searchStore(){
        $name   = $this->input->post('name',true);
        $where  = ['company_id'=>COMPANY_ID];
        $page   = intval($this->input->post('page',true)?$this->input->post('page',true):1);
        $offset = PAGINATE*($page-1);
        $field  = ['id','name','city','rent_type','address','contact_user','counsel_phone','status','created_at'];
        $count  = ceil(Storemodel::where('name','like',"%$name%")->count()/PAGINATE);
        $cities = Storemodel::where($where)->groupBy('city')->get(['city']);
        $types  = Storemodel::where($where)->groupBy('rent_type')->get(['rent_type']);
        $status = Storemodel::where($where)->groupBy('status')->get(['status']);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'city'=>$cities,'type'=>$types,'status'=>$status,'list'=>[]]);
            return;
        }
        $stores = Storemodel::where($where)->where('name','like',"%$name%")->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $this->api_res(0,['count'=>$count,'city'=>$cities,'type'=>$types,'status'=>$status,'list'=>$stores]);
    }

    /**
     * 删除门店
     */
    public function deleteStore(){
        $store_id   = $this->input->post('store_id',true);
        $where  = ['company_id'=>COMPANY_ID];
        if(Storemodel::where($where)->find($store_id)->delete()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 批量删除门店
     */
    public function destroyStore(){
        $id     = $this->input->post('store_id',true);
        if(!is_array($id)){
            $this->api_res(1005);
            return;
        }
        $where  = ['company_id'=>COMPANY_ID];
        $ids    = Storemodel::where($where)->get(['id'])->map(function($id){
            return $id->id;
        })->toArray();
        $diff     = array_diff($id,$ids);
        if($diff){
            $this->api_res(1009);
            return;
        }
        if(Storemodel::destroy($id)){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 获取门店名
     */
    public function showStore(){
        $city   = $this->input->post('city',true);
        $where  = ['company_id'=>COMPANY_ID];
        $city?$where['city']=$city:null;
        $store_ids['id']= explode(',',$this->employee->store_ids);

        if (empty($store_ids)||!isset($store_ids)){
            $this->api_res(1018);
            return;
        }

        $store  = Storemodel::where($where)->whereIn($store_ids)->get(['id','name','province','city','district']);
        $this->api_res(0,['stores'=>$store]);
    }

    /**
     * 获取城市
     */
    public function showCity(){
        $where  = ['company_id'=>COMPANY_ID];
        $city   = Storemodel::where($where)->groupBy('city')->get(['city'])->map(function($city){
            return $city['city'];
        });
        $this->api_res(0,['cities'=>$city]);
    }

    /**
     * 查看门店信息
     */
    public function getStore(){
        $where  = ['company_id'=>COMPANY_ID];
        $store_id   = $this->input->post('store_id',true);
        if(!$store_id){
            $this->api_res(1005);
            return;
        }
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'counsel_phone','counsel_time','images','describe'
        ];
        $store  = Storemodel::where($where)->select($field)->find($store_id);
//        $store->describe    = strip_tags(htmlspecialchars_decode($store->describe));
        $store->describe    = (htmlspecialchars_decode($store->describe));
        $store->images  = $this->fullAliossUrl(json_decode($store->images,true),true);
        $this->api_res(0,['store'=>$store]);
    }


    /**
     * 编辑门店
     */
    public function updateStore(){
        $store_id   = $this->input->post('store_id',true);
        $where  = ['company_id'=>COMPANY_ID];
        if(!$store_id){
            $this->api_res(1005);
            return;
        }
//        $this->api_res(0,['des'=>$this->input->post('describe')]);
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'counsel_phone','counsel_time','images','describe'
        ];
        if(!$this->validationText($this->validationUpdateConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post    = $this->input->post(null,true);
        if(!isset($post['images']))
        {
            $this->api_res(1002,['error'=>'必须上传图片']);
            return;
        }
        $images  = $this->splitAliossUrl($post['images'],true);
        $images  = json_encode($images);
        $update  = Storemodel::where($where)->find($store_id);
        if(!$update){
            $this->api_res(1009);
            return;
        }
        $post    = $this->input->post(null,true);
        $update->fill($post);
        $update->describe   = htmlspecialchars($this->input->post('describe'));
        log_message('error',$post['describe']);
        $update->images = $images;
        if($update->save()){
            $this->api_res(0,['store_id'=>$update->id]);
        }else{
            $this->api_res(1009);
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
        if(!$this->validationText($this->validationAddDotConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post    = $this->input->post(null,true);
        if(!isset($post['images']))
        {
            $this->api_res(1002,['error'=>'必须上传图片']);
            return;
        }
        $images  = $this->splitAliossUrl($post['images'],true);
        $images = json_encode($images);
        if(Storemodel::where(['company_id'=>COMPANY_ID,'rent_type'=>'DOT','name'=>$post['name']])->first()){
            $this->api_res(1008);
            return;
        }
        $insert  = new Storemodel();
        $insert->fill($post);
        $insert->describe   = htmlspecialchars($this->input->post('describe'));
        $insert->company_id = COMPANY_ID;
        $insert->images=$images;
        if($insert->save()){
            $this->api_res(0,['store_id'=>$insert->id]);
        }else{
            $this->api_res(1009);
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
        if(!$this->validationText($this->validationAddUnionConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post   = $this->input->post(null,true);
        if(!isset($post['images']))
        {
            $this->api_res(1002,['error'=>'必须上传图片']);
            return;
        }
        $images  = $this->splitAliossUrl($post['images'],true);
        $images = json_encode($images);
        if(Storemodel::where(['company_id'=>COMPANY_ID,'rent_type'=>'UNION','name'=>$post['name']])->first()){
            $this->api_res(1008);
            return;
        }
        $insert  = new Storemodel();
        $insert->fill($post);
        $insert->company_id = COMPANY_ID;
        $insert->images=$images;
        if($insert->save()){
            $this->api_res(0,['store_id'=>$insert->id]);
        }else{
            $this->api_res(1009);
        }
    }


    /**
     * 编辑门店的验证规则
     */
    public function validationUpdateConfig()
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
                'rules' => 'required|trim|in_list[UNION,DOT]',
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
                'rules' => 'required|trim|min_length[9]|max_length[14]',
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

    /**
     * 添加门店的验证规则
     */
    public function validationAddUnionConfig()
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
                'rules' => 'required|trim|in_list[UNION]',
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
                'rules' => 'required|trim|min_length[9]|max_length[14]',
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

    /**
     * 添加门店的验证规则
     */
    public function validationAddDotConfig()
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
                'rules' => 'required|trim|in_list[DOT]',
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
                'rules' => 'required|trim|min_length[9]|max_length[14]',
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
        ];
        return $config;
    }


}
