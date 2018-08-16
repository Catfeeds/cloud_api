<?php
defined('BASEPATH') OR exit('No direct script access allowed');


/**
 * User: weijinlong
 * Date: 2018-08-16
 * Time: 10:37
 */
/**
 * for test
 */
class Index extends MY_Controller
{
    public function __construct() {
        parent::__construct();
       
    }

    public function index()
    {
        echo 'ok->'.X_API_TOKEN;
    }


}
