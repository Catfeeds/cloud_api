<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/8/15
 * Time:        10:41
 * Describe:    公司
 */
class Company extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function Register()
	{
		$this->load->library('sms');
		$this->load->library('m_redis');
		$this->load->model('companymodel');
		$this->load->model('employeemodel');
		$this->load->model('privilegemodel');
		$this->load->model('positionmodel');
		$post = $this->input->post(null, true);
		if (!$this->validation()) {
			$fieldarr = ['name', 'is_show', 'sort'];
			$this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
			return false;
		}
		if (!$this->m_redis->verifySmsCode($post['phone'], $post['code'])) {
			$this->api_res(10008);
			return false;
		}
		$company = new Companymodel();
		$res     = $company->newCompany($post);
		if ($res) {
			$this->api_res(0, ['employee_id' => $res]);
		} else {
			$this->api_res(1009);
		}
	}
	
	/**
	 * 生成验证码缓存并发送短信
	 */
	public function sendCode()
	{
		$this->load->library('m_redis');
		$phone = $this->input->post('phone', true);
		$this->load->library('sms');
		$code = str_pad(rand(1, 9999), 4, 0, STR_PAD_LEFT);
		$str  = SMSTEXT . $code;
		$this->m_redis->storeSmsCode($phone, $code);
		$this->sms->send($str, $phone);
		$this->api_res(0);
	}
	
	/**
	 * 绑定微信
	 */
	public function boundWechat()
	{
		$this->load->model('employeemodel');
		$this->load->library('wechat');
		$post = $this->input->post(null, true);
		if (empty($post['code']) || !isset($post['code'])) {
			log_message('error','没有上传code');
			$this->api_res(10002);
			return false;
		}
		$wechat    = new Wechat();
		$info      = $wechat->getAccessToken($post['code']);
		$user_info = $wechat->getWXUserInfo($info);
		switch ($user_info['sex']) {
			case 1:
				$gender = 'M';
				break;
			case 2:
				$gender = 'W';
				break;
			default:
				$gender = 'N';
				break;
		}
		//整理用户信息
		$user    = ['nickname' => $user_info['nickname'],
		            'gender'   => $gender,
		            'avatar'   => $user_info['headimgurl'],
		            'openid'   => $user_info['openid'],
		            'unionid'  => $user_info['unionid'],
		            'province' => $user_info['province'],
		            'city'     => $user_info['city'],
		            'country'  => $user_info['country'],
		];
		$emplyee = new Employeemodel();
		$res     = $emplyee->updateEmployee(intval($post['id']), $user);
		if (!$res){
			$this->api_res(1009);
			return false;
		}else{
			$this->api_res(0);
		}
	}
	
	/**
	 * 企业认证
	 */
	public function auth()
	{
		$this->load->model('companymodel');
		$this->load->model('employeemodel');
		
	}
	
	/**
	 * 注册信息验证
	 */
	public function validation()
	{
		$this->load->library('form_validation');
		$config = [
			[
				'field'  => 'name',
				'label'  => '姓名',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'phone',
				'label'  => '电话',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'code',
				'label'  => '验证码',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
		];
		$this->form_validation->set_rules($config)->set_error_delimiters('', '');
		return $this->form_validation->run();
	}
	
	/**
	 * 认证信息验证
	 */
	public function validationAuth()
	{
		$this->load->library('form_validation');
		$config = [
			[
				'field'  => 'name',
				'label'  => '姓名',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'phone',
				'label'  => '电话',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'code',
				'label'  => '验证码',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
		];
		$this->form_validation->set_rules($config)->set_error_delimiters('', '');
		return $this->form_validation->run();
	}
}