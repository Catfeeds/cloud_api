<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/29 0029
 * Time:        18:32
 * Describe:
 *
 */
class Storepaymodel extends Basemodel{

    protected $table    = 'boss_store_pay';

    protected $casts    = ['data'=>'array'];
}
