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
    public function listRoomType(){
        $post   = $this->input->post(null,true);
        $page   = isset($post['page'])?$post['page']:1;
        $offset = PAGINATE*($page-1);
        $field  = ['id','store_id','name','feature'];
        $count  = ceil(Roomtypemodel::count()/PAGINATE);
        if($page>$count){
            throw new Exception();
        }
        $this->load->model('storemodel');
        $roomtypes = Roomtypemodel::with('store')->offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $this->api_res(0,['count'=>$count,'list'=>$roomtypes]);
    }

    public function addRoomType(){
        $post   = $this->input->post(null,true);

    }

}
