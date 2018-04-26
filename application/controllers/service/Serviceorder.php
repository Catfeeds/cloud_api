<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/23
 * Time:        10:37
 * Describe:    [boss端]服务管理--服务订单
 */
class Serviceorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('serviceordermodel');
    }

    /**
     * 返回服务订单列表
     */
    public function index()
    {
        $post       = $this->input->post(NULL,true);
        $where      = array();
        $filed      = ['id','sequence_number','store_id','room_id','estimate_money','pay_money','status','deal'];

        if(isset($post['store_id'])){$where['store_id']=$post['store_id'];}
        if(isset($post['service_id'])){$where['service_type_id']=$post['service_id'];}
        if(isset($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(isset($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};

        if(empty($where)){
            $order = Serviceordermodel::whereBetween('created_at',[$bt,$et])->get($filed);
            $this->api_res(0,$order);
            return;
        }
        $order = Serviceordermodel::where($where)->whereBetween('created_at',[$bt,$et])->get($filed);
        $this->api_res(0,$order);
    }

    /**
     * 城市列表
     */
    public function getCity()
    {
        $this->load->model('storemodel');
        $filed      = ['city'];
        $city       = Storemodel::get($filed);
        $this->api_res(0,$city);
    }

    /**
     * 公寓列表
     */
    public function getStore()
    {
        $this->load->model('storemodel');
        $filed      = ['id','name'];
        $post       = $this->input->post(NULL,true);

        if (isset($post['city'])){
            $store  = Storemodel::where('city',$post['city'])->get($filed);
            $this->api_res(0,$store);
            return;
        }
        $store      = Storemodel::get($filed);
        $this->api_res(0,$store);
    }

    /**
     * 服务列表
     */
    public function getServiceType()
    {
        $this->load->model('servicetypemodel');
        $filed      = ['id','name'];
        $service    = Servicetypemodel::get($filed);
        $this->api_res(0,$service);
    }

    /**
     * 返回详细信息
     */
    public function getDetail()
    {
        $post   = $this->input->post(NULL,true);
        $id     = $post['id'];
        $filed  = ['sequence_number','store_id','room_id','estimate_money','pay_money','status','deal'];
        $order  = Serviceordermodel::where('id',$id)->get([$filed]);
        $this->api_res(0,$order);
    }
}