<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
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
        $this->load->model('taskflowrecordmodel');
    }

    /**
     * show my create
     * 我发起的任务
     */
    public function showMyCreate()
    {
        $input  = $this->input->post(null,true);
        $employee_id    = $this->employee->id;
        $where  = [];
        empty($input['store_id'])?:$where['store_id']=$input['store_id'];
        empty($input['type'])?:$where['type']=$input['type'];
        empty($input['status'])?:$where['status']=$input['status'];
        $page   = (int)(empty($input['page'])?1:$input['page']);
        $count  = Taskflowmodel::where($where)
            ->where('employee_id',$employee_id)
            ->count();
        $totalPage  = ceil($count/PAGINATE);
        if ($page>$totalPage) {
            $this->api_res(0,['taskflows'=>[],'page'=>$page,'totalPage'=>$totalPage]);
            return;
        }
        $offset    = PAGINATE * ($page - 1);
        $this->load->model('positionmodel');
        $this->load->model('storemodel');
        $taskflows  = Taskflowmodel::with(['step'=>function($query){
            $query->with(['employee'=>function($query){
                $query->with('position');
            }]);
        }])
            ->with('store')
            ->where($where)
            ->where('employee_id',$employee_id)
            ->offset($offset)
            ->limit(PAGINATE)
            ->orderBy('id','DESC')
            ->get();
            /*->map(function($res){
                $position_ids   = explode(',',$res->step->position_ids);
                $positions   = Positionmodel::whereIn('id',$position_ids)->pluck('name');
                $res->step->positions = $positions;
                return $res;
            })*/
        $this->api_res(0,['taskflows'=>$taskflows,'page'=>$page,'totalPage'=>$totalPage]);
    }

    /**
     * 展示全部审批
     */
    public function showAllTaskflow()
    {
        $input  = $this->input->post(null,true);
        //先判断权限
        $e_store_ids  = explode(',',$this->employee->store_ids);
        $where  = [];
        if (!empty($input['store_id'])) {
            $where['store_id']  = $input['store_id'];
            if (!in_array($input['store_id'],$e_store_ids)) {
                $this->api_res(1011);
                return;
            }
        }
        empty($input['type'])?:$where['type']=$input['type'];
        empty($input['status'])?:$where['status']=$input['status'];
        $page   = (int)(empty($input['page'])?1:$input['page']);
        $count  = Taskflowmodel::where($where)
            ->whereIn('store_id',$e_store_ids)
            ->count();
        $totalPage  = ceil($count/PAGINATE);
        if ($page>$totalPage) {
            $this->api_res(0,['taskflows'=>[],'page'=>$page,'totalPage'=>$totalPage]);
            return;
        }
        $offset    = PAGINATE * ($page - 1);
        $this->load->model('storemodel');
        $this->load->model('positionmodel');
        $taskflows  = Taskflowmodel::with(['step'=>function($query){
            $query->with(['employee'=>function($query){
                $query->with('position');
            }]);
        }])
            ->with('store')
            ->where($where)
            ->whereIn('store_id',$e_store_ids)
            ->offset($offset)
            ->limit(PAGINATE)
            ->get();
        $this->api_res(0,['taskflows'=>$taskflows,'page'=>$page,'totalPage'=>$totalPage]);
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
     * 我参与审核的任务流
     */
    public function showMyAudited()
    {
        $input  = $this->input->post(null,true);
        $where  = [];
        empty($input['store_id'])?:$where['store_id']=$input['store_id'];
        empty($input['type'])?:$where['type']=$input['type'];
        empty($input['status'])?:$where['status']=$input['status'];
        $page   = (int)(empty($input['page'])?1:$input['page']);
        $count  = Taskflowrecordmodel::where('employee_id',$this->employee->id)
            ->where($where)->groupBy('taskflow_id')->count();
        $totalPage  = ceil($count/PAGINATE);
        if ($page>$totalPage) {
            $this->api_res(0,['steps'=>[],'page'=>$page,'totalPage'=>$totalPage]);
            return;
        }
        $offset    = PAGINATE * ($page - 1);
        $this->load->model('storemodel');
        $this->load->model('positionmodel');
        $steps  = Taskflowrecordmodel::with(['taskflow'=>function($query){
            $query->with(['step'=>function($query){
                $query->with(['employee'=>function($query){
                    $query->with('position');
                }]);
            }])
            ->with('store');
        }])
            ->where('employee_id',$this->employee->id)
            ->where($where)
            ->offset($offset)
            ->limit(PAGINATE)
            ->groupBy('taskflow_id')
            ->orderBy('id','DESC')
            ->get();
        $this->api_res(0,['steps'=>$steps,'page'=>$page,'totalPage'=>$totalPage]);
    }

    /**
     * 任务流发起者撤销（暂时搁置）
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
//        $taskflow->status   = Taskflowmodel::STATE_CLOSED;
        $taskflow->save();
        //需要保存审核记录所以不更新状态
//        $taskflow->steps()->update(['status'=>Taskflowstepmodel::STATE_CLOSED]);
        $this->api_res(0);
    }

    /**
     * 查看任务流详情
     */
    public function show()
    {
        $input  = $this->input->post(null,true);
        $taskflow_id    = $input['taskflow_id'];
        $taskflow   = $this->showRecord($taskflow_id);
        if(!$taskflow){
            $this->api_res(1007);
            return;
        }
        switch ($taskflow->type) {
            case Taskflowmodel::TYPE_CHECKOUT:
                $data   = $this->showCheckoutInfo($taskflow_id);
                break;
            default:
                $data   = [];
        }
        $this->api_res(0,['taskflow'=>$taskflow,'data'=>$data]);
    }

    /**
     * 展示任务流审核记录（record）
     */
    private function showRecord($taskflow_id)
    {

        $this->load->model('positionmodel');
        $taskflow   = Taskflowmodel::with(['employee'=>function($query){
            $query->with('position');
        }])
            ->with(['record'=>function($query){
                $query->with(['employee'=>function($query){
                    $query->with('position');
                }])
                    ->orderBy('id','DESC');
            }])
            ->find($taskflow_id);
        return $taskflow;
    }

    /**
     * 展示退房任务信息详情
     */
    private function showCheckoutInfo($taskflow_id)
    {
        $this->load->model('checkoutmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('storemodel');
        $checkout   = Checkoutmodel::where('taskflow_id',$taskflow_id)->first();
        $resident   = $checkout->resident;
        $store      = $checkout->store->name;
        $unpaid     = $resident->orders()->whereIn('status',[Ordermodel::STATE_PENDING,Ordermodel::STATE_GENERATED])->get();
        $unpaidMoney    = number_format($unpaid->sum('money'),2,'.','');
        $diffmoney  = number_format($resident->deposit_money+$resident->tmp_deposit-$unpaidMoney,2,'.','');
        $data   = ['unpaidMoney'=>$unpaidMoney,'diffMoney'=>$diffmoney,'store'=>$store,'unpaid'=>$unpaid->toArray(),'resident'=>$resident->toArray()];
        return $data;
    }

    /**
     * 待我审批任务流列表
     */
    public function listTaskflow()
    {
        $input  = $this->input->post(null,true);
        $where  = [];
        empty($input['store_id'])?:$where['store_id']=$input['store_id'];
        empty($input['type'])?:$where['type']=$input['type'];
        $e_position_id  = $this->employee->position_id;
        $e_store_ids    = explode(',',$this->employee->store_ids);
        $page   = (int)(empty($input['page'])?1:$input['page']);
        $this->load->model('positionmodel');
        $this->load->model('storemodel');
        $audit  = Taskflowstepmodel::with(['taskflow'=>function($query){
            $query->with('employee')->with(['step'=>function($a){
                $a->with(['employee'=>function($query){
                    $query->with('position');
                }]);
            }])->with('store');
        }])
            ->where('status','!=',Taskflowstepmodel::STATE_APPROVED)
            ->whereIn('store_id',$e_store_ids)
            ->where($where)
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
            ->where('id','>',0);

        $count  = $audit->count();
        $totalPage  = ceil($count/PAGINATE);
        if ($page>$totalPage) {
            $this->api_res(0,['audits'=>[],'page'=>$page,'totalPage'=>$totalPage]);
            return;
        }

        $pageAudit  = $audit->forPage($page,PAGINATE);
        $res    = [];
        foreach ($pageAudit as $a){
            $res[]  = $a;
        }
        $this->api_res(0,['audits'=>$res,'page'=>$page,'totalPage'=>$totalPage]);
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

    /**
     * 审批审核（审核不通过，推送到上一步）
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
        $step_audit_first    = $taskflow->steps()->whereIn('status',[Taskflowstepmodel::STATE_AUDIT,Taskflowstepmodel::STATE_UNAPPROVED])->first();
        if(empty($step_audit_first)){
            $this->api_res(1007);
            return;
        }
        $step_positions_ids  = explode(',',$step_audit_first->position_ids);
        if (!in_array($this->employee->position_id,$step_positions_ids)) {
            $this->api_res(1011);
            return;
        }
        try{
            DB::beginTransaction();

            $step_audit_first->status   = $audit;
            $step_audit_first->remark   = $remark;
            $step_audit_first->employee_id  = $this->employee->id;
            $step_audit_first->save();
            //创建记录
            $this->createRecord($step_audit_first);
            //如果 通过 继续/不通过 返回上一个
            if ($audit !== Taskflowrecordmodel::STATE_APPROVED) {
                //判断是否有已经审核过的步骤，如果有把上一步改为AUDIT，如果没有呢
                if ($taskflow->step_id>0) {
                    $taskflow->step->update(['status'=>Taskflowstepmodel::STATE_AUDIT]);
                }
            }
            $taskflow->step_id  = $step_audit_first->id;
            $steps_audit_count    = $taskflow->steps()->whereIn('status',[Taskflowstepmodel::STATE_AUDIT,Taskflowstepmodel::STATE_UNAPPROVED])->count();
            if ($steps_audit_count==0) {
                $taskflow->status   = Taskflowmodel::STATE_APPROVED;
                switch ($taskflow->type) {
                    case Taskflowmodel::TYPE_CHECKOUT:
                        $this->load->model('checkoutmodel');
                        $taskflow->checkout()->update(['status'=>Checkoutmodel::STATUS_UNPAID]);
                        break;
                }
            }
            $taskflow->save();

            DB::commit();
        } catch (Exception $e){

            DB::rollBack();
            throw  $e;
        }
        $this->api_res(0);
    }

    /**
     * 创建新的审核的记录
     * @data $step对象
     */
    private function createRecord($step)
    {
        $record = new Taskflowrecordmodel();
        $record->fill($step->toArray());
        $record->step_id    = $step->id;
        $record->save();
    }

    /**
     * 任务流审核（审核不通过则关闭）
     */
    public function auditToClose()
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
        $step_audit_first->employee_id  = $this->employee->id;
        $step_audit_first->save();
        $taskflow->step_id  = $step_audit_first->id;
        if($audit == Taskflowstepmodel::STATE_UNAPPROVED){
            $taskflow->status   = Taskflowmodel::STATE_UNAPPROVED;
        }else{
            $steps_audit_count    = $steps->where('status',Taskflowstepmodel::STATE_AUDIT)->count();
            if ($steps_audit_count==0) {
                $taskflow->status   = Taskflowmodel::STATE_APPROVED;
                if ($taskflow->type == Taskflowmodel::TYPE_CHECKOUT) {
                    $this->load->model('checkoutmodel');
                    $taskflow->checkout()->update(['status'=>Checkoutmodel::STATUS_UNPAID]);
                }
            }
        }
        $taskflow->save();
        $this->api_res(0);
    }
}
