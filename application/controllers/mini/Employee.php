<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/22 0022
 * Time:        10:41
 * Describe:    员工
 */
class Employee extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
    }

    /**
     * 个人中心主页
     */
    public function showCenter()
    {
        $this->load->model('positionmodel');
        $this->load->model('storemodel');
        $field = ['id', 'name', 'avatar', 'position_id', 'store_id'];
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->with(['store' => function ($query) {
            $query->select('id', 'name');
        }])->where('bxid', CURRENT_ID)->get($field);
        $this->api_res(0, $category);
    }

    /**
     * 显示员工列表
     */
    public function listEmp()
    {
        $post = $this->input->post(null, true);
        $field = ['id', 'name', 'avatar', 'phone', 'position_id'];
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 10;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $count_total = Employeemodel::where('company_id', COMPANY_ID)
            ->where('status', 'ENABLE')->count();
        $count = ceil($count_total / $page_count);//总页数
        if ($page > $count) {
            return;
        }
        $this->load->model('positionmodel');
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->where('company_id', COMPANY_ID)
            ->where('status', 'ENABLE')->take($page_count)->skip($offset)
            ->orderBy('id', 'desc')->get($field)->toArray();
        $this->api_res(0, ['list' => $category, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
    }

    /**
     * 切换门店
     */
    public function switchStore(){

    }

    /**
     * 获取员工可操作的门店
     */
    public function showStore(){

    }

}
