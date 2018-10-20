<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/9
 * Time: 16:35
 */
class Activityvisitmodel extends Basemodel {
    protected $table = 'boss_activity_visit';

    public function resident(){
        return $this->belongsTo(Residentmodel::class, 'customer_id', 'customer_id');
    }
}
