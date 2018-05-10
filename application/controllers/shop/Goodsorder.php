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
    }

    public function listgoodsorder()
    {
        $this->load->model('customermodel');
        $this->load->model('shopaddressmodel');
        $post   = $this->input->post(NULL,true);
        $page   = isset($post['page'])?intval($post['page']):1;
        $offset = PAGINATE*($page-1);

        $filed  = ['id','number','customer_id','address_id','status','goods_money','pay_money'];

        if(!empty($post['begin_time'])){$bt=$post['begin_time'];}else{$bt = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$et=$post['end_time'];}else{$et = date('Y-m-d H:i:s',time());};

        if(!empty($post['number'])){
            $number = $post['number'];
            $count  = ceil(Goodsordermodel::where('number',$number) ->whereBetween('created_at',[$bt,$et])
                                                ->count()/PAGINATE);
            if($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return;
            }else {
                $goods = Goodsordermodel::with('customer')->with('address')
                                        ->where('number', $number)
                                        ->whereBetween('created_at', [$bt, $et])
                                        ->take(PAGINATE)->skip($offset)
                                        ->orderBy('id', 'desc')
                                        ->get($filed)->toArray();
            }
        }else{
            $count  = ceil(Goodsordermodel::whereBetween('created_at',[$bt,$et])->count()/PAGINATE);
            if($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return;
            }else {
                $goods = Goodsordermodel::with('customer')->with('address')
                                        ->whereBetween('created_at', [$bt, $et])
                                        ->take(PAGINATE)->skip($offset)
                                        ->orderBy('id', 'desc')
                                        ->get($filed)->toArray();
            }
        }
        $this->api_res(0,['list'=>$goods,'count'=>$count]);
    }

    /**
     * 返回详细信息
     */
    public function detail()
    {
        $this->load->model('shopgoodsordermodel');
        $this->load->model('goodsmodel');
        $this->load->model('customermodel');
        $this->load->model('shopaddressmodel');
        $post   = $this->input->post(NULL,true);
        $id     = $post['id'];
        $filed  = ['id','number','customer_id','address_id','goods_quantity','status','goods_money','pay_money'];
        $order  = Goodsordermodel::with('customer')->with('address')
                                    ->where('id',$id)
                                    ->get($filed)->toArray();
        $goods_id   = Shopgoodsordermodel::where('order_id',$id)->get(['goods_id'])->toArray();
        $goods_id   = $goods_id[0]['goods_id'];
        $goods_name = Goodsmodel::where('id',$goods_id)->get(['name']);
        $order[$id-1]['goods_name'] = $goods_name[0]['name'];
        $this->api_res(0,$order);
    }
}