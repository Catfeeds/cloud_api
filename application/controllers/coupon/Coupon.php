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
        $filed = ['id','name','type','limit','description','valid_time','deadline','discount'];
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
            $fieldarr   = ['name','description','type','limit','discount','valid_time','deadline'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return ;
        }
        $coupon = new Coupontypemodel();
        $coupon->fill($post);
        if ($post['deadline']==''){$coupon->deadline = '0000-00-00 00:00:00';}
        if ($post['deadline']){$coupon->valid_time = 0;}
        if ($post['valid_time']==''){$coupon->valid_time = 0;}
        if ($post['deadline']==''&&$post['valid_time']==''){
            $this->api_res(1002);
            return;
        }
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
            $fieldarr   = ['name','description','type','limit','discount','valid_time','deadline'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return ;
        }
        $coupon = Coupontypemodel::findorFail($id);

        $coupon->fill($post);
        if ($post['deadline']==''){$coupon->deadline = '0000-00-00 00:00:00';}
        if ($post['deadline']){$coupon->valid_time = 0;}
        if ($post['valid_time']==''){$coupon->valid_time = 0;}
        if ($post['deadline']==''&&$post['valid_time']==''){
            $this->api_res(1002);
            return;
        }
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
        $post = $this->input->post(null,true);
        $uid = isset($post['id'])?explode(',',$post['id']):null;

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
                'field' => 'valid_time',
                'label' => '有效时长',
                'rules' => 'trim|integer',
            ),
            array(
                'field' => 'deadline',
                'label' => '截止日期',
                'rules' => 'trim',
            ),

        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }

}