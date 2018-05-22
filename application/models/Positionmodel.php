<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        15:07
 * Describe:
 */
class Positionmodel extends Basemodel{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $table    = 'boss_position';

<<<<<<< HEAD
    protected $hidden   = ['created_at','updated_at','deleted_at'];


    public function employee(){

        return $this->hasMany(Employeemodel::class,'position_id')->select('id','position_id');
    }

=======
    protected $hidden   = ['updated_at','deleted_at'];

    public function employee()
    {
        return $this->hasMany(Employeemodel::class,'position_id')->select(['id','position_id']);
    }
>>>>>>> a97dc516047a37d972262321686a838a0e191cd1
}
