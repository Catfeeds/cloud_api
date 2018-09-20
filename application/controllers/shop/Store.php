
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/23 0023
 * Time:        11:43
 * Describe:
 */
class Store extends MY_Controller {

    protected $employee;
    public function __construct() {

        parent::__construct();
        $this->load->model('storemodel');
        //需放在MY_CONTROLLER
        if (defined(CURRENT_ID)) {
            $this->load->model('employeemodel');
            $this->current_user = Funxadminmodel::where('bxid', $this->current_id)->all();
        }
        //---END

    }

    //添加门店
    public function addStore() {
        $post = $this->input->post(NULL, TRUE);
    }

    //门店列表
    public function listStore() {

    }

    //修改门店
    public function updateStore() {

    }

    //查找门店(按门店名？)
    public function searchStore() {

    }

}
