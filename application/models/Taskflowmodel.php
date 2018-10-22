<?php
defined('BASEPATH') or exit('No direct script access allowed');
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

    const TYPE_CHECKOUT = 'CHECKOUT';                   //正常退房任务流
    const TYPE_CHECKOUT_NO_LIABILITY = 'NO_LIABILITY';  //免责退房任务流
    const TYPE_CHECKOUT_UNDER_CONTRACT = 'UNDER_CONTRACT';  //违约退房任务流(退款大于0)
    const TYPE_CHECKOUT_UNDER_CONTRACT_LESS = 'UNDER_CONTRACT_LESS'; //违约退房任务流(退款小于0)
    const TYPE_GIVE_UP  = 'GIVE_UP'; //放弃收益

    const TYPE_PRICE    = 'PRICE';
    const TYPE_RESERVE  = 'RESERVE';
    const TYPE_SERVICE  = 'SERVICE';
    const TYPE_WARNING  = 'WARNING'; //警告
    const GROUP_NOTICE  = 'NOTICE'; //通知类任务流
    const GROUP_AUDIT   = 'AUDIT'; //审核类任务流

    const CREATE_EMPLOYEE = 'EMPLOYEE';
    const CREATE_CUSTOMER = 'CUSTOMER';
    const CREATE_SYSTEM   = 'SYSTEM';

    protected $table = 'boss_taskflow';

    protected $casts = ['message' => 'array'];

    protected $fillable = [
        'company_id', 'name', 'type', 'description',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->CI = &get_instance();
    }

    /**
     * 发起员工
     */
    public function employee()
    {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }

    /**
     * 最近操作的步骤
     */
    public function step()
    {
        return $this->belongsTo(Taskflowstepmodel::class, 'step_id');
    }

    /**
     * 审核步骤
     */
    public function steps()
    {
        return $this->hasMany(Taskflowstepmodel::class, 'taskflow_id');
    }

    /**
     * 审核记录
     */
    public function record()
    {
        return $this->hasMany(Taskflowrecordmodel::class, 'taskflow_id');
    }

    /**
     * 门店
     */
    public function store()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

    /**
     * 房间
     */
    public function roomunion()
    {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    /**
     * 房型
     */
    public function roomtype()
    {
        return $this->belongsTo(Roomtypemodel::class, 'room_type_id');
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
        return $this->hasOne(Checkoutmodel::class, 'taskflow_id');
    }

    /**
     * 调价的信息
     */
    public function price()
    {
        return $this->hasOne(Pricecontrolmodel::class, 'taskflow_id');
    }

    /**
     * 预约看房的信息
     */
    public function reserve()
    {
        return $this->hasOne(Reserveordermodel::class, 'taskflow_id');
    }

    /**
     * 服务订单
     */
    public function service()
    {
        return $this->hasOne(Serviceordermodel::class, 'taskflow_id');
    }

    /**
     * 警告
     */
    public function warning()
    {
        return $this->belongsTo(Warningrecordmodel::class, 'data_id');
    }

    /**
     * 生成审批编号
     */
    public function newNumber($store_id)
    {
        $count = $this
            ->withTrashed()
            ->where('store_id', $store_id)
            ->whereDate('created_at', date('Y-m-d'))
            ->count();
        $newCount      = $count + 1;
        $serial_number = date('Ymd') . sprintf('%05s', $store_id) . sprintf('%05s', $newCount);
        return $serial_number;
    }

    /**
     * 创建任务流
     * @param type store_id room_id create_role,employee_id
     */
    public function createTaskflow(
        $company_id,
        $type,
        $store_id,
        $room_id,
        $create = self::CREATE_EMPLOYEE,
        $employee_id = null,
        $data_id = null,
        $message = null,
        $msg = ''
    ) {
        $this->CI->load->model('taskflowtemplatemodel');
        $this->CI->load->model('taskflowstepmodel');
        $this->CI->load->model('taskflowsteptemplatemodel');
        $template = Taskflowtemplatemodel::where('company_id', $company_id)
            ->where('type', $type)
            ->first();
        if (empty($template)) {
            return null;
        }
        $step_field    = ['id', 'company_id', 'name', 'type', 'seq', 'position_ids', 'employee_ids', 'group'];
        $step_template = $template->step_template()->get($step_field);
        if (empty($step_template->toArray())) {
            return null;
        }
        $taskflow = new Taskflowmodel();
        $taskflow->fill($template->toArray());
        $taskflow->template_id   = $template->id;
        $taskflow->serial_number = $taskflow->newNumber($store_id);
        $taskflow->store_id      = $store_id;
        $taskflow->create_role   = $create;
        $taskflow->data_id       = $data_id;
        $taskflow->message       = $message;
        $taskflow->employee_id   = empty($employee_id) ? null : $employee_id;
        $taskflow->status        = Taskflowmodel::STATE_AUDIT;
        $taskflow->group         = $template->group;
        $taskflow->room_id       = $room_id;
        $taskflow->save();
        $step_template_keys_transfer = ['step_template_id', 'company_id', 'name', 'type', 'seq', 'position_ids', 'employee_ids', 'group'];
        $step_template_arr           = $step_template->toArray();
        $step_merge_data             = [
            'store_id'    => $store_id,
            'taskflow_id' => $taskflow->id,
            'status'      => Taskflowstepmodel::STATE_AUDIT,
            'created_at'  => Carbon::now()->toDateTimeString(),
            'updated_at'  => Carbon::now()->toDateTimeString(),
        ];
        $result = [];
        foreach ($step_template_arr as $step) {
            $step_combine = array_combine($step_template_keys_transfer, $step);
            $result[]     = array_merge($step_merge_data, $step_combine);
        }
        Taskflowstepmodel::insert($result);

        $this->notify($taskflow->type, $msg, $this->listEmployees($taskflow->id));

        return $taskflow->id;
    }

    protected function notify($type, $msg, $employees)
    {
        if (empty($msg) || empty($employees)) {
            return;
        }
        log_message('debug', $type . ' notify  ' . $msg);
        switch ($type) {
            case self::TYPE_CHECKOUT:
            case self::TYPE_CHECKOUT_NO_LIABILITY:
            case self::TYPE_CHECKOUT_UNDER_CONTRACT:
            case self::TYPE_CHECKOUT_UNDER_CONTRACT_LESS:
            case self::TYPE_GIVE_UP:
                $this->sendCheckoutMsg(json_decode($msg), $employees);
                break;
            case self::TYPE_PRICE:
                $this->sendPriceMsg(json_decode($msg), $employees);
                break;
            case self::TYPE_WARNING:
                $this->sendWarningMsg(json_decode($msg), $employees);
                break;
            default:
                break;
        }
    }

    protected function sendPriceMsg($body, $employees = [])
    {
        if (!empty($body->type)) {
            switch ($body->type) {
                case Pricecontrolmodel::TYPE_ROOM:
                    $type   = '房租服务费';
                    break;
                case Pricecontrolmodel::TYPE_MANAGEMENT:
                    $type   = '物业服务费';
                    break;
                case Pricecontrolmodel::TYPE_ELECTRICITY:
                    $type   = '房间电费单价';
                    break;
                case Pricecontrolmodel::TYPE_WATER:
                    $type   = '房间冷水单价';
                    break;
                case Pricecontrolmodel::TYPE_HOTWATER:
                    $type   = '房间热水单价';
                    break;
                default:
                    $type = '未知类型';
                    break;
            }
        }else{
            $type = '未知类型';
        }

        $data = [
            'first'    => "有新的调价审核",
            'keyword1' => "{$body->store_name}-{$body->number}的调价申请",
            'keyword2' => "{$body->create_name}",
            'keyword3' => date('Y-m-d H:i:s'),
            'keyword4' => "调价类型:{$type}，调价金额:{$body->money}",
            'remark'   => '请尽快处理!',
        ];
        
        return $this->sendWechatMessage(config_item('tmplmsg_employee_TaskRemind'), $data, $employees);
    }

    protected function sendCheckoutMsg($body, $employees = [])
    {
        $data = [
            'first'    => "用户:{$body->name}提交了退房申请,请尽快处理!",
            'keyword1' => "{$body->type}退房申请",
            'keyword2' => $body->create_name,
            'keyword3' => Carbon::now()->toDateTimeString(),
            'keyword4' => "退租:{$body->store_name}-{$body->number}",
            'remark'   => '请尽快处理用户退房审批!',
        ];

        return $this->sendWechatMessage(config_item('tmplmsg_employee_TaskRemind'), $data, $employees);
    }

    protected function sendWarningMsg($body, $employees = [])
    {
        $content = '';
        if ($body->batch) {
            $content = "{$body->store_name} 有 {$body->count} 位住户超过48小时没有房间开锁记录。";
        }else{
            $content = "{$body->room_number}房间 {$body->username}住户 {$body->message}";
        }
        $data = [
            'first'    => "住户风险预警消息",
            'keyword1' => Carbon::now()->toDateTimeString(), // 时间
            'keyword2' => $content, // 内容
            'remark'   => '请尽快联系住户了解情况!',
        ];
        return $this->sendWechatMessage(config_item('tmplmsg_employee_Warning'), $data, $employees);
    }

    protected function sendWechatMessage($tpl_id, $data, $employees) {
        if (empty($tpl_id)) {
            log_message('error', "send wechat message failed, use nil message template id.");
            return;
        }
        if (empty($employees)) {
            log_message('info', "Warning: send wechat message to nobody.");
            return;
        }

        $this->CI->load->helper('common');
        $app = new Application(getWechatEmployeeConfig());
        foreach ($employees as $employee) {
            if (null == $employee['employee_mp_openid']) {
                log_message('error', '找不到openid');
                continue;
            }
            try {
                log_message('debug', 'try to send wechat message to '.$employee->name);
                $app->notice->send([
                    'touser'      => $employee['employee_mp_openid'],
                    'template_id' => $tpl_id,
                    'data'        => $data,
                    "miniprogram" => [
                        "appid"    => config_item('miniAppid'),
                        "pagepath" => "/pages/index/homePage",
                    ],
                ]);
                log_message('info', '发送微信模板消息成功, ' . $employee->name);
            } catch (Exception $e) {
                log_message('error', '退房审核消息通知失败：' . $e->getMessage());
                continue;
            }
        }
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
        $step_audit_first = $taskflow->steps()->whereIn('status', [Taskflowstepmodel::STATE_AUDIT, Taskflowstepmodel::STATE_UNAPPROVED])->first();
        if (!empty($step_audit_first)) {
            $step_audit_first->employee_id = $this->CI->employee->id;
            $step_audit_first->status      = Taskflowstepmodel::STATE_APPROVED;
            $step_audit_first->save();
            $this->createRecord($step_audit_first);
            $taskflow->step_id = $step_audit_first->id;
        }
        $steps_audit_count = $taskflow->steps()->whereIn('status', [Taskflowstepmodel::STATE_AUDIT, Taskflowstepmodel::STATE_UNAPPROVED])->count();
        if (0 == $steps_audit_count) {
            $taskflow->status = self::STATE_APPROVED;
        }
        $taskflow->save();
        if (self::STATE_APPROVED == $taskflow->status) {
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
        $record->step_id = $step->id;
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
//        $step_audit_first = $taskflow->steps()->whereIn('status', [Taskflowstepmodel::STATE_AUDIT, Taskflowstepmodel::STATE_UNAPPROVED])->first();
//        $update_arr       = ['status' => Taskflowmodel::STATE_CLOSED];
//        if (!empty($step_audit_first)) {
//            $step_audit_first->employee_id = $this->CI->employee->id;
//            $step_audit_first->status      = Taskflowstepmodel::STATE_UNAPPROVED;
//            $step_audit_first->remark      = '关闭任务流';
//            $step_audit_first->save();
//            $this->createRecord($step_audit_first);
//            $update_arr['step_id'] = $step_audit_first->id;
//        }
//        $taskflow->status = Taskflowmodel::STATE_CLOSED;
//        $taskflow->save();
        $taskflow->steps()->delete();
        $taskflow->record()->delete();
        $taskflow->deleted_at   = date('Y-m-d H:i:s',time());
        $taskflow->save();
        return true;
    }

    /**
     * 在原任务流下重新发起任务流审核步骤
     */
    public function reissueTaskflow(
        $taskflow,
        $company_id,
        $type,
        $store_id,
        $room_id,
        $create = self::CREATE_EMPLOYEE,
        $employee_id = null,
        $data_id = null,
        $message = null,
        $msg = ''
    ) {
        $this->CI->load->model('taskflowtemplatemodel');
        $this->CI->load->model('taskflowstepmodel');
        $this->CI->load->model('taskflowsteptemplatemodel');
        $template = Taskflowtemplatemodel::where('company_id', $company_id)
            ->where('type', $type)
            ->first();
        if (empty($template)) {
            return null;
        }
        $step_field    = ['id', 'company_id', 'name', 'type', 'seq', 'position_ids', 'employee_ids', 'group'];
        $step_template = $template->step_template()->get($step_field);
        if (empty($step_template->toArray())) {
            return null;
        }

        $taskflow->fill($template->toArray());
        $taskflow->template_id   = $template->id;
//        $taskflow->serial_number = $taskflow->newNumber($store_id);
        $taskflow->store_id      = $store_id;
        $taskflow->create_role   = $create;
        $taskflow->data_id       = $data_id;
        $taskflow->message       = $message;
        $taskflow->employee_id   = empty($employee_id) ? null : $employee_id;
        $taskflow->status        = Taskflowmodel::STATE_AUDIT;
        $taskflow->group         = $template->group;
        $taskflow->room_id       = $room_id;
        $taskflow->save();
        $step_template_keys_transfer = ['step_template_id', 'company_id', 'name', 'type', 'seq', 'position_ids', 'employee_ids', 'group'];
        $step_template_arr           = $step_template->toArray();
        $step_merge_data             = [
            'store_id'    => $store_id,
            'taskflow_id' => $taskflow->id,
            'status'      => Taskflowstepmodel::STATE_AUDIT,
            'created_at'  => Carbon::now()->toDateTimeString(),
            'updated_at'  => Carbon::now()->toDateTimeString(),
        ];
        $result = [];
        foreach ($step_template_arr as $step) {
            $step_combine = array_combine($step_template_keys_transfer, $step);
            $result[]     = array_merge($step_merge_data, $step_combine);
        }
        Taskflowstepmodel::insert($result);

        $this->notify($taskflow->type, $msg, $this->listEmployees($taskflow->id));

        return $taskflow->id;
    }


}
