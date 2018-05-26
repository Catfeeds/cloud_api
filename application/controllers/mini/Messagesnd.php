<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\template_message;
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/25
 * Time:        20:27
 * Describe:    推送通知
 */

class Messagesnd extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('Messagesndmodel');
    }

}