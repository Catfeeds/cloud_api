<?php
/**
 * User: wws
 * Date: 2018-05-23
 * Time: 18:46
 */

class Fddrecordmodel extends Basemodel
{
    /**
     * 合同的状态
     */
    const STATUS_INITIATED = 'INITIATED';       //发起签署 or 未进行签署
    const STATUS_SUCCEED   = 'SUCCEED';         //签署成功
    const STATUS_FAILED    = 'FAILED';          //签署失败

    const ROLE_A           = 'A';     //签署动作发起人 : 甲方
    const ROLE_B           = 'B';     //签署动作发起人 : 乙方

    const TYPE_NORMAL      = 'NORMAL';      //线下纸质合同
    const TYPE_FDD         = 'FDD';         //法大大电子合同

    protected $table       = 'boss_fdd_transaction';

    protected $dates       = ['created_at', 'updated_at'];

    protected $fillable    = [];

    public function contract()
    {
        return $this->belongsTo(Contractmodel::class, 'contract_id');
    }
}
