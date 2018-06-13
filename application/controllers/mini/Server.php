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
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 4;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $filed = ['id','room_id','customer_id','type','name', 'phone', 'time','deal', 'remark'];

        $store_id   = $this->employee->store_id;

        $count_total = ceil(Serviceordermodel::where('store_id',$store_id)->count());//总条数
        $count = ceil($count_total / $page_count);//总页数
        if ($page > $count) {
            return;
        }
        $server = Serviceordermodel::with('roomunion','customer')->where('store_id',$store_id)
                                    ->orderBy('id', 'desc')->get($filed)->toArray();
        $this->api_res(0, ['list' => $server, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
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


//      $room_id=$post['room_id'];
        $room_id  =  152;

//        $store_id   = $this->employee->store_id;



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




}