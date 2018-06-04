<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/2
 * Time:        10:25
 * Describe:    优惠券
 */
class Coupon extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('coupontypemodel');
    }

    /**
     * 优惠券列表
     */
    public function listCoupon()
    {
        $post = $this->input->post(null,true);
        $id = isset($post['id'])?intval($post['id']):null;
        $page = isset($post['page'])?intval($post['page']):1;
        $filed = ['id','name','type','limit','description','deadline','discount'];
        $offset = PAGINATE * ($page - 1);
        $count = ceil((Coupontypemodel::get($filed)->count())/PAGINATE);
        if ($count<$page||$page<0){
            $this->api_res(0,[]);
            return;
        }
        if($id){
            $coupon = Coupontypemodel::where('id',$id)->get($filed)->toArray();
            $this->api_res(0,['coupon'=>$coupon]);
        }else{
            $coupon = Coupontypemodel::orderBy('created_at','DESC')
                ->offset($offset)->limit(PAGINATE)->get($filed)->toArray();
            $this->api_res(0,['count'=>$count,'list'=>$coupon]);
        }
    }

    /**
     * 新增优惠券
     */
    public function addCoupon()
    {
        $this->load->model('coupontypemodel');
        $post = $this->input->post();
        if(!$this->validation())
        {
            $fieldarr   = ['name','description','type','limit','discount','deadline'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return ;
        }
        $coupon = new Coupontypemodel();
        $coupon->fill($post);
        if ($coupon->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }

    }

    /**
     * 编辑优惠券
     */
    public function updateCoupon()
    {
        $this->load->model('coupontypemodel');
        $post = $this->input->post();
        $id = isset($post['id'])?intval($post['id']):null;
        if(!$this->validation())
        {
            $fieldarr   = ['name','description','type','limit','discount','deadline'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return ;
        }
        $coupon = Coupontypemodel::findorFail($id);

        $coupon->fill($post);
        if ($coupon->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 分配优惠券
     */
    public function sendCoupon()
    {
        $this->load->model('residentmodel');
        $post = $this->input->post(null,true);
        $coupon_id = isset($post['coupon_id'])?explode(',',$post['coupon_id']):null;
        $resident_id = isset($post['resident_id'])?explode(',',$post['resident_id']):null;
        $coupon = Coupontypemodel::where('id',$coupon_id)->get(['id','deadline'])->toArray();
        $resident = Residentmodel::where('id',$resident_id)->get(['id','name'])->toArray();
        //var_dump($coupon);
        foreach ($coupon as $key=>$value){
            foreach ($resident as $key1=>$value1){
                foreach ($resident[$key] as $key2=>$value2){
                    var_dump($resident[$key]);
                }
            }
        }

    }

    /**
     * 客户列表
     */
    public function resident()
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
    }

    /**
     * 表单验证规则
     */
    public function validation()
    {
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
        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }
}