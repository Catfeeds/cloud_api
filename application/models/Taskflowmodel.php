<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        11:01
 * Describe:
 */
class Taskflowmodel extends Basemodel
{
    protected $CI;
    const STATE_AUDIT   = 'AUDIT';
    const STATE_APPROVED= 'APPROVED';
    const STATE_UNAPPROVED= 'UNAPPROVED';
    const STATE_CLOSED  = 'CLOSED';

    const TYPE_CHECKOUT = 'CHECKOUT';
    const TYPE_PRICE    = 'PRICE';

    const CREATE_EMPLOYEE   = 'EMPLOYEE';
    const CREATE_CUSTOMER   = 'CUSTOMER';

    protected $table    = 'boss_taskflow';

    protected $fillable = [
        'company_id','name','type','description'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->CI=&get_instance();
    }

    /**
     * 发起员工
     */
    public function employee()
    {
        return $this->belongsTo(Employeemodel::class,'employee_id');
    }

    /**
     * 最近操作的步骤
     */
    public function step()
    {
        return $this->belongsTo(Taskflowstepmodel::class,'step_id');
    }

    /**
     * 审核步骤
     */
    public function steps()
    {
        return $this->hasMany(Taskflowstepmodel::class,'taskflow_id');
    }

    /**
     * 审核记录
     */
    public function record()
    {
        return $this->hasMany(Taskflowrecordmodel::class,'taskflow_id');
    }

    /**
     * 退房的信息
     */
    public function checkout()
    {
        return $this->hasOne(Checkoutmodel::class,'taskflow_id');
    }

    /**
     * 调价的信息
     */
    public function price()
    {
        return $this->hasOne(Pricecontrolmodel::class,'taskflow_id');
    }

    /**
     * 门店
     */
    public function store()
    {
        return $this->belongsTo(Storemodel::class,'store_id');
    }
    /**
     * 生成审批编号
     */
    public function newNumber($store_id)
    {
        $count  = $this
            ->where('store_id',$store_id)
            ->whereDate('created_at',date('Y-m-d'))
            ->count();
        $newCount   = $count+1;
        $serial_number  = date('Ymd').sprintf('%05s',$store_id).sprintf('%05s',$newCount);
        return $serial_number;
    }

    /**
     * 创建退房的同时创建退款的任务流
     */
    public function createTaskflow($room_id,$type,$store_id)
    {
        $this->CI->load->model('taskflowtemplatemodel');
        $this->CI->load->model('taskflowstepmodel');
        $this->CI->load->model('taskflowsteptemplatemodel');
        $template   = Taskflowtemplatemodel::where('company_id',COMPANY_ID)
            ->where('type',$type)
            ->first();
        if (empty($template)) {
            return false;
        }
        $step_field = ['id','company_id','name','type','seq','position_ids','employee_ids'];
        $step_template  = $template->step_template()->get($step_field);
        $taskflow   = new Taskflowmodel();
        $taskflow->fill($template->toArray());
        $taskflow->template_id  = $template->id;
        $taskflow->serial_number= $taskflow->newNumber($store_id);
        $taskflow->store_id     = $store_id;
        $taskflow->create_role  = Taskflowmodel::CREATE_EMPLOYEE;
        $taskflow->employee_id  = $this->CI->employee->id;
        $taskflow->status       = Taskflowmodel::STATE_AUDIT;
        $taskflow->room_id      = $room_id;
        $taskflow->save();
        $step_template_keys_transfer = ['step_template_id','company_id','name','type','seq','position_ids','employee_ids'];
        $step_template_arr  = $step_template->toArray();
        $step_merge_data = [
            'store_id'      => $store_id,
            'taskflow_id'   => $taskflow->id,
            'status'        => Taskflowstepmodel::STATE_AUDIT,
            'created_at'    => Carbon::now()->toDateTimeString(),
            'updated_at'    => Carbon::now()->toDateTimeString(),
        ];
        $result = [];
        foreach ($step_template_arr as $step){
            $step_combine   = array_combine($step_template_keys_transfer,$step);
            $result[]   = array_merge($step_merge_data,$step_combine);
        }
        Taskflowstepmodel::insert($result);

        return $taskflow->id;
    }

}