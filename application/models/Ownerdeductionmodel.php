<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        17:45
 * Describe:
 */
class Ownerdeductionmodel extends Basemodel {
    public function __construct() {
        parent::__construct();
    }

    protected $table = 'boss_owner_deduction';

    protected $hidden = ['created_at', 'updated_at'];

    public function ownerEarning(){
        return $this->hasOne(Ownerearningmodel::class, 'id', 'earnings_id');
    }

    public function house(){
        return $this->belongsTo(Ownerhousemodel::class, 'house_id');
    }
}
