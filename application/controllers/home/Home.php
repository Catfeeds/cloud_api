<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/6
 * Time:        11:21
 * Describe:    首页展示
 */
class Home extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function home()
    {
        $this->load->model('employeemodel');
        $this->load->model('reserveordermodel');
        $this->load->model('ordermodel');
        $date_d = [date('Y-m-d',time()), date('Y-m-d H-i-s',time())];
        $date_m = [date('Y-m',time()), date('Y-m-d H-i-s',time())];

        $store_ids = Employeemodel::getMyStoreids();
        $count_yylf = Reserveordermodel::whereIn('store_id', $store_ids)->count(); //预约来访
        $count_dsdd = Ordermodel::whereIn('store_id', $store_ids)->where('status', 'CONFIRM')->count(); //待收账单
        $count_dsh = Ordermodel::whereIn('store_id', $store_ids)->where('status', 'GENERATE')->count(); //待审核
        $count_wjf = Ordermodel::whereIn('store_id', $store_ids)->where('status', 'PENDING ')->count(); //未缴费账单
        $count_yykf = Reserveordermodel::whereIn('store_id', $store_ids)
            ->whereBetween('created_at', $date_d)->get(['visit_time'])->count(); //预约看房数
        $this->load->model('contractmodel');
        $count_xqzh = Contractmodel::whereIn('store_id', $store_ids)
            ->whereBetween('created_at', $date_d)->count(); //新签住户数
        $money_rys = Ordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)
            ->get(['money'])->sum('money'); //应收
        $money_rss = Ordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)
            ->get(['money'])->sum('money'); //实收

        $this->load->model('residentmodel');
        $count_tz = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('refund_time', $date_d)->count();  //退租
        $conut_wx = Ordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)
            ->where('type', 'REPAIR')->count(); //维修订单
        $count_qj = Ordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)
            ->where('type', 'CLEAN')->count(); //清洁订单

        $this->load->model('roomunionmodel');
        $count_qb = Roomunionmodel::whereIn('store_id', $store_ids)->count(); //全部
        $count_ycz = Roomunionmodel::whereIn('store_id', $store_ids)->whereIn('status', ['RENT', 'ARREARS'])->count(); //已出租
        $count_kz = Roomunionmodel::whereIn('store_id', $store_ids)->where('status', 'BLANK')->count(); //空置
        if ($count_qb != 0) {
            if ($count_kz != 0) {
                $percentage = round(($count_kz / $count_qb), 2) * 100;
                $count_bfb = $percentage.'%'; //百分比
            } else {
                $count_bfb = 0;
            }
        }

        $money_ys = Ordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_m)->get(['money'])->sum('money'); //月报表实收

        $count = [
            'c_yylf' => $count_yylf,
            'c_dsdd' => $count_dsdd,
            'c_dsh'  => $count_dsh,
            'c_wjf'  => $count_wjf,
            'c_yykf' => $count_yykf,
            'c_xqzh' => $count_xqzh,
            'c_rys'  => $money_rys,
            'c_rss'  => $money_rss,
            'c_tz'   => $count_tz,
            'c_wx'   => $conut_wx,
            'c_qj'   => $count_qj,
            'c_qb'   => $count_qb,
            'c_ycz'  => $count_ycz,
            'c_kz'   => $count_kz,
            'c_bfb'  => $count_bfb,
            'c_ys'   => $money_ys
        ];
        $this->api_res(0, $count);
    }
}