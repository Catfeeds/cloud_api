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
        $this->load->model('employeemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
    }

    /**
     * 服务订单列表
     */
    public function listServer()
    {

        $post = $this->input->post(NULL, true);
        $filed = ['id','room_id','customer_id','type','name', 'phone', 'time','deal', 'remark','status'];
//var_dump(CURRENT_ID);
        $store_id   = 1;//$this->employee->store_id;
        $server = Serviceordermodel::with('roomunion','customer')
                                    ->where('store_id',$store_id)
                                    ->orderBy('id', 'desc')
                                    ->get($filed)->toArray();
        $this->api_res(0, ['list' => $server,'id'=>CURRENT_ID]);
    }

    /**
     * 显示一条服务的详情
     */
    public function show()
    {
        $post = $this->input->post(null,true);
        if ($post['id']){
            $id = intval($post['id']);
        }else{
            $this->api_res(0,[]);
            return;
        };

        $server = Serviceordermodel::with('roomunion','customer')->find($id)->toArray();
        $this->api_res(0,$server);
    }

    //创建一个订单
    public function create(){
        $post = $this->input->post(NULL, true);
        if(!$this->validation())
        {
            $fieldarr= ['time','name','phone','type','money','remark','money'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return;
        }
        $server = new Serviceordermodel();
        $server->fill($post);
        if ($server->save()){
            $this->api_res(0,[]);
        }else{
            $this->api_res(1002);
        }
    }


    //更新订单
    public function update(){
        $post = $this->input->post(NULL, true);
        switch ($post['action']) {
                case 'CONFIRM'  :
                    $record     = $this->confirm();
                    break;
                case 'PAY'      :
                    $record     = $this->payAndServe();
                    break;
                case 'SERVING'  :
                    $record     = $this->serve();
                    break;
                case 'COMPLETE' :
                    $record     = $this->complete();
                    break;
                case 'CANCEL'   :
                    $record     = $this->cancel();
                    break;
            }

        $this->api_res(0,$record);

    }

    /**
     * 将记录改为服务中的状态
     */
    private function serve($record)
    {
        return $record;
    }

    //确认订单
    private function confirm($record)
    {



    }

    /**
     * 取消服务
     */
    private function cancel($record)
    {

    }

    /**
     * 完成服务
     */
    private function complete($record)
    {


    }

    /**
     * 表单验证
     */
    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'number',
                'label' => '房间号',
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
                'field' => 'money',
                'label' => '服务费用',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'remark',
                'label' => '备注信息',
                'rules' => 'trim|required',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }


}