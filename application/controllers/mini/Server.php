<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:
 * Date:        2018/5/18 0018
 * Time:        17:08
 * Describe:    服务订单
 */
class Server extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('serviceordermodel');

    }

    /**
     * 服务订单列表
     */
    public function listServer() {
        $this->load->model('employeemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
        $post = $this->input->post(NULL, true);
        if (isset($post['number'])) {
            $number   = trim($post['number']);
            $room_id  = Roomunionmodel::where('number', 'like', "$number%")->get(['id'])->toArray();
            $room_ids = [];
            if ($room_id) {
                foreach ($room_id as $key => $value) {
                    array_push($room_ids, $room_id[$key]['id']);
                }
                var_dump($room_ids);
                $filed    = ['id', 'room_id', 'customer_id', 'type', 'name', 'phone', 'time', 'deal', 'remark', 'status'];
                $store_id = $this->employee->store_id;
                $server   = Serviceordermodel::with('roomunion', 'customer')
                    ->where('deal', '!=', 'PDONE')
                    ->where('store_id', $store_id)->whereIn('room_id', $room_ids)
                    ->orderBy('id', 'desc')
                    ->get($filed)->toArray();
            } else {
                $this->api_res(1009);
            }
        } else {
            $filed    = ['id', 'room_id', 'customer_id', 'type', 'name', 'phone', 'time', 'deal', 'remark', 'status'];
            $store_id = $this->employee->store_id;
            $server   = Serviceordermodel::with('roomunion', 'customer')
                ->whereIn('deal', ['UNDONE', 'SDOING'])
                ->where('store_id', $store_id)
                ->orderBy('id', 'desc')
                ->get($filed)->toArray();
        }
        $this->api_res(0, ['list' => $server]);
    }

    /**
     * 显示一条服务的详情
     */
    public function show() {
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
        $post = $this->input->post(null, true);
        if ($post['id']) {
            $id = intval($post['id']);
        } else {
            $this->api_res(0, []);
            return;
        }
        $server = Serviceordermodel::with('roomunion', 'customer')->where('id', $id)->get()
            ->map(function ($s) {
                $paths = json_decode($s->paths, true);
                if (!empty($paths)) {
                    $s->paths = $this->fullAliossUrl($paths, true);
                }
                return $s;
            });
        $this->api_res(0, $server);
    }

    /**
     * 创建一个订单
     */
    public function create() {
        $this->load->model('ordermodel');
        $post = $this->input->post(NULL, true);
        if (!$this->validation()) {
            $fieldarr = ['room_id', 'time', 'name', 'phone', 'type', 'addr_from', 'addr_to', 'money', 'remark'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return;
        }
        $store_id      = $this->employee->store_id;
        $employee_id   = $this->current_id;
        $server_number = (new Serviceordermodel())->getOrderNumber();
        $order_number  = (new Ordermodel())->getOrderNumber();
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $room = Roomunionmodel::where('id', intval($post['room_id']))
            ->where('store_id', $store_id)->get(['resident_id', 'room_type_id'])->toArray();
        if (empty($room)) {
            $this->api_res(1002);
            return;
        } else {
            $room_id      = intval($post['room_id']);
            $resident_id  = $room[0]['resident_id'];
            $customer     = Residentmodel::where('id', $resident_id)->get(['customer_id'])->toArray();
            $customer_id  = $customer[0]['customer_id'];
            $room_type_id = $room[0]['room_type_id'];
        }
//生成服务订单
        $server = new Serviceordermodel();
        $server->fill($post);
        $server->number      = $server_number;
        $server->employee_id = $employee_id;
        $server->room_id     = $room_id;
        $server->store_id    = $store_id;
        $server->customer_id = $customer_id;
//生成账单
        $order               = new Ordermodel();
        $order->number       = $order_number;
        $order->store_id     = $store_id;
        $order->room_id      = $room_id;
        $order->room_type_id = $room_type_id;
        $order->employee_id  = $employee_id;
        $order->resident_id  = $resident_id;
        $order->customer_id  = $customer_id;
        $order->uxid         = $customer_id;
        $order->money        = trim($post['money']);
        $order->type         = trim($post['type']);
        $order->year         = date('Y');
        $order->month        = date('m');
        $order->status       = 'PENDING';
        $order->deal         = 'UNDONE';
        $order->pay_status   = 'SERVER';
        if ($server->save() && $order->save()) {
            $server->order_id = $order->id;
            $server->save();
            $this->api_res(0, []);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 确认订单
     */
    public function comfirmOrder() {
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $post   = $this->input->post(null, true);
        $id     = isset($post['id']) ? intval($post['id']) : null;
        $money  = isset($post['money']) ? trim($post['money']) : null;
        $remark = isset($post['remark']) ? trim($post['remark']) : null;
        if ($money && $remark) {
//确认服务订单
            $server         = Serviceordermodel::where('id', $id)->first();
            $server->money  = $money;
            $server->remark = $remark;
            $server->deal   = 'SDOING';
//创建账单
            $order           = new Ordermodel();
            $order_number    = $order->getOrderNumber();
            $order->number   = $order_number;
            $order->store_id = $server->store_id;
            $order->room_id  = $server->room_id;
            $room            = Roomunionmodel::where('id', $server->room_id)->where('store_id', $server->store_id)
                ->get(['resident_id', 'room_type_id'])->toArray();
            if (empty($room)) {
                $this->api_res(1002);
                return;
            } else {
                $resident_id  = $room[0]['resident_id'];
                $customer     = Residentmodel::where('id', $resident_id)->get(['customer_id'])->toArray();
                $customer_id  = $customer[0]['customer_id'];
                $room_type_id = $room[0]['room_type_id'];
            }
            $order->room_type_id = $room_type_id;
            $order->employee_id  = $server->employee_id;
            $order->resident_id  = $resident_id;
            $order->customer_id  = $customer_id;
            $order->uxid         = $customer_id;
            $order->money        = trim($post['money']);
            $order->type         = $server->type;
            $order->year         = date('Y');
            $order->month        = date('m');
            $order->status       = 'PENDING';
            $order->deal         = 'UNDONE';
            $order->pay_status   = 'SERVER';
            if ($server->save() && $order->save()) {
                $server->order_id = $order->id;
                $server->save();
                $this->api_res(0, []);
                return;
            } else {
                $this->api_res(1009);
                return;
            }
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * 完成服务||取消服务
     */
    public function serverStatus() {
        $post = $this->input->post(null, true);
        if (isset($post['deal']) && isset($post['id'])) {
            $status = trim($post['deal']);
            $id     = trim($post['id']);
            $server = Serviceordermodel::where('id', $id)->first();
            //如果账单已完成将无法取消服务
            if ($status == 'PDONE') {
                $this->load->model('ordermodel');
                $order_status = Ordermodel::where('id', $server->order_id)->first();
                if (!empty($order_status) && in_array($order_status->status, ['PENDING', 'AUDITED', 'GENERATE'])) {
                    //var_dump($order_status);
                    Ordermodel::destroy($server->order_id);
                }
                $server->deal = $status;
                $server->employee_id    = $this->employee->id;
                if (!$server->save()) {
                    $this->api_res(1009);
                    return;
                }
                //加入任务流关闭
                $this->load->model('taskflowmodel');
                $taskflow   = $server->taskflow;
                $this->taskflowmodel->closeTaskflow($taskflow);
                $this->api_res(0, []);
            } else {
                //加入任务流审核
                $this->load->model('taskflowmodel');
                $taskflow   = $server->taskflow;
                if($this->taskflowmodel->approveTaskflow($taskflow)){
                    $server->employee_id    = $this->employee->id;
                    $server->deal = $status;
                    $server->save();
                }
                $this->api_res(0, []);
                return;
            }
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * 表单验证
     */
    public function validation() {
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

        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }

}
