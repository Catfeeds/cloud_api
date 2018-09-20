<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        11:05
 * Describe:    任务流模板
 */
class Template extends MY_Controller{

    public function __construct()
    {
        parent::__construct();
        //判断权限

        $this->load->model('Taskflowtemplatemodel');
        $this->load->model('Taskflowsteptemplatemodel');
    }

    /**
     * 创建模板
     */
    public function create()
    {
        $field  = ['name','type'];
        if(!$this->validationText($this->validation())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $input  = $this->input->post(null,true);
        $steps  = $input['steps'];
        if(!is_array($steps)|| empty($steps)){
            $this->api_res(1002,['error'=>'请至少填写一个审批步骤']);
            return;
        }
        $description    = empty($input['description'])?'无':$input['description'];
        $name           = $input['name'];
        $type           = $input['type'];
        $company_id     = $this->company_id;
//        $store_id       = $this->employee->store_id;
        if(Taskflowtemplatemodel::where(['company_id'=>$company_id,'name'=>$name])->exists()){
            $this->api_res(1008);
            return;
        }
        $employee_id    = $this->employee->id;
        $create_at      = Carbon::now();
        $data           = ["员工{$employee_id} 在$create_at 创建了 {$type}的审批流，名称为 {$name}"];
        $template       = new Taskflowtemplatemodel();
        try{
            DB::beginTransaction();
            $template->name = $name;
            $template->type = $type;
            $template->company_id   = $company_id;
//            $template->store_id     = $store_id;
            $template->employee_id  = $employee_id;
            $template->description  = $description;
            $template->data = $data;
            $template->save();
            $template_id    = $template->id;
            $step_templates =[];
            for($a=0;$a<count($steps);$a++){
                $s_template = [];
                $s_template['template_id'] = $template_id;
                $s_template['name'] = empty($steps[$a]['name'])?"未设置":$steps[$a]['name'];
                $s_template['company_id'] = $this->company_id;
                $s_template['type'] = $type;
//                $s_template['store_id'] = $store_id;
                $s_template['seq']  = $a+1;
                $s_template['position_ids']  = $steps[$a]['position_ids'];
                $step_templates[]   = $s_template;
            }
            Taskflowsteptemplatemodel::insert($step_templates);
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

    /**
     * 修改模板
     */
    public function edit()
    {
        $field  = ['name','type'];
        if(!$this->validationText($this->validation())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $input  = $this->input->post(null,true);
        $template_id    = $input['template_id'];
        $steps  = $input['steps'];
        if(!is_array($steps)|| empty($steps)){
            $this->api_res(1002,['error'=>'请至少填写一个审批步骤']);
            return;
        }
        $type           = $input['type'];
        if (in_array($type,$this->taskflowtemplatemodel->getNoticeTypes())) {
            $group    = Taskflowtemplatemodel::GROUP_NOTICE;
            if (count($steps)>1) {
                $this->api_res(11205);
                return;
            }
        } else {
            $group    = Taskflowtemplatemodel::GROUP_AUDIT;
        }
        $description    = empty($input['description'])?'无':$input['description'];
        $name           = $input['name'];
        $employee_id    = $this->employee->id;
        $updated_at     = Carbon::now();
        $data           = "员工{$employee_id} 在{$updated_at} 修改了 {$type}的审批流，名称为 {$name}";
        $template       = Taskflowtemplatemodel::findOrFail($template_id);
        $ori_data       = $template->data;
        array_push($ori_data,$data);
        try{
            DB::beginTransaction();
            $template->step_template()->delete();
            $template->name = $name;
            $template->type = $type;
            $template->employee_id  = $employee_id;
            $template->description  = $description;
            $template->data = $ori_data;
            $template->group    = $group;
            $template->save();
            $template_id    = $template->id;
            $step_templates =[];
            for($a=0;$a<count($steps);$a++){
                $s_template = [];
                $s_template['template_id'] = $template_id;
                $s_template['name'] = empty($steps[$a]['name'])?"未设置":$steps[$a]['name'];
                $s_template['company_id'] = $this->company_id;
                $s_template['seq']    = $a+1;
                $s_template['group']  = $template->group;
                $s_template['position_ids']  = $steps[$a]['position_ids'];
                $s_template['type'] = $template->type;
                $step_templates[]   = $s_template;
            }
            Taskflowsteptemplatemodel::insert($step_templates);
            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

    /**
     * 删除模板
     */
    public function destroy()
    {
        $input  = $this->input->post(null,true);
        $template_id    = $input['template_id'];
        $template   = Taskflowtemplatemodel::findOrFail($template_id);
        $template->step_template()->delete();
        $template->delete();
        $this->api_res(0);
    }

    /**
     * 查看模板
     */
    public function show()
    {
        $input  = $this->input->post(null,true);
        $template_id    = $input['template_id'];
        $template   = Taskflowtemplatemodel::with('step_template')->where(
            ['company_id'=>$this->company_id]
        )->findOrFail($template_id);
        $this->load->model('positionmodel');
        $template->step_template->map(function($res){
            $position_ids   = explode(',',$res->position_ids);
            $res->positions  = Positionmodel::whereIn('id',$position_ids)->get()->toArray();
            return $res;
        });

        $this->api_res(0,$template);
    }

    /**
     * 审批流模板列表
     */
    public function listTemplate()
    {
        $this->load->model('employeemodel');
        $templates  = Taskflowtemplatemodel::with(['employee'=>function($query){
            $query->select('name','id');
        }])
            ->where('company_id',$this->company_id)
            ->get(['id','company_id','name','type','description','employee_id','created_at','updated_at','data']);

        $this->api_res(0,$templates);
    }

    /**
     *
     */
    private function validation()
    {
        return array(
            array(
                'field' => 'name',
                'label' => '名称',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'type',
                'label' => '类型',
                'rules' => 'trim|in_list[CHECKOUT,PRICE,RESERVE,SERVICE,WARNING,NO_LIABILITY,UNDER_CONTRACT]',
            ),
        );
    }

}
