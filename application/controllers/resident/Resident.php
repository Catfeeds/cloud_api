<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/9
 * Time:        15:06
 * Describe:    住户
 */
class Resident extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('residentmodel');
    }

    public function showResident()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $post = $this->input->post(null,true);
        $page  = isset($post['page'])?intval($post['page']):1;
        $offset= PAGINATE * ($page - 1);
        $filed = ['id','name','phone','room_id','card_number','created_at','status'];
        $where = [];
        if(!empty($post['store_id'])){$where['store_id'] = intval($post['store_id']);};
        if(!empty($post['name'])){$where['name'] = trim($post['name']);};

        $count = $count = ceil(Residentmodel::where($where)->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else {
            $resident = Residentmodel::with('room')->where($where)->take(PAGINATE)
                    ->skip($offset)->get($filed)->map(function ($s){
                    $s->room->store_id = (Storemodel::where('id',$s->room->store_id)->get(['name']))[0]['name'];
                    $s->createdat = date('Y-m-d',strtotime($s->created_at->toDateTimeString()));
                    return $s;
                })->toArray();
            $this->api_res(0, ['list' => $resident, 'count' => $count]);
        }
    }
}