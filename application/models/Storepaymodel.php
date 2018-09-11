<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/29 0029
 * Time:        18:32
 * Describe:
 *
 */
class Storepaymodel extends Basemodel {
    const STATE_UNDONE = 'UNDONE'; // 调起微信配置时的状态
    const STATE_DONE  = 'DONE'; //  微信回调支付成功状态
    protected $table = 'boss_store_pay';
    protected $casts = ['data' => 'array'];

    public function order(){

        return $this->belongsTo(Ordermodel::class,'id' ,'store_pay_id');
    }
}
