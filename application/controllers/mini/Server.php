<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/18 0018
 * Time:        17:08
 * Describe:    预约看房
 */
class Server extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('serviceordermodel');

    }

    /**
     * 服务订单列表
     */
    public function listServer()
    {
        $this->load->model('employeemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
        $post = $this->input->post(NULL, true);
        $filed = ['id','room_id','customer_id','type','name', 'phone', 'time','deal', 'remark','status'];
        $store_id   = $this->employee->store_id;
        $server = Serviceordermodel::with('roomunion','customer')
                                    ->where('store_id',$store_id)
                                    ->orderBy('id', 'desc')
                                    ->get($filed)->toArray();
        $this->api_res(0, ['list' => $server]);
    }

    /**
     * 显示一条服务的详情
     */
    public function show()
    {
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
        $post = $this->input->post(null,true);
        if ($post['id']){
            $id = intval($post['id']);
        }else{
            $this->api_res(0,[]);
            return;
        };
        $server = Serviceordermodel::with('roomunion','customer')->where('id',$id)->get()
                                    ->map(function($s){
                                        $paths =  json_decode($s->paths,true);
                                        if (!empty($paths)){
                                            $s->paths = $this->fullAliossUrl($paths,true);
                                        }
                                        return $s;
                                    })->toArray();
        $this->api_res(0,$server);
    }

    //创建一个订单
    public function create(){
        $this->load->model('ordermodel');
        $post = $this->input->post(NULL, true);
        if(!$this->validation())
        {
            $fieldarr= ['room_id','time','name','phone','type','addr_from','addr_to','money','remark',];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return;
        }
        $store_id       = $this->employee->store_id;
        $employee_id    = CURRENT_ID;
        $server_number  = (new Serviceordermodel())->getOrderNumber();
        $order_number   = (new Ordermodel())->getOrderNumber();
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $room = Roomunionmodel::where('id',intval($post['room_id']))
                                ->where('store_id',$store_id)->get(['resident_id'])->toArray();
        if (empty($room)){
            $this->api_res(1002);
            return;
        }else{
            $room_id = intval($post['room_id']);
            $resident_id = $room[0]['resident_id'];
            $customer = Residentmodel::where('id',$resident_id)->get(['customer_id'])->toArray();
            $customer_id = $customer[0]['customer_id'];
        }
        $server = new Serviceordermodel();
        $server->fill($post);
        $server->number = $server_number;
        $server->employee_id = $employee_id;
        $server->room_id = $room_id;
        $server->store_id = $store_id;
        $server->customer_id = $customer_id;

        $order = new Ordermodel();
        $order->number = $order_number;
        $order->store_id = $store_id;

        if ($server->save()){
            $this->api_res(0,[]);
        }else{
            $this->api_res(1009);
        }
    }

    //确认订单
    public function comfirmOrder()
    {
        $this->input->post(null,true);

    }

    /**
     * 表单验证
     */
    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'room_id',
                'label' => '房间ID',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'time',
                'label' => '预约时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '联系方式',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'type',
                'label' => '服务类型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'addr_from',
                'label' => '位置',
                'rules' => 'trim',
            ),
            array(
                'field' => 'addr_to',
                'label' => '服务类型',
                'rules' => 'trim',
            ),
            array(
                'field' => 'money',
                'label' => '服务费用',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'remark',
                'label' => '备注信息',
                'rules' => 'trim',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }


}