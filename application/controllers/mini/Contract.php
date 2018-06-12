<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/10 0010
 * Time:        16:38
 * Describe:
 */
class Contract extends MY_Controller{

    public function notify(){
        log_message('error','FDD合同签署回调成功');
    }

    public function unSignContract()
    {

    }
}
