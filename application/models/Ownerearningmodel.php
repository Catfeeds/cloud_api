<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        17:31
 * Describe:
 */
class Ownerearningmodel extends Basemodel
{
    public function __construct()
    {
        parent::__construct();
    }

    protected $table = 'boss_owner_earning';

    protected $hidden = ['created_at', 'updated_at'];

    public function deductions()
    {
        return $this->hasMany(Ownerdeductionmodel::class, 'earnings_id');
    }

}