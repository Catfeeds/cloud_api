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
        $page   = isset($post['page'])?$post['page']:1;
        $offset = PAGINATE*($page-1);
        $field  = ['id','store_id','name','feature'];
        $this->load->model('storemodel');
        $where  = isset($post['store_id'])?['store_id'=>$post['store_id']]:[];
        $roomtypes = Roomtypemodel::with('store')->where($where)->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $count  = ceil(($roomtypes->count())/PAGINATE);
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
            'provide','images',
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $roomtype   = new Roomtypemodel();
        $roomtype->fill($post);
        if($roomtype->save())
        {
            $this->api_res(0);
        }
    }

    /**
     * 删除房型
     */
    public function deleteRoomType(){

        $room_type_id   = $this->input->post('room_type_id',true);
        if(Roomtypemodel::find($room_type_id)->delete()){
            $this->api_res(0);
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
                'rules' => 'required|trim|numeric',
            ),
            array(
                'field' => 'area',
                'label' => '房型面积',
                'rules' => 'required|trim|decimal',
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
                'field' => 'provide',
                'label' => '房型设施',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'images',
                'label' => '房型图片',
                'rules' => 'required|trim',
            ),
        ];
        return $config;
    }




}
