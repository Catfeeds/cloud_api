<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/27
 * Time:        10:17
 * Describe:    商城管理-商品管理
 */
class Goods extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('goodsmodel');
    }

    /**
     * 返回商品列表
     */
    public function index()
    {
        $post = $this->input->post(NULL,true);
        $filed = ['goods_thumb','name','shop_price','market_price','quantity','on_sale'];

        $goods = Goodsmodel::get($filed);
        $this->api_res(0,$goods);
    }

    
}