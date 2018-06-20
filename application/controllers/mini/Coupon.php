<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        14:37
 * Describe:  优惠券
 */
class Coupon extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 员工发放优惠券
     */
    public function EmployeeAssignCoupons()
    {
        //员工发送优惠券
//        $activity_id    = 99;
        $store_id   = $this->employee->store_id;
        $employee_id    = $this->employee->id;
        $resident_id    = $this->input->post('resident_id',true);
        $coupon_id  = $this->input->post('coupon_id',true);
        $this->load->model('coupontypemodel');
        $this->load->model('couponmodel');


    }

}
