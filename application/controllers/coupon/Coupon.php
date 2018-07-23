<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/2
 * Time:        10:25
 * Describe:    优惠券
 */
class Coupon extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('coupontypemodel');
    }

    /**
     * 优惠券列表
     */
    public function listCoupon() {
        $post   = $this->input->post(null, true);
        $id     = isset($post['id']) ? intval($post['id']) : null;
        $page   = isset($post['page']) ? intval($post['page']) : 1;
        $filed  = ['id', 'name', 'type', 'limit', 'description', 'deadline', 'discount'];
        $offset = PAGINATE * ($page - 1);
        $count  = ceil((Coupontypemodel::get($filed)->count()) / PAGINATE);
        if ($count < $page || $page < 0) {
            $this->api_res(0, []);
            return;
        }
        if ($id) {
            $coupon = Coupontypemodel::where('id', $id)->get($filed)->toArray();
            $this->api_res(0, ['coupon' => $coupon]);
        } else {
            $coupon = Coupontypemodel::orderBy('created_at', 'DESC')
                ->offset($offset)->limit(PAGINATE)->get($filed)->toArray();
            $this->api_res(0, ['count' => $count, 'list' => $coupon]);
        }
    }

    /**
     * 新增优惠券
     */
    public function addCoupon() {
        $this->load->model('coupontypemodel');
        $post = $this->input->post();
        if (!$this->validation()) {
            $fieldarr = ['name', 'description', 'type', 'limit', 'discount', 'deadline'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return;
        }
        $coupon = new Coupontypemodel();
        $coupon->fill($post);
        if ($coupon->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }

    }

    /**
     * 编辑优惠券
     */
    public function updateCoupon() {
        $this->load->model('coupontypemodel');
        $post = $this->input->post();
        $id   = isset($post['id']) ? intval($post['id']) : null;
        if (!$this->validation()) {
            $fieldarr = ['name', 'description', 'type', 'limit', 'discount', 'deadline'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return;
        }
        $coupon = Coupontypemodel::findorFail($id);

        $coupon->fill($post);
        if ($coupon->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 发放优惠券
     */
    public function AssignCoupon() {
        $input        = $this->input->post(null, true);
        $coupon_id    = $input['coupon_id'];
        $resident_ids = $input['resident_ids'];
        $this->load->model('residentmodel');
        $this->load->model('couponmodel');
        $coupon_type = Coupontypemodel::findOrFail($coupon_id);
        if ($coupon_type->deadline <= date('Y-m-d', time())) {
            $this->api_res(10031);
            return;
        }
        $residents = Residentmodel::whereIn('id', $resident_ids)->get();

        $data     = [];
        $datetime = Carbon::now();

        foreach ($residents as $resident) {
            $data[] = [
                'customer_id'    => $resident->customer_id,
                'resident_id'    => $resident->id,
                'employee_id'    => $this->employee->id,
                'activity_id'    => 0,
                'coupon_type_id' => $coupon_id,
                'status'         => Coupontypemodel::STATUS_UNUSED,
                'deadline'       => $coupon_type->deadline,
                'created_at'     => $datetime,
                'updated_at'     => $datetime,
            ];
        }

        Couponmodel::insert($data);
        $this->api_res(0);

    }

    /**
     * 住户列表
     */
    public function resident() {
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $post                                          = $this->input->post(null, true);
        $page                                          = isset($post['page']) ? intval($post['page']) : 1;
        $where                                         = [];
        empty($post['store_id']) ?: $where['store_id'] = $post['store_id'];
        if (!empty($post['search'])) {
            $name = $post['search'];
        }
        $filed  = ['id', 'room_id', 'name', 'phone', 'card_number', 'created_at', 'status'];
        $offset = PAGINATE * ($page - 1);
        if (isset($name)) {
            $count = ceil((Residentmodel::where('name', 'like', "%$name%")->with(['roomunion' => function ($query) use ($where) {
                $query->with('store');
            }])
                    ->whereHas('roomunion', function ($query) use ($where) {
                        $query->where($where);
                    })->count()) / PAGINATE);
            if ($count < $page || $page < 0) {
                $this->api_res(0, []);
                return;
            }
            $resident = Residentmodel::where('name', 'like', "%$name%")->with(['roomunion' => function ($query) use ($where) {
                $query->with('store');
            }])
                ->whereHas('roomunion', function ($query) use ($where) {
                    $query->where($where);
                })
                ->offset($offset)
                ->limit(PAGINATE)
                ->orderBy('created_at', 'DESC')
                ->get($filed);
        } else {
            $count = ceil((Residentmodel::with(['roomunion' => function ($query) use ($where) {
                $query->with('store');
            }])
                    ->whereHas('roomunion', function ($query) use ($where) {
                        $query->where($where);
                    })->count()) / PAGINATE);
            if ($count < $page || $page < 0) {
                $this->api_res(0, []);
                return;
            }

            $resident = Residentmodel::with(['roomunion' => function ($query) use ($where) {
                $query->with('store');
            }])
                ->whereHas('roomunion', function ($query) use ($where) {
                    $query->where($where);
                })
                ->offset($offset)
                ->limit(PAGINATE)
                ->orderBy('created_at', 'DESC')
                ->get($filed);
        }
        $this->api_res(0, ['total_page' => $count, 'list' => $resident]);
    }

    /**
     * 客户列表
     */
    /*public function resident()
    {
    $this->load->model('residentmodel');
    $post = $this->input->post(null,true);
    $page = isset($post['page'])?intval($post['page']):1;
    $filed = ['room_id','name','phone','card_number','created_at','status'];
    $offset = PAGINATE * ($page - 1);
    $count = ceil((Residentmodel::get($filed)->count())/PAGINATE);
    if ($count<$page||$page<0){
    $this->api_res(0,[]);
    return;
    }
    $customer = Residentmodel::orderBy('created_at','DESC')->offset($offset)->limit(PAGINATE)
    ->get($filed)->toArray();
    $this->api_res(0,['count'=>$count,'list'=>$customer]);
    }*/

    /**
     * 表单验证规则
     */
    public function validation() {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'name',
                'label' => '优惠券名称',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'description',
                'label' => '优惠券的简要描述',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'type',
                'label' => '优惠方式(类型)',
                'rules' => 'trim|required|in_list[CASH,DISCOUNT,REMIT]',
            ),
            array(
                'field' => 'limit',
                'label' => '使用范围',
                'rules' => 'trim|required|in_list[ROOM,UTILITY,SERVICE,NONE]',
            ),
            array(
                'field' => 'discount',
                'label' => '优惠金额',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'deadline',
                'label' => '截止日期',
                'rules' => 'trim|required',
            ),

        );
        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }
}
