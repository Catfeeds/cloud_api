<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/18 0018
 * Time:        17:08
 * Describe:    预约看房
 */
class Reserve extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('reserveordermodel');
        $this->load->model('employeemodel');
    }

    /**
     * 预约订单列表
     */
    public function listReserve()
    {
        $this->load->model('roomtypemodel');
        $post = $this->input->post(NULL, true);
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 4;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $filed = ['id', 'customer_id','room_type_id', 'name', 'phone', 'visit_time', 'remark'];
        $store_id   = $this->employee->store_id;

        $count_total = ceil(Reserveordermodel::where(['store_id'=>$store_id])->whereIn('status', ['WAIT', 'BEGIN'])->count());//总条数
        $count = ceil($count_total / $page_count);//总页数
//        if ($page > $count) {
//            return;
//        }
        $reserve = Reserveordermodel::with('roomtype')->where(['store_id'=>$store_id])->whereIn('status', ['WAIT', 'BEGIN'])
                                    ->take($page_count)->skip($offset)
                                    ->orderBy('id', 'desc')->get($filed)->toArray();
        $this->api_res(0, ['list' => $reserve, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
    }

    /**
     * 查看看房信息
     */
    public function reserveInfo()
    {
        $this->load->model("roomtypemodel");
        $post = $this->input->post(null,true);
        if ($post['id']){
            $id = intval($post['id']);
        }else{
            $this->api_res(0,[]);
            return;
        };
        $feild = ['id','room_type_id','time','name','phone','work_address','check_in_time',
                    'people_count','require','guest_type','remark','info_source'];
        $reserve = Reserveordermodel::with('roomType')->where('id',$id)->get($feild);
        $this->api_res(0,$reserve);
    }

    /**
     *  确认预约或者取消预约
     */
    public function reserveStatus()
    {
        $post = $this->input->post(null,true);
        $id = isset($post['id'])?intval($post['id']):null;
        if(!$this->validation())
        {
            $fieldarr= ['room_type_id','work_address','info_source','people_count','check_in_time',
                        'guest_type','require','remark'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return;
        }
        $reserve    = Reserveordermodel::findOrFail($id);
        $reserve->fill($post);
        if($post['status']=='END'||$post['status']=='INVALID')
        {
            $status = trim($post['status']);
        }else{
            $this->api_res(1002,[]);
            return;
        }
        $reserve->status = $status;
        if ($reserve->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     *  房型
     */
    public function room_type()
    {
        $this->load->model('roomtypemodel');
        $room_type = Roomtypemodel::get(['id','name','feature']);
        $this->api_res(0,['list'=>$room_type]);
    }

    public function validation()
    {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'work_address',
                'label' => '工作地点',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'info_source',
                'label' => '信息来源',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'people_count',
                'label' => '入住人数',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'check_in_time',
                'label' => '入住时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'guest_type',
                'label' => '顾客类型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'require',
                'label' => '需求',
                'rules' => 'trim',
            ),
            array(
                'field' => 'remark',
                'label' => '备注',
                'rules' => 'trim',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }
}