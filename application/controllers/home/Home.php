<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/6
 * Time:        11:21
 * Describe:    首页展示
 */
class Home extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    public function home() {
        //测试用
        /*$result['home']['count_visit'] =88;
        $result['home']['count_order'] =88;
        $result['home']['count_confirm'] =88;
        $result['home']['count_bills'] =88;

        $result['day']['view'] =11;
        $result['day']['sign'] =11;
        $result['day']['recmoney'] =11;
        $result['day']['paymoney'] =11;
        $result['day']['checkout'] =1;
        $result['day']['server'] =11;
        $result['day']['clean'] =11;
        $result['day']['complaint'] =11;

        $result['house']['all'] =100;
        $result['house']['use'] =30;
        $result['house']['free'] =50;

        $result['month']['total']['all'] =100;
        $result['month']['total']['server'] =30;
        $result['month']['total']['other'] =50;

        $result['month']['resident']['all'] =-11;
        $result['month']['resident']['server'] =30;
        $result['month']['resident']['other'] =50;

        $result['month']['keeprent']['all'] =100;
        $result['month']['keeprent']['server'] =30;
        $result['month']['keeprent']['other'] =50;

        $result['month']['free']['all'] =100;
        $result['month']['free']['server'] =30;
        $result['month']['free']['other'] =50;*/

        $this->load->model('employeemodel');
        $this->load->model('reserveordermodel');
        $this->load->model('ordermodel');
        $this->load->model('billmodel');
        $this->load->model('residentmodel');
        $this->load->model('serviceordermodel');
        $this->load->model('contractmodel');
/**************************时间节点******************************/
        //当前时间节点之前的一天之内(只含当天)
        $date_d = [date('Y-m-d', time()) . " 00:00:00", date('Y-m-d H:i:s', time())];
        //当前时间节点之前的一月之内(只含本月)
        $date_m = [date('Y-m', time()) . "-00 00:00:00", date('Y-m-d H:i:s', time())];
        //当前时间节点之后的一月之内
        $date_later_m = [date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', strtotime('+1month'))];

        //权限下的门店信息
        $store_ids = Employeemodel::getMyStoreids();
/******************************Home****************************/
        //预约来访
        $result['home']['count_visit'] = Reserveordermodel::where('status', 'WAIT')->whereIn('store_id', $store_ids)->count();
        //即将到期
        $result['home']['count_order'] = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('end_time', $date_later_m)->count();
        //服务订单
        $result['home']['count_confirm'] = Serviceordermodel::whereIn('store_id', $store_ids)->whereIn('deal', ['UNDONE', 'SDOING'])->count();
        //未缴费账单
        $result['home']['count_bills'] = Ordermodel::whereIn('store_id', $store_ids)->whereIn('status', ['PENDING', 'AUDITED', 'GENERATE'])->count();
/******************************日报表****************************/
        //预约看房数
        $result['day']['view'] = Reserveordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)->count();
        //新签住户数
        $result['day']['sign'] = Contractmodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)->count();
        //应收
        $result['day']['paymoney'] = Billmodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)->sum('money');
        $result['day']['paymoney'] = number_format($result['day']['paymoney'], 2, '.', '');
        //实收
        $result['day']['recmoney'] = Ordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)->sum('money');
        $result['day']['recmoney'] = number_format($result['day']['recmoney'], 2, '.', '');
        //退租
        $result['day']['checkout'] = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('refund_time', $date_d)->count();
        //维修订单
        $result['day']['server'] = Serviceordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)->where('type', 'REPAIR')->count();
        //清洁订单
        $result['day']['clean'] = Serviceordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_d)->where('type', 'CLEAN')->count();
        //投诉（数据库中没有投诉字段，暂时处理成0）
        $result['day']['complaint'] = 0;
/******************************房源统计****************************/
        $this->load->model('roomunionmodel');
        //全部
        $result['house']['all'] = Roomunionmodel::whereIn('store_id', $store_ids)->count();
        //已出租
        $result['house']['use'] = Roomunionmodel::whereIn('store_id', $store_ids)->whereIn('status', ['RENT', 'ARREARS'])->count();
        //空置
        $result['house']['free'] = Roomunionmodel::whereIn('store_id', $store_ids)->where('status', 'BLANK')->count();
/******************************月报表****************************/
        //月报表实收
        $result_input                    = Billmodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_m)->where('type', 'input')->sum('money');
        $result_out                      = Billmodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_m)->where('type', 'output')->sum('money');
        $result['month']['total']['all'] = number_format($result_input - $result_out, 2, '.', '');
        //月报表住宿服务费实收
        $result['month']['total']['server'] = Ordermodel::whereIn('store_id', $store_ids)
            ->where('sequence_number', '<>', '')->where('type', 'ROOM')
            ->whereBetween('created_at', $date_m)
            ->sum('paid');
        $result['month']['total']['server'] = number_format($result['month']['total']['server'], 2, '.', '');
        //月报表物业服务费实收
        $result['month']['total']['management'] = Ordermodel::whereIn('store_id', $store_ids)
            ->where('sequence_number', '<>', '')->where('type', 'MANAGEMENT')
            ->whereBetween('created_at', $date_m)
            ->sum('paid');
        $result['month']['total']['management'] = number_format($result['month']['total']['management'], 2, '.', '');
        //月报表水电服务费实收
        $result['month']['total']['utility'] = Ordermodel::whereIn('store_id', $store_ids)
            ->where('sequence_number', '<>', '')->where('type', 'UTILITY')
            ->whereBetween('created_at', $date_m)
            ->sum('money');
        $result['month']['total']['utility'] = number_format($result['month']['total']['utility'], 2, '.', '');
        //月报表其他服务费实收
        $result['month']['total']['other'] = $result['month']['total']['all'] - $result['month']['total']['server'] - $result['month']['total']['management'] - $result['month']['total']['utility'];
        $result['month']['total']['other'] = number_format($result['month']['total']['other'], 2, '.', '');

        //住户增
        $count_thz = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('begin_time', $date_d)->count();
        //住户减
        $count_thj = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('end_time', $date_d)->count();
        //新签数
        $result['month']['resident']['server'] = Contractmodel::whereIn('store_id', $store_ids)->where('status', 'ARCHIVED')->whereBetween('created_at', $date_m)->count();
        //退租
        $result['month']['resident']['other'] = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('refund_time', $date_m)->count();
        //住戶增减
        $result['month']['resident']['all'] = $result['month']['resident']['server'] - $result['month']['resident']['other'];

        if ($result['month']['resident']['all'] >= 0) {
            $result['month']['resident']['all'] = "+" . $result['month']['resident']['all'];
        }
        /*$count_yhzj = $count_thz - $count_thj;
        if ($count_yhzj > 0) {
        $count_yhzj = '+' . $count_yhzj;
        } else if ($count_yhzj == 0) {
        $count_yhzj = 0;
        }

        $result['month']['resident']['all']     = $count_yhzj;
        //新签数
        $result['month']['resident']['server']  = Contractmodel::whereIn('store_id', $store_ids)->where('status','ARCHIVED')->whereBetween('created_at', $date_m)->count();
        //退租
        $result['month']['resident']['other']   = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('refund_time', $date_m)->count();

        $xzs = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_m)->get(['data'])->map(function ($d) {
        return $d->data;
        });
        $count_xz = 0;
        foreach ($xzs as $xz) {
        $is = array_has($xz, 'renewal');
        if ($is) {
        $count_xz++;
        }
        }
        $result['month']['keeprent']['server'] = $count_xz; //续签数
        $result['month']['keeprent']['other'] = Residentmodel::whereIn('store_id', $store_ids)->whereBetween('end_time', $date_m)->count();//到期数
        if ($result['month']['keeprent']['other'] != 0) {
        if ($result['month']['keeprent']['server'] != 0) {
        $result['month']['keeprent']['all'] = round(($result['month']['keeprent']['server'] / $result['month']['keeprent']['other']), 2) * 100; //百分比
        } else {
        $result['month']['keeprent']['all'] = 0; //续租率
        }
        } else {
        $result['month']['keeprent']['all'] = 0;
        }

        $result['month']['free']['server'] = Roomunionmodel::whereIn('store_id', $store_ids)->count(); //月可出租房间数
        $result['month']['free']['other'] = Roomunionmodel::whereIn('store_id', $store_ids)->whereIn('updated_at', $date_m)->where('status', 'BLANK')->count(); //月空置数
        if ($result['month']['free']['server'] != 0) {
        if ($result['month']['free']['other'] != 0) {
        $result['month']['free']['all'] = round(($result['month']['free']['other'] / $result['month']['free']['server']), 2) * 100; //百分比
        } else {
        $result['month']['free']['all'] = 0;
        }
        } else {
        $result['month']['free']['all'] = 0;
        }*/
        $this->api_res(0, $result);
    }

}
