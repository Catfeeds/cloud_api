<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/20 0020
 * Time:        16:05
 * Describe:    BOSS
 * 合同模板表
 */
class Contracttemplatemodel extends Basemodel {

    protected $table = 'boss_contract_template';

    protected $hidden = ['created_at', 'updated_at', 'deleted_at', 'fdd_tpl_id', 'fdd_tpl_path'];

}
