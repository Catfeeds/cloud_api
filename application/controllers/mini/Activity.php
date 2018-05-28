<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        14:36
 * Describe:    活动
 */
class Activity extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 展示当前公寓下的活动 （按类型分类）
     */
    public function showActivity(){
        $input  = $this->input->post(null,true);
        $type   = isset($input['type'])?$input['type']:'';
        $this->load->model('employeemodel');
        $this->load->model('storemodel');
        $this->load->model('activitymodel');
        $activities = $this->employee->store->activities;
        if ($type AND in_array($type, $this->activitymodel->getAllTypes())) {
            $activities     = $activities->where('type', $type);
        }
        $this->api_res(0,['list'=>$activities]);
    }
}
