<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/9/13
 * Time:        16:44
 * Describe:    授权方微信信息
 */
class Companywxinfomodel extends Basemodel
{
	protected $table = 'fx_company_wxinfo';
	protected $hidden = ['deleted_at', 'created_at', 'updated_at'];
}