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
     * 员工列表
     */
    public function showCenter()
    {
        $post = $this->input->post(null, true);
        $this->load->model('employeemodel');
        $store_ids = Employeemodel::getMyStoreids();

        $current_page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $pre_page = isset($post['pre_page']) ? intval($post['pre_page']) : 10;//当前页显示条数
        $offset = $pre_page * ($current_page - 1);
        $field = ['id', 'name', 'room_id', 'customer_id','status'];
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');

        $total = Residentmodel::whereIn('store_id', $store_ids)->count();
        $total_pages = ceil($total / $pre_page);//总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                'total_pages' => $total_pages, 'data' => []]);
            return;
        }
        $category = Residentmodel::with(['roomunion' => function ($query) {
            $query->select('id', 'number');
        }])->with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->whereIn('store_id', $store_ids)->take($pre_page)->skip($offset)
            ->orderBy('id', 'desc')->get($field)->toArray();
        $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
            'total_pages' => $total_pages, 'data' => $category]);
    }

    /**
     * 按房号查找
     */
    public function searchResident()
    {
        $post   = $this->input->post(null,true);
        $this->load->model('employeemodel');
        $store_ids = Employeemodel::getMyStoreids();

        $field = ['id', 'name', 'room_id', 'customer_id','status'];
        $current_page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $pre_page = isset($post['pre_page']) ? intval($post['pre_page']) : 10;//当前页显示条数
        $offset = $pre_page * ($current_page - 1);

        $this->load->model('roomunionmodel');
        $number = isset($post['number'])?$post['number']:null;
        if (!$number) {
            $this->api_res(1009,['error'=>'未指定房间号']);
            return;
        }
        $total = Roomunionmodel::whereIn('store_id', $store_ids)->where('number', $number)->count();
        $total_pages = ceil($total / $pre_page);//总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                'total_pages' => $total_pages, 'data' => []]);
            return;
        }

        $room_ids = Roomunionmodel::whereIn('store_id', $store_ids)
            ->where('number', $number)->get(['id'])->map(function ($r) {
                return $r->id;
            });
        if (!$room_ids) {
            $this->api_res(1009);
        }
        $this->load->model('customermodel');
        $category = Residentmodel::with(['roomunion' => function ($query) {
            $query->select('id', 'number');
        }])->with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->whereIn('store_id', $store_ids)->whereIn('room_id',$room_ids)
            ->take($pre_page)->skip($offset)->orderBy('id', 'desc')->get($field)->toArray();
        $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                                'total_pages' => $total_pages, 'data' => $category]);
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
                  'real_property_costs', 'deposit_money', 'status', 'begin_time', 'end_time'];
        $filed_two = ['name', 'phone', 'room_id', 'card_type', 'card_number', 'name_two', 'phone_two',
            'card_type_two', 'card_number_two', 'card_one', 'card_two', 'card_three',
            'alternative', 'alter_phone', 'people_count', 'address', 'real_rent_money',
            'real_property_costs', 'deposit_money', 'status', 'begin_time', 'end_time'];

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
     * 切换公寓
     */
    public function switchoverApartment()
    {
        $post = $this->input->post(null, true);
        $this->load->model('employeemodel');
        $store_ids = Employeemodel::getMyStoreids();

        $current_page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $pre_page = isset($post['pre_page']) ? intval($post['pre_page']) : 10;//当前页显示条数
        $offset = $pre_page * ($current_page - 1);

        $total = count($store_ids);
        $total_pages = ceil($total / $pre_page);//总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                'total_pages' => $total_pages, 'data' => []]);
            return;
        }
        $this->load->model('storemodel');
        $store_names = Storemodel::whereIn('id', $store_ids)->take($pre_page)->skip($offset)
            ->orderBy('id', 'asc')->get(['id', 'name']);
        $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
            'total_pages' => $total_pages, 'data' => $store_names]);
    }

    /**
     * 员工个人中心
     */
    public function displayCenter()
    {
        $post = $this->input->post(null, true);
        $field = ['id', 'name', 'position_id', 'store_id', 'avatar'];
        $this->load->model('employeemodel');
        $this->load->model('positionmodel');
        $employee = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->where('bxid', CURRENT_ID)->first($field);
        $this->load->model('storemodel');
        $store = Storemodel::where('id', $employee->store_id)->first(['name']);
        $employee->store_name = $store->name;
        $this->api_res(0, ['data' => $employee]);
    }

    /**
     * 数据统计
     */
    public function dataStatistics()
    {

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
            default:
                return '智能设备不明';
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
            default:
                return '房间状态不明';
        }
    }

}