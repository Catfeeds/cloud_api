<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/26
 * Time:        15:17
 * Describe:    服务管理-预约订单
 */
class Reserveorder extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('reserveordermodel');
    }

    /**
     * 返回预约订单列表
     */
    public function index()
    {
        $this->load->model('employeemodel');
        $post   = $this->input->post(NULL,true);
        $page   = isset($post['page'])?intval($post['page']):1;

        $offset = PAGINATE*($page-1);
        $where  = array();
        $filed  = ['id','time','name','phone','visit_by','work_address','require','info_source','employee_id','status','remark'];

        if(!empty($post['store_id'])){$where['store_id']=intval($post['store_id']);}
        if(!empty($post['visit_type'])){$where['visit_by']=trim($post['visit_type']);}

        if(empty($where)){
            $count   = ceil(Reserveordermodel::count()/PAGINATE);
            if ($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return;
            }else{
                $reserve = Reserveordermodel::with('employee')
                                            ->take(PAGINATE)->skip($offset)
                                            ->orderBy('id','desc')->get($filed)->toArray();
            }
        }else{
            $count   = ceil(Reserveordermodel::where($where)->count()/PAGINATE);
            if ($page>$count||$page<1){
                $this->api_res(0,['list'=>[]]);
                return;
            }else{
                $reserve = Reserveordermodel::with('employee')->where($where)
                                            ->take(PAGINATE)->skip($offset)
                                            ->orderBy('id','desc')->get($filed)->toArray();
            }
        }
        $this->api_res(0,['list'=>$reserve,'count'=>$count]);
    }
}