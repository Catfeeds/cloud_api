<?php
/**
 * Created by PhpStorm.
 * User: dowell
 * Date: 2018/6/13
 * Time: 23:47
 */

class Home extends MY_Controller
{
    public function __construct()
    {
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
    public function lists()
    {

        //获取首页提示信息
        //未缴费订单
        $store_id   = empty($this->employee->store_id)?'':$this->employee->store_id;
        if(empty($store_id)){
            $this->api_res(1006);
        }


    $data['tipsnum']['order']=Ordermodel::where([
        'store_id'=>$store_id
        ])->where('status','PENDING')->count();
    //缴费订单确认
    $data['tipsnum']['sureorder']=Ordermodel::where([
         'store_id'=>$store_id
     ])->whereIn('status',['CONFIRM'])->count();

     //办理入住未完成
    $data['tipsnum']['noorder']= Residentmodel::where([
        'store_id'=>$store_id
    ])->whereIn('status',['NOT_PAY'])->count();

     //合同签约

    $data['tipsnum']['contract']= Contractmodel::where('status','GENERATED')->count();

    $this->api_res(0,$data);

    }



}