<?php
/**
 * Created by PhpStorm.
 * User: dowell
 * Date: 2018/6/13
 * Time: 23:47
 */

class Home extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('reserveordermodel');
        $this->load->model('ordermodel');
        $this->load->model('contractmodel');
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');
    }
    /**
     * 办理退房
     */
    public function lists() {
        $this->load->model('roomunionmodel');
//获取首页提示信息
        //未缴费订单
        $this->load->model('roomtypemodel');
        $store_id = $this->employee->store_id;
        //未缴费订单
        $room = Roomunionmodel::with('order') //
        ->where('store_id' ,$store_id)
            ->whereHas('order')
            ->orderBy('number', 'ASC')
            ->get()
            ->groupBy('layer')
            ->map(function ($room) {
                $roominfo                  = $room->toArray();
                $roominfo['count_total']   = count($room);
                $roominfo['count_arrears'] = 0;
                for ($i = 0; $i < $roominfo['count_total']; $i++) {
                    if (!empty($roominfo[$i]['order'])) {
                        $roominfo['count_arrears'] += 1;
                    }
                }
                global $count;
                $count += $roominfo['count_arrears'];
                return $count;
            })
            ->toArray();
        if($room) {
            $data['tipsnum']['order'] = end($room);
        }else{
            $data['tipsnum']['order'] = 0;
        }
        //缴费订单确认
        $this->load->model('customermodel');
        $where['status']= 'CONFIRM';
        $where['store_id'] = $this->employee->store_id;
        $order = $this->ordermodel->ordersOfRooms($where);
        if($order){
            $data['tipsnum']['sureorder'] = $order['total'];
        }else{
            $data['tipsnum']['sureorder'] = 0;
        }

        //办理入住未完成
        $data['tipsnum']['noorder'] = Residentmodel::where(['store_id' => $store_id])
            ->whereIn('status', ['NOT_PAY'])
            ->count();
        //合同签约
        $data['tipsnum']['contract'] = Contractmodel::where('status', 'GENERATED')->count();
        $this->api_res(0, $data);
    }
}
