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
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 10;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $field = ['id', 'name', 'room_id', 'customer_id','status'];
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');

        $count_total = Residentmodel::all()->count();
        $count = ceil($count_total / $page_count);//总页数
        if ($page > $count) {
            return;
        }
        $category = Residentmodel::with(['roomunion' => function ($query) {
            $query->select('id', 'number');
        }])->with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->take($page_count)->skip($offset)
            ->orderBy('id', 'desc')->get($field)->toArray();
        $this->api_res(0, ['list' => $category, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
    }

    /**
     * 按房号查找
     */
    public function searchResident()
    {
        $field = ['id', 'name', 'room_id', 'customer_id','status'];
        $post   = $this->input->post(null,true);
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 10;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $count_total = Residentmodel::all()->count();
        $count = ceil($count_total / $page_count);//总页数
        if ($page > $count) {
            return;
        }
        $number = isset($post['number'])?$post['number']:null;
        if (!$number) {
            $this->api_res(1009,['error'=>'未指定房间号']);
            return;
        }
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
        }])->where('room_id',$room_union->id)->take($page_count)->skip($offset)
            ->orderBy('id', 'desc')->get($field)->toArray();
        $this->api_res(0, ['list' => $category, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
    }

    /**
     * 显示住户详情
    */
    public function showDetail()
    {
        $post = $this->input->post(null, true);
        $id   = isset($post['id'])?$post['id']:null;
        $filed_one = ['name', 'phone', 'room_id', 'card_type', 'card_number', 'card_one', 'card_two', 'card_three',
                  'alternative', 'alter_phone', 'people_count', 'address', 'real_rent_money',
                  'real_property_costs', 'deposit_money', 'status', 'contract_time'];
        $filed_two = ['name', 'phone', 'room_id', 'card_type', 'card_number', 'name_two', 'phone_two',
            'card_type_two', 'card_number_two', 'card_one', 'card_two', 'card_three',
            'alternative', 'alter_phone', 'people_count', 'address', 'real_rent_money',
            'real_property_costs', 'deposit_money', 'status', 'contract_time'];

        $resident = Residentmodel::where('id', $id)->first(['people_count']);
        if (!$resident) {
            $this->api_res(1009, ['error' => '住户信息不符']);
            return;
        }

        if ($resident->people_count > 1) {
            $resident = Residentmodel::where('id', $id)->first($filed_two);
        } else {
            $resident = Residentmodel::where('id', $id)->first($filed_one);
        }
        $this->load->model('roomunionmodel');
        $room_id = $resident->room_id;
        $room = Roomunionmodel::where('id', $room_id)->first(['number']);
        if (!$room) {
            $this->api_res(1009, ['error' => '住户房间号不符']);
            return;
        }
        $resident->number = $room->number;
        $this->load->model('smartdevicemodel');
        $devicetype = Smartdevicemodel::where('room_id', $room_id)->first(['type']);
        if (!$devicetype) {
            $this->api_res(1009, ['error' => '住户房间号不符']);
            return;
        }
        $resident->type = $this->getDeviceType($devicetype->type);
        $resident->status = $this->getRoomStatus($resident->status);

        $this->api_res(0, $resident);
    }

    /**
     * 获取房间状态
     */
    public function getDeviceType($type)
    {
        switch ($type) {
            case 'LOCK':
                return '门锁';
            case 'HOT_WATER_METER':
                return '热水表';
            case 'COLD_WATER_METER':
                return '冷水表';
            case 'ELECTRIC_METER':
                return '电表';
            case 'UNKNOW':
                return '不明';
        }
    }

    /**
     * 获取房间状态
     */
    public function getRoomStatus($status)
    {
        switch ($status) {
            case 'NOT_PAY':
                return '办理入住未支付';
            case 'PRE_RESERVE':
                return '办理预订未支付';
            case 'PRE_CHECKIN':
                return '预订转入住未支付';
            case 'PRE_CHANGE':
                return '换房未支付';
            case 'PRE_RENEW':
                return '续约未支付';
            case 'RESERVE':
                return '预订';
            case 'NORMAL':
                return '正常';
            case 'NORMAL_REFUND':
                return '正常退房';
            case 'UNDER_CONTRACT':
                return '违约退房';
            case 'INVALID':
                return '无效';
        }
    }

}