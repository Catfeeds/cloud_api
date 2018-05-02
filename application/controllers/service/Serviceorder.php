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
        $this->load->model('storemodel');
    }

    /**
     * 返回服务订单列表
     */
    public function index()
    {

        $post       = $this->input->post(NULL,true);
        $where      = array();
        $filed      = ['id','sequence_number','store_id','room_id','estimate_money','pay_money','service_type_id','status','deal'];

        $page       = isset($post['page'])?$post['page']:1;
        $offset     = PAGINATE*($page-1);
        $count      = ceil(Serviceordermodel::count()/PAGINATE);

        if(!empty($post['store_id'])){$where['store_id']=$post['store_id'];}
        if(!empty($post['service_id'])){$where['service_type_id']=$post['service_id'];}
        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};

        if(empty($where)){
            $order = Serviceordermodel::whereBetween('created_at',[$bt,$et])->take(PAGINATE)->skip($offset)
                                        ->orderBy('id','desc')->get($filed)->toArray();
            $this->api_res(0,['list'=>$order,'count'=>$count]);
            return;
        }
        $order = Serviceordermodel::where($where)->whereBetween('created_at',[$bt,$et])->take(PAGINATE)
                                    ->skip($offset)->orderBy('id','desc')->get($filed)->toArray();
        $this->api_res(0,['list'=>$order,'count'=>$count]);
    }

    /**
     * 城市列表
     */
    public function getCity()
    {
        $this->load->model('storemodel');
        $filed      = ['city'];
        $city       = Storemodel::get($filed)->groupBy('city')->toArray();
        $this->api_res(0,$city);
    }

    /**
     * 公寓列表
     */
    public function getStore()
    {
        $this->load->model('storemodel');
        $filed      = ['id','name'];
        $store  = Storemodel::get($filed);
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

    /**
     *
     */
    public function test()
    {
        $this->load->model('storemodel');
        /*$result = Serviceordermodel::find(1);
        $store_name=$result->store()->get(['name'])->toArray()[0]['name'];
        //var_dump(compact('store_name'));die();
        $result = array_merge($result->toArray(),compact('store_name'));
        var_dump($result);*/
        //$order = Serviceordermodel::get();
        $result = Serviceordermodel::with('store')->get();

        //$name = $result->name;
        //$stname = $result->store->name;

        $this->api_res(0,$result);
    }
}