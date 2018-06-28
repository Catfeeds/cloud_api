<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/28
 * Time:        14:21
 * Describe:    财务-水电费
 */

class Utility extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('meterreadingmodel');
    }

    /**
     * 水电列表
     */
    public function listUtility()
    {
        $this->load->model('meterreadingtransfermodel');
        $this->load->model('storemodel');
        $this->load->model('buildingmodel');
        $this->load->model('roomunionmodel');
        $post = $this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $where      = [];
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(!empty($post['status'])){$where['status'] = trim($post['status']);};
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);}
        if(!empty($post['number'])){$number = trim($post['number']);}
        $filed  = ['id','store_id','building_id','room_id','type','last_reading','this_reading','updated_at'];
        $count  = ceil(Meterreadingtransfermodel::where($where)->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else {
            $utility = Meterreadingtransfermodel::where($where)->orderBy('updated_at', 'DESC')
                ->with('store', 'building', 'roomunion')
                ->get($filed)->toArray();
            $this->api_res(0, ['list'=>$utility,'count'=>$count]);
        }
    }
}