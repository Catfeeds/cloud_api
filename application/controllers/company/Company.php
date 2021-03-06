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
	
	/**
	 * 注册
	 */
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
			$fieldarr = ['name', 'phone', 'code'];
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
		$post = $this->input->post(null, true);
		$this->load->helper('check');
		if (!isMobile($post['phone'])) {
			log_message('debug', '请检查手机号码');
			$this->api_res(1002, ['error' => '请检查手机号码']);
			return false;
		}
		$phone = $post['phone'];
		$this->load->library('sms');
		$code = str_pad(rand(1, 9999), 4, 0, STR_PAD_LEFT);
		$str  = str_ireplace('#code#', $code, config_item('yunpian_signature'));
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
		$post = $this->input->post(null, true);
		if (empty($post['code']) || !isset($post['code'])) {
			log_message('error', '没有上传code');
			$this->api_res(1002);
			return false;
		}
		$code   = $post['code'];
		$appid  = config_item('wx_web_appid');
		$secret = config_item('wx_web_secret');
		$url    = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
		$user   = $this->httpCurl($url, 'get', 'json');
		if (array_key_exists('errcode', $user) || empty($user['openid'])) {
			log_message('error', 'GET_ACCESS_TOKEN' . $user['errmsg']);
			$this->api_res(1006);
			return false;
		}
		$this->debug('返回用户信息为-->', $user);
		$info_url  = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $user['access_token'] . '&openid=' . $user['openid'] . '&lang=zh_CN';
		$user_info = $this->httpCurl($info_url, 'get', 'json');
		if (array_key_exists('errcode', $user_info)) {
			log_message('error', '请求info:' . $user_info['errmsg']);
			$this->api_res(1006);
			return false;
		}
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
		if (!$res) {
			log_message('error', '添加用户信息失败');
			$this->api_res(1009);
			return false;
		} else {
			$this->api_res(0);
		}
	}
	
	/**
	 * 企业认证
	 */
	public function certification()
	{
		$this->load->model('companymodel');
		$post = $this->input->post(null, true);
		if (!$this->validationAuth()) {
			$fieldarr = ['name', 'legal_person', 'phone', 'id_number', 'idcard_front', 'idcard_back', 'brand', 'brand_intro', 'license', 'license_image'];
			$this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
			return false;
		}
		$this->load->helper('check');
		if (!isIdNumber($post['id_number'])) {
			log_message('debug', '请填写正确的身份证号码');
			$this->api_res(1002, ['error' => '请填写正确的身份证号码']);
			return false;
		}
		
		$company_id = $this->company_id;
		$company    = Companymodel::Find($company_id);
		
		$company->fill($post);
		$company->license_image = $this->splitAliossUrl($post['license_image']);
		$company->idcard_front  = $this->splitAliossUrl($post['idcard_front']);
		$company->idcard_back   = $this->splitAliossUrl($post['idcard_back']);
		$company->status        = Companymodel::STATE_UNANTH;
		if ($company->save()) {
			$this->api_res(0);
		} else {
			$this->api_res(1009);
		}
	}
	
	/**
	 * 返回公司认证信息
	 */
	public function companyInfo()
	{
		$this->load->model('companymodel');
		$company_id = $this->company_id;
		$company    = Companymodel::where('id', $company_id)->get()
			->map(function ($company) {
				$company->license_image = $this->fullAliossUrl($company->license_image);
				$company->idcard_front  = $this->fullAliossUrl($company->idcard_front);
				$company->idcard_back   = $this->fullAliossUrl($company->idcard_back);
				return $company;
			})
			->toArray();
		$this->api_res(0, $company[0]);
	}
	
	/**
	 * 返回公司公众号信息
	 */
	public function companyWXInfo()
	{
		$this->load->model('companywxinfomodel');
		$company_id = $this->company_id;
		$company    = Companywxinfomodel::Find($company_id);
		if (empty($company)) {
			$this->api_res(0);
		}else{
			$this->api_res(0,$company->toArray());
		}
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
				'rules'  => 'trim|required|exact_length[4]',
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
				'label'  => '公司名称',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'legal_person',
				'label'  => '法人姓名',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'phone',
				'label'  => '法人电话',
				'rules'  => 'trim|required|max_length[13]',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'id_number',
				'label'  => '法人身份证号',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'idcard_front',
				'label'  => '法人身份证正面',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请上传%s',
				],
			],
			[
				'field'  => 'idcard_back',
				'label'  => '法人身份背面',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请上传%s',
				],
			],
			[
				'field'  => 'brand',
				'label'  => '品牌名称',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'brand_intro',
				'label'  => '品牌介绍',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'license',
				'label'  => '营业执照号码',
				'rules'  => 'trim|required',
				'errors' => [
					'required' => '请填写%s',
				],
			],
			[
				'field'  => 'license_image',
				'label'  => '营业执照照片',
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