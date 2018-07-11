<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        14:37
 * Describe:    优惠券类型
 */
class Coupontype extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 优惠券列表
     */
    public function listCouponType()
    {

        $this->load->model('coupontypemodel');

        $couponTypes    = Coupontypemodel::all();

        $this->api_res(0,['coupontypes'=>$couponTypes]);

    }
}