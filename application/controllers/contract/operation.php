<?php
/**
 * User: wws
 * Date: 2018-05-24
 * Time: 09:23
 *   运营合同
 */

class Operation extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     *  入住合同管理
     */
    public function operatList()
    {
        $this->load->model('storemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('employeemodel');
        $this->load->model('residentmodel');

        $post           = $this->input->post(NULL,true);
        $page           = empty($post['page'])?1:intval($post['page']);
        $offset         = PAGINATE*($page-1);
        $count  = ceil(Contractmodel::count()/PAGINATE);
        $where          = [];
        if(!empty($post['store_id'])){$where['id']  = $post['store_id'];}

        if(!empty($post['begin_time'])){$btime=$post['begin_time'];}else{$btime = date('Y-m-d H:i:s',0);};
        if(!empty($post['end_time'])){$etime=$post['end_time'];}else{$etime = date('Y-m-d H:i:s',time());};

        $filed  = ['id','serial_number','resident_id','sign_type','store_id','room_id','created_at','status','employee_id'];
        if ($where) {
            $operation = Contractmodel::with('resident')->with('employee')->with('store')->with('roomunion')->
            where($where)->whereBetween('created_at', [$btime, $etime])->take(PAGINATE)->
            skip($offset)->orderBy('id', 'desc')->get($filed);
        }
        $this->api_res(0,['operation'=>$operation,'count'=>$count]);
    }

    /**
     *查看入住合同
     */
    public function operationFind()
    {


    }



}
