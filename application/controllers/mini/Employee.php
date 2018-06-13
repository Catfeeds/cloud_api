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
        $current_page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $pre_page = isset($post['pre_page']) ? intval($post['pre_page']) : 10;//当前页显示条数
        $offset = $pre_page * ($current_page - 1);
        $total = Employeemodel::where('status', 'ENABLE')->count();
        $total_pages = ceil($total / $pre_page);//总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                'total_pages' => $total_pages, 'data' => []]);
            return;
        }
        $this->load->model('positionmodel');
        $category = Employeemodel::with('position')->where('status', 'ENABLE')->take($pre_page)->skip($offset)
            ->orderBy('id', 'desc')->get($field)->toArray();
        $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                                'total_pages' => $total_pages, 'data' => $category]);
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
