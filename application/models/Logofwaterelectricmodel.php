<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/8/9
 * Time:        10:06
 * Describe:
 */
class Logofwaterelectricmodel extends Basemodel {
    protected $table = 'boss_log_water_electricity';

    /**
     * 该记录所属房间
     */
    public function employee() {
        return $this->belongsTo(Employeemodel::class, 'employee_id');
    }

    public function transfer() {
        return $this->belongsTo(Meterreadingtransfermodel::class, 'transfer_id');
    }

}