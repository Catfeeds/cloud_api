<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use \Illuminate\Database\Eloquent\Model as Eloquent;

//此model应放入models文件夹，这里为了简化demo
class Usermodel extends Eloquent { protected $table= 'user'; }

class Eloquent_demo extends MY_Controller {

	public function index()
	{
		// $this->load->model('Usermodel');  //放入models文件夹的model应加载
		$users = Usermodel::all();
		$this->api_res(0,$users);
	}
	
}