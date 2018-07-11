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
    public function listServiceorder()
    {
        $this->load->model('storemodel');
        $this->load->model('servicetypemodel');
        $post       = $this->input->post(NULL,true);
        $where      = [];
        $filed      = ['id','sequence_number','store_id','room_id','estimate_money','pay_money','service_id','status','deal'];
        $page       = isset($post['page'])?intval($post['page']):1;
        $offset     = PAGINATE*($page-1);

        if(!empty($post['store_id'])){$where['store_id']=intval($post['store_id']);}
        if(!empty($post['service_id'])){$where['service_id']=intval($post['service_id']);}
        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};
        $store_ids = explode(',',$this->employee->store_ids);
        $count     = ceil(Serviceordermodel::where($where)->whereIn('id',$store_ids)
                                ->whereBetween('created_at',[$bt,$et])->count()/PAGINATE);
        if ($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else {
            $order = Serviceordermodel::with('serviceType')->with('store')
                                        ->where($where)->whereIn('store_id',$store_ids)
                                        ->whereBetween('created_at', [$bt, $et])
                                        ->take(PAGINATE)->skip($offset)
                                        ->orderBy('id', 'desc')
                                        ->get($filed)->toArray();
        }
        $this->api_res(0,['list'=>$order,'count'=>$count]);
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
        $this->load->model('storemodel');
        $post   = $this->input->post(NULL,true);
        $id     = $post['id'];
        $filed  = ['number','sequence_number','store_id','room_id','name','estimate_money','pay_money','status','deal'];
        $order  = Serviceordermodel::with('store')->where('id',$id)->get($filed)->toArray();
        $this->api_res(0,$order);
    }

}