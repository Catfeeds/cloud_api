<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/4 0014
 * Time:        15:07
 * Describe:
 */
class Privilegemodel extends Basemodel {

    public function __construct()
    {
        parent::__construct();
    }

    protected $table    = 'boss_privilege';

    protected $hidden   = ['updated_at','deleted_at'];

}