<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/24
 * Time:        11:39
 * Describe:    住户 resident center
 */

class Residentct extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('residentmodel');
    }

    /**
     * 显示住户中心
     */
    public function showCenter()
    {
        $post = $this->input->post(null, true);
        $page = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $count = ceil((Residentmodel::all()->count()) / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $field = ['id', 'name', 'room_id', 'customer_id','status'];
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
        $category = Residentmodel::with(['roomunion' => function ($query) {
            $query->select('id', 'number');
        }])->with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->offset($offset)->limit(PAGINATE)->orderBy('id', 'desc')->get($field);

        $this->api_res(0, ['count' => $count, 'list' => $category]);
    }

    /**
     * 按房号查找
     */
    public function searchResident()
    {
        $field = ['id', 'name', 'room_id', 'customer_id','status'];
        $post   = $this->input->post(null,true);
        $page = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $count = ceil((Residentmodel::all()->count()) / PAGINATE);
        $number   = isset($post['number'])?$post['number']:null;
        $this->load->model('roomunionmodel');
        $room_union = Roomunionmodel::where('number', $number)->first();
        if (!$room_union) {
            $this->api_res(1009);
        }
        $this->load->model('customermodel');
        $category = Residentmodel::with(['roomunion' => function ($query) {
            $query->select('id', 'number');
        }])->with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->where('room_id',$room_union->id)->orderBy('id','desc')->offset($offset)
            ->limit(PAGINATE)->orderBy('id', 'desc')->get($field);
        $this->api_res(0, ['count' => $count, 'list' => $category]);
    }

    /**
     * 显示住户详情
    */
    public function showDetail()
    {
        $post = $this->input->post(null, true);
        $id   = isset($post['id'])?$post['id']:null;
        $filed = ['name', 'phone', 'room_id', 'card_type', 'card_number', 'card_one', 'card_two', 'card_three',
                  'alternative', 'alter_phone', 'people_count', 'address', 'real_rent_money',
                  'real_property_costs', 'deposit_money', 'status', 'contract_time'];

        $resident = Residentmodel::where('id', $id)->get($filed)->toArray();
        $this->load->model('roomunionmodel');
        $room_id = $resident[0]['room_id'];
        $room = Roomunionmodel::where('id', $room_id)->get(['number'])->toArray();
        $resident[0]['number'] = $room[0]['number'];
        $this->load->model('smartdevicemodel');
        $devicetype = Smartdevicemodel::where('room_id', $room_id)->get(['type'])->toArray();
        $resident[0]['type'] = $devicetype[0]['type'];

        $this->api_res(0, $resident);
    }

}