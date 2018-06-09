<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/8
 * Time:        21:57
 * Describe:    调价
 */
class Pricecontrol extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 调价
     */
    public function priceControl()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('buildingmodel');
        $this->load->model('roomtypemodel');

        $post  =$this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $filed = ['id','store_id','building_id','number','room_type_id','updated_at'];
        $where = [];
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);};
        if(!empty($post['building_id'])){$where['building_id'] = intval($post['building_id']);};
        if(!empty($post['number'])){$where['number'] = trim($post['number']);};

        $count = $count = ceil(Roomunionmodel::where($where)->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else {
            $price = Roomunionmodel::with('store_s')->with('building_s')->with('room_type')
                ->where($where)->take(PAGINATE)
                ->skip($offset)->get($filed)->toArray();

            $this->api_res(0, ['list' => $price, 'count' => $count]);
        }

    }
}