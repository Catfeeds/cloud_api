<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use EasyWeChat\Foundation\Application;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/7/30 0030
 * Time:        11:01
 * Describe:
 */
class Taskflowmodel extends Basemodel
{
    protected $CI;
    const STATE_AUDIT      = 'AUDIT';
    const STATE_APPROVED   = 'APPROVED';
    const STATE_UNAPPROVED = 'UNAPPROVED';
    const STATE_CLOSED     = 'CLOSED';

    const TYPE_CHECKOUT = 'CHECKOUT';
    const TYPE_PRICE    = 'PRICE';
    const TYPE_RESERVE  = 'RESERVE';
    const TYPE_SERVICE  = 'SERVICE';
    const TYPE_WARNING  = 'WARNING';    //警告
    const GROUP_NOTICE  = 'NOTICE';     //通知类任务流
    const GROUP_AUDIT   = 'AUDIT';      //审核类任务流

    const CREATE_EMPLOYEE   = 'EMPLOYEE';
    const CREATE_CUSTOMER   = 'CUSTOMER';
    const CREATE_SYSTEM     = 'SYSTEM';

    protected $table    = 'boss_taskflow';

    protected $casts    = ['message'=>'array'];

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
     * 门店
     */
    public function store()
    {
        return $this->belongsTo(Storemodel::class,'store_id');
    }

    /**
     * 房间
     */
    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class,'room_id');
    }

    /**
     * 房型
     */
    public function roomtype()
    {
        return $this->belongsTo(Roomtypemodel::class,'room_type_id');
    }

    /******************************************** 不同的关联流程 *********************************************/
    /**
     * 综合展示详情
     */
//    public function detail($a)
//    {
//        switch ($this->type) {
//            case self::TYPE_CHECKOUT:
//                $this->CI->load->model('checkoutmodel');
//                return $this->hasOne(Checkoutmodel::class,'taskflow_id');
//                break;
//            case self::TYPE_PRICE:
//                $this->CI->load->model('pricecontrolmodel');
//                return $this->hasOne(Pricecontrolmodel::class,'taskflow_id');
//                break;
//            case self::TYPE_RESERVE:
//                $this->CI->load->model('reserveordermodel');
//                return $this->hasOne(Reserveordermodel::class,'taskflow_id');
//                break;
//            default:
//                return null;
//        }
//
//    }


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
     * 预约看房的信息
     */
    public function reserve()
    {
        return $this->hasOne(Reserveordermodel::class,'taskflow_id');
    }

    /**
     * 服务订单
     */
    public function service()
    {
        return $this->hasOne(Serviceordermodel::class,'taskflow_id');
    }

    /**
     * 警告
     */
    public function warning()
    {
        return $this->belongsTo(Warningrecordmodel::class,'data_id');
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
     * 创建任务流
     * @param type store_id room_id create_role,employee_id
     */
    public function createTaskflow($company_id,$type,$store_id,$room_id,$create=self::CREATE_EMPLOYEE,$employee_id=null,$data_id=null,$message=null,$msg='')
    {
        $this->CI->load->model('taskflowtemplatemodel');
        $this->CI->load->model('taskflowstepmodel');
        $this->CI->load->model('taskflowsteptemplatemodel');
        $template   = Taskflowtemplatemodel::where('company_id',$company_id)
            ->where('type',$type)
            ->first();
        if (empty($template)) {
            return null;
        }
        $step_field = ['id','company_id','name','type','seq','position_ids','employee_ids','group'];
        $step_template  = $template->step_template()->get($step_field);
        if(empty($step_template->toArray())){
            return null;
        }
        $taskflow   = new Taskflowmodel();
        $taskflow->fill($template->toArray());
        $taskflow->template_id  = $template->id;
        $taskflow->serial_number= $taskflow->newNumber($store_id);
        $taskflow->store_id     = $store_id;
        $taskflow->create_role  = $create;
        $taskflow->data_id  = $data_id;
        $taskflow->message  = $message;
        $taskflow->employee_id  = empty($employee_id)?null:$employee_id;
        $taskflow->status       = Taskflowmodel::STATE_AUDIT;
        $taskflow->group        = $template->group;
        $taskflow->room_id      = $room_id;
        $taskflow->save();
        $step_template_keys_transfer = ['step_template_id','company_id','name','type','seq','position_ids','employee_ids','group'];
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

        $this->notify($taskflow->type, $msg, $this->listEmployees($taskflow->id));

        return $taskflow->id;
    }

    protected function notify($type, $msg, $employees)
    {
        log_message('debug', $type . ' notify  ' . $msg);
        switch ($type) {
            case self::TYPE_CHECKOUT:
                $this->sendCheckoutMsg(json_decode($msg), $employees);
                break;
            case self::TYPE_PRICE:
                $this->sendPriceMsg(json_decode($msg), $employees);
                break;
            case self::TYPE_WARNING:
                $this->sendWarningMsg(json_encode($msg),$employees);
                break;
            default:
                break;
        }
    }

    protected function sendPriceMsg($body,$employees=[])
    {
        $data = [
            'first'     => "有新的调价审核",
            'keyword1'  => "{$body->store_name}-{$body->number}的调价申请",
            'keyword2'  => "{$body->create_name}",
            'keyword3'  =>  date('Y-m-d H:i:s'),
            'keyword4'  => "调价类型:{$body->type}，调价金额:{$body->money}",
            'remark'    => '请尽快处理!',
        ];
        // $this->CI->load()
        $this->CI->load->helper('common');
        $app = new Application(getWechatEmployeeConfig());
        foreach ($employees as $employee) {
            if (null == $employee['employee_mp_openid']) {
                log_message('error', '找不到openid');
                continue;
            }
            try {
                log_message('debug', 'try to 调价审核通知');
                $app->notice->send([
                    'touser' => $employee['employee_mp_openid'],
                    'template_id' => config_item('tmplmsg_employee_TaskRemind'),
                    'data' => $data,
                    "miniprogram"=> [
                        "appid"=> config_item('miniAppid'),
                        "pagepath"=>"/pages/index/homePage"
                    ],
                ]);
                log_message('info', '微信回调成功发送模板消息: ' . $employee->name);
            } catch (Exception $e) {
                log_message('error', '调价审核消息通知失败：' . $e->getMessage());
//                throw $e;
                return;
            }
        }
    }

    protected function sendCheckoutMsg($body,$employees=[])
    {
        $data   = [
            'first'     => "用户:{$body->name}提交了退房申请,请尽快处理!",
            'keyword1'  => '退房申请',
            'keyword2'  => $body->create_name,
            'keyword3'  => Carbon::now()->toDateTimeString(),
            'keyword4'  => "退租:{$body->store_name}-{$body->number}",
            'remark'    => '请尽快处理用户退房审批!'
        ];
        // $this->CI->load()
        $this->CI->load->helper('common');
        $app = new Application(getWechatEmployeeConfig());
        foreach ($employees as $employee) {
            if (null == $employee['employee_mp_openid']) {
                log_message('error', '找不到openid');
                continue;
            }
            try {
                log_message('debug', 'try to 退房审批通知');
                $app->notice->send([
                    'touser' => $employee['employee_mp_openid'],
                    'template_id' => config_item('tmplmsg_employee_TaskRemind'),
                    'data' => $data,
                    "miniprogram"=> [
                        "appid"=> config_item('miniAppid'),
                        "pagepath"=>"/pages/index/homePage"
                    ],
                ]);
                log_message('info', '微信回调成功发送模板消息: ' . $employee->name);
            } catch (Exception $e) {
                log_message('error', '退房审核消息通知失败：' . $e->getMessage());
//                throw $e;
                return;
            }
        }
    }

    protected function sendWarningMsg($body,$employees=[])
    {
        return;
    }

    public function listEmployees($taskflow_id)
    {
        $audit = Taskflowstepmodel::where('status', '!=', Taskflowstepmodel::STATE_APPROVED)
            ->where('taskflow_id', $taskflow_id)
            ->first();
        $this->CI->load->model('employeemodel');
        $employee_list = Employeemodel::whereIn('position_id', explode(',', $audit['position_ids']))
            ->get();

        $ret = [];
        foreach ($employee_list as $employee) {
            $store_arr = explode(',', $employee['store_ids']);
            if (!in_array($audit['store_id'], $store_arr)) {
                continue;
            }
            $ret[] = $employee;
        }

        return $ret;
    }

    /**
     * 通过任务流
     */
    public function approveTaskflow($taskflow)
    {
        if (empty($taskflow)) {
            return true;
        }
        $this->CI->load->model('taskflowstepmodel');
        $this->CI->load->model('taskflowrecordmodel');
        $step_audit_first    = $taskflow->steps()->whereIn('status',[Taskflowstepmodel::STATE_AUDIT,Taskflowstepmodel::STATE_UNAPPROVED])->first();
        if (!empty($step_audit_first)) {
            $step_audit_first->employee_id  = $this->CI->employee->id;
            $step_audit_first->status   = Taskflowstepmodel::STATE_APPROVED;
            $step_audit_first->save();
            $this->createRecord($step_audit_first);
            $taskflow->step_id  = $step_audit_first->id;
        }
        $steps_audit_count    = $taskflow->steps()->whereIn('status',[Taskflowstepmodel::STATE_AUDIT,Taskflowstepmodel::STATE_UNAPPROVED])->count();
        if ($steps_audit_count==0) {
            $taskflow->status   = self::STATE_APPROVED;
        }
        $taskflow->save();
        if ($taskflow->status==self::STATE_APPROVED) {
            return true;
        } else {
            return false;
        }
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
     * 关闭任务流
     */
    public function closeTaskflow($taskflow)
    {
        if (empty($taskflow)) {
            return true;
        }
        $this->CI->load->model('taskflowstepmodel');
        $this->CI->load->model('taskflowrecordmodel');
        $step_audit_first    = $taskflow->steps()->whereIn('status',[Taskflowstepmodel::STATE_AUDIT,Taskflowstepmodel::STATE_UNAPPROVED])->first();
        $update_arr = ['status'=>Taskflowmodel::STATE_CLOSED];
        if (!empty($step_audit_first)) {
            $step_audit_first->employee_id  = $this->CI->employee->id;
            $step_audit_first->status   = Taskflowstepmodel::STATE_UNAPPROVED;
            $step_audit_first->remark   = '关闭任务流';
            $step_audit_first->save();
            $this->createRecord($step_audit_first);
            $update_arr['step_id']  = $step_audit_first->id;
        }
        $taskflow->status=Taskflowmodel::STATE_CLOSED;
        $taskflow->save();
        return true;
    }



}