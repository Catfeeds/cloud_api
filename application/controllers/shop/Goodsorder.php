<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/28
 * Time:        15:09
 * Describe:    商品管理-商品订单
 */
class Goodsorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('goodsordermodel');
        $this->load->model('customermodel');
        $this->load->model('shopaddressmodel');
    }

    public function index()
    {
        $post   = $this->input->post(NULL,true);
        $page   = isset($post['page'])?$post['page']:1;
        $offset = PAGINATE*($page-1);
        $count  = ceil(Goodsordermodel::count()/PAGINATE);
        $filed  = ['id','number','customer_id','address_id','status','goods_money','pay_money'];

        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};

        if(!empty($post['name'])){
            $name     = $post['name'];
            $goods  = Goodsordermodel::with('customer')->with('address')->where('name','like','%'."$name".'%')->whereBetween('created_at',[$bt,$et])
                        ->take(PAGINATE)->skip($offset)->orderBy('id','desc')->get($filed)->toArray();
            $this->api_res(0,['list'=>$goods,'count'=>$count,'cdn_path'=>config_item('cdn_path')]);
        }
        else if(!empty($post['number'])){
            $number = $post['number'];
            var_dump($number);
            $goods  = Goodsordermodel::with('customer')->with('address')->where('number',$number)->take(PAGINATE)->skip($offset)->orderBy('id','desc')
                        ->get($filed)->toArray();
            $this->api_res(0,['list'=>$goods,'count'=>$count,'cdn_path'=>config_item('cdn_path')]);
        }else{
            $goods  = Goodsordermodel::with('customer')->with('address')->whereBetween('created_at',[$bt,$et])->take(PAGINATE)->skip($offset)
                        ->orderBy('id','desc')->get($filed)->toArray();
            $this->api_res(0,['list'=>$goods,'count'=>$count,'cdn_path'=>config_item('cdn_path')]);
        }
    }


}