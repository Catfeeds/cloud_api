<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:07
 * Describe:    [FUNX]
 */

class Funxadminmodel extends Basemodel {

    protected $table    = 'fx_funx_admin_users';
    
    protected $hidden   = ['id','openid','unionid','deleted_at','created_at','updated_at'];
}