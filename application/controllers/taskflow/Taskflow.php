<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        11:05
 * Describe:    任务流
 */
class Taskflow extends MY_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('taskflowmodel');
        $this->load->model('taskflowstepmodel');
    }

    /**
     * 创建任务流
     */
    public function create()
    {

    }

    /**
     * 修改？
     */
    public function edit()
    {

    }

    /**
     * 删除？
     */
    public function destroy()
    {

    }

    /**
     * 查看任务流详情
     */
    public function show()
    {

    }

    /**
     * 任务流列表
     */
    public function listTaskflow()
    {
        $e_position_id  = $this->employee->position_id;
        $audit  = Taskflowstepmodel::where('status','!=',Taskflowstepmodel::STATE_APPROVED)
            ->groupBy('taskflow_id')
            ->having('company_id',COMPANY_ID)
            ->get();

        var_dump($audit->toArray());
    }

    /**
     * 任务流审核
     */
    public function audit()
    {

    }

}
