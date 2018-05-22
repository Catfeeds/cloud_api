<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/28 0028
 * Time:        9:29
 * Describe:    房型管理
 */
class Roomtype extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('roomtypemodel');
    }

    /**
     * 房型列表
     */
    public function listRoomType()
    {
        $this->load->model('storemodel');
        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = PAGINATE*($page-1);
        $field  = ['id','store_id','name','feature'];
        $this->load->model('storemodel');
        $where  = isset($post['store_id'])?['store_id'=>$post['store_id']]:[];
        if(isset($post['city'])&&!empty($post['city'])){
            $store_ids  = Storemodel::where('city',$post['city'])->get(['id'])->map(function($s){
                return $s['id'];
            });
            $count  = ceil((Roomtypemodel::with('store')->whereIn('store_id',$store_ids)->where($where)->count())/PAGINATE);
            if($page>$count){
                $this->api_res(0,['count'=>$count,'list'=>[]]);
                return;
            }
            $roomtypes = Roomtypemodel::with('store')->whereIn('store_id',$store_ids)->where($where)->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
            $this->api_res(0,['count'=>$count,'list'=>$roomtypes]);
            return;
        }
        $count  = ceil((Roomtypemodel::with('store')->where($where)->count())/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $roomtypes = Roomtypemodel::with('store')->where($where)->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $this->api_res(0,['count'=>$count,'list'=>$roomtypes]);
    }


    /**
     * 新增房型
     * store_id,
     */
    public function addRoomType(){

        $post   = $this->input->post(null,true);
        $field  = [
            'store_id','name','feature','area','room_number','hall_number','toilet_number','toward','description',
            'provides','images',
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $roomtype   = new Roomtypemodel();
        $roomtype->fill($post);
        $images  = $this->splitAliossUrl($post['images'],true);
        $images  = json_encode($images);
        $roomtype->images=$images;
        if($roomtype->save())
        {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 删除房型
     */
    public function deleteRoomType(){

        $room_type_id   = $this->input->post('room_type_id',true);
        if(Roomtypemodel::find($room_type_id)->delete()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 批量删除
     */
    public function destroyRoomType(){
        $id = $this->input->post('room_type_id',true);
        if(!is_array($id)){
            $this->api_res(1005);
            return;
        }
        if(Roomtypemodel::destroy($id)){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 按名称模糊查找
     */
    public function searchRoomType(){
        $field  = ['id','store_id','name','feature'];
        $post   = $this->input->post(null,true);
        $name   = isset($post['name'])?$post['name']:null;
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = PAGINATE*($page-1);
        $this->load->model('storemodel');
        $count  = ceil((Roomtypemodel::with('store')->where('name','like',"%$name%")->count())/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $roomtypes = Roomtypemodel::with('store')->where('name','like',"%$name%")->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $this->api_res(0,['count'=>$count,'list'=>$roomtypes]);
    }

    /**
     * 查看房型信息
     */
    public function getRoomType(){
        $post   = $this->input->post(null,true);
        $field  = [
            'store_id','name','feature','area','room_number','hall_number','toilet_number','toward','description',
            'provides','images',
        ];
        $room_type_id   = isset($post['room_type_id'])?$post['room_type_id']:null;
        $room_type  = Roomtypemodel::select($field)->findOrFail($room_type_id);
        $room_type->images  = $this->fullAliossUrl(json_decode($room_type->images,true),true);
        $this->api_res(0,['room_type'=>$room_type]);
    }

    /**
     * 编辑房型信息
     */
    public function updateRoomType(){
        $post   = $this->input->post(null,true);
        $room_type_id   = isset($post['room_type_id'])?$post['room_type_id']:null;
        $field  = [
            'store_id','name','feature','area','room_number','hall_number','toilet_number','toward','description',
            'provides','images',
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $roomtype  = Roomtypemodel::findOrFail($room_type_id);
        $roomtype->fill($post);
        $images  = $this->splitAliossUrl($post['images'],true);
        $images = json_encode($images);
        $roomtype->images=$images;
        if($roomtype->save())
        {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }



    /**
     * 添加房型的表单验证规则
     */
    public function validationAddConfig(){
        $config = [
            array(
                'field' => 'store_id',
                'label' => '所属门店',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'name',
                'label' => '房型名称',
                'rules' => 'required|trim|max[20]',
            ),
            array(
                'field' => 'feature',
                'label' => '房型特色',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'area',
                'label' => '房型面积',
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'room_number',
                'label' => '房型卧室数目',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'hall_number',
                'label' => '房型大厅数目',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'toilet_number',
                'label' => '房型卫生间数目',
                'rules' => 'required|trim|integer',
            ),
            array(
                'field' => 'toward',
                'label' => '房型朝向',
                'rules' => 'required|trim|in_list[E,W,S,N,EW,SN]',
            ),
            array(
                'field' => 'description',
                'label' => '房型描述',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'provides',
                'label' => '房型设施',
                'rules' => 'required|trim',
            ),
            /*array(
                'field' => 'images',
                'label' => '房型图片',
                'rules' => 'required|trim',
            ),*/
        ];
        return $config;
    }

}
