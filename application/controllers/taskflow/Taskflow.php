<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
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
     * show my create
     */
    public function showMyCreate()
    {
        $this->load->model('positionmodel');
        $employee_id    = $this->employee->id;
        $taskflows  = Taskflowmodel::with(['steps'=>function($query){
            $query->with(['employee'=>function($e){
                $e->with('position');
            }]);
        }])->where('employee_id',$employee_id)->get();
        $this->api_res(0,$taskflows);
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
     * 任务流发起者撤销
     */
    public function destroyMyCreate()
    {
        $input  = $this->input->post(null,true);
        $taskflow_id    = $input['taskflow_id'];
        $remark = $input['remark'];
        $taskflow   = Taskflowmodel::with('steps')
            ->where('employee_id',$this->employee->id)
            ->where('status','!=',Taskflowmodel::STATE_APPROVED)
            ->findOrFail($taskflow_id);
        $taskflow->remark   = $remark;
        $taskflow->deleted_at   = Carbon::now();
        $taskflow->save();
        $taskflow->steps()->delete();
        $this->api_res(0);
    }

    /**
     * 查看任务流详情
     */
    public function show()
    {
        $input  = $this->input->post(null,true);
        $taskflow_id    = $input['taskflow_id'];
        $taskflow   = Taskflowmodel::with(['steps'=>function($query){
            $query->with('employee');
        }])->find($taskflow_id);
        $this->load->model('positionmodel');
        $taskflow->steps->map(function($res){
            $position_ids   = explode(',',$res->position_ids);
            $res->positions  = Positionmodel::whereIn('id',$position_ids)->get()->toArray();
            return $res;
        });
        $this->api_res(0,$taskflow);
    }

    /**
     * 任务流列表
     */
    public function listTaskflow()
    {
        $e_position_id  = $this->employee->position_id;
        $e_store_ids    = explode(',',$this->employee->store_ids);
        $audit  = Taskflowstepmodel::with(['taskflow'=>function($query){
            $query->with('employee')->with(['step'=>function($a){
                $a->with('employee');
            }]);
        }])
            ->where('status','!=',Taskflowstepmodel::STATE_APPROVED)
            ->whereIn('store_id',$e_store_ids)
            ->groupBy('taskflow_id')
            ->having('company_id',COMPANY_ID)
            ->get()
            ->map(function($res) use ($e_position_id){
                $s_position_ids = explode(',',$res->position_ids);
                if(in_array($e_position_id,$s_position_ids)){
                    return $res;
                }else{
                    return null;
                }
            })
            ->where('id','>',0)
            ->toArray();
        $res    = [];
        foreach ($audit as $a){
            $res[]  = $a;
        }
        $this->api_res(0,$res);
    }

    /**
     * 任务流审核
     */
    public function audit()
    {
        $input  = $this->input->post(null,true);
        if(!$this->validationText($this->validate())){
            $this->api_res(1002,['error'=>$this->form_first_error()]);
            return;
        }
        $taskflow_id    = $input['taskflow_id'];
        $remark = $input['remark'];
        $audit  = $input['audit'];
        //首先找到任务流
        $taskflow   = Taskflowmodel::find($taskflow_id);
        if(empty($taskflow) || $taskflow->status!=Taskflowmodel::STATE_AUDIT){
            $this->api_res(1007);
            return;
        }
        $steps  = $taskflow->steps;
        $step_audit_first    = $steps->where('status',Taskflowstepmodel::STATE_AUDIT)->first();
        if(empty($step_audit_first)){
            $this->api_res(1007);
            return;
        }
        $step_positions_ids  = explode(',',$step_audit_first->position_ids);
        if(!in_array($this->employee->position_id,$step_positions_ids)){
            $this->api_res(1011);
            return;
        }
        $step_audit_first->status   = $audit;
        $step_audit_first->remark   = $remark;
        $step_audit_first->employee_id  = $this->employee_id;
        $step_audit_first->save();
        $taskflow->step_id  = $step_audit_first->id;
        $steps_audit_count    = $steps->where('status',Taskflowstepmodel::STATE_AUDIT)->count();
        if($steps_audit_count==0){
            $taskflow->status   = Taskflowmodel::STATE_APPROVED;
        }
        $taskflow->save();
        $this->api_res(0);
    }

    private function validate()
    {
        return array(
            array(
                'field'=>'taskflow_id',
                'label'=>'选择审批的任务流',
                'rules'=>'trim|required|integer',
            ),
            array(
                'field'=>'remark',
                'label'=>'备注',
                'rules'=>'trim|required',
            ),
            array(
                'field'=>'audit',
                'label'=>'选择审批的任务流',
                'rules'=>'trim|required|in_list[APPROVED,UNAPPROVED]',
            ),
        );
    }

}
