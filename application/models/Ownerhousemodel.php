<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        10:17
 * Describe:
 */

class Ownerhousemodel extends Basemodel {

    public function __construct() {
        parent::__construct();
    }

    protected $table = 'boss_owner_house';

    protected $hidden = ['created_at', 'updated_at'];

}
