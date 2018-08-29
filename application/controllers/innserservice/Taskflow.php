<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/8/29 0029
 * Time:        10:43
 * Describe:
 */
class Taskflow extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('taskflowmodel');
        $this->load->model('taskflowstepmodel');
        $this->load->model('taskflowrecordmodel');
    }

    /**
     * 创建预警的任务流
     */
    public function createWarning()
    {
        $input  = $this->input->post(null,true);
        $data       = $input['data'];
        $store_id   = $input['store_id'];
        $warning_type   = $input['type'];
        if (!$this->validationText($this->validateWarning())) {
            $this->api_res(1002);
            return false;
        }
        if (!is_array($data)) {
            return false;
        }
        //记录任务插入成功的总条数
        $i = 0;
        log_message('debug','RISK_DATA:'.json_encode($data));
        foreach ($data as $item) {
            try{
                $company_id = $item['company_id'];
                $store_id   = $item['store_id'];
                $room_id    = $item['room_id'];
                $warning_id = $item['warning_id'];
                $message    = $item['message'];
                $taskflow_type       = Taskflowmodel::TYPE_WARNING;
                $create_role= Taskflowmodel::CREATE_SYSTEM;
                $employee_id= null;
                $taskflow   = $this->taskflowmodel->createTaskflow($company_id,$taskflow_type,$store_id,$room_id,$create_role,$employee_id,$warning_id,$message);
                if ($taskflow) {
                    $i++;
                }
            } catch (Exception $e) {
                log_message('error', 'warning_id'.$warning_id.'创建warning任务流失败，message:' . $e->getMessage());
                continue;
            }
        }
        $this->api_res(0,$i);
        // do something to send sms or wechat message
    }

    private function validateWarning()
    {
        return array(
            array(
                'field' => 'type',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'store_id',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'data[]',
                'rules' => 'trim|required',
            ),
        );
    }

}
