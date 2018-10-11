<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/9
 * Time: 16:35
 */
class Activityprizemodel extends Basemodel {
    protected $table = 'boss_activity_prize';
    public $timestamps = false;

    public function prize(){
        return $this->belongsTo(Activitymodel::class, 'id','prize_id');
    }
}
