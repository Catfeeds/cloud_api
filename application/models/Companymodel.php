<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Illuminate\Database\Capsule\Manager as DB;

class Companymodel extends Basemodel
{
	protected $table = 'fx_company';
	protected $hidden = ['deleted_at', 'created_at', 'updated_at'];
	protected $fillable = ['bxid', 'name', 'nickname', 'address', 'contact_user', 'contact_phone',
	                       'base_position', 'phone', 'openid', 'unionid', 'license', 'remark',
	                       'status', 'privilege', 'expiretime',''];
	
	const STATE_UNSCAN = 'UNSCAN'; //未扫码
	const STATE_UNANTH = 'UNANTH'; //未认证
	const STATE_NORMAL = 'NORMAL'; //正常
	const STATE_CLOSE  = 'CLOSE';  //关闭
    const STATE_REGISTER    = 'REGISTER';
    const STATE_UNAPPROVED  = 'UNAPPROVED';
	
	const TYPE_CENTRALIZED = 'centralized'; //集中式
	const TYPE_DISTRIBUTED = 'distributed'; //集中式
	
	//公司的员工
	public function employee()
	{
		return $this->hasMany(Employeemodel::class, 'company_id');
	}
	
	//公司的门店
	public function store()
	{
		return $this->hasMany(Storemodel::class, 'company_id');
	}
	
	//查找公司信息
	public function getInfo($type, $sign)
	{
		switch ($type) {
			case 'id':
				$info = $this->find($sign);
				break;
			case 'phone':
				$info = $this->where('phone', $sign)->first();
				break;
			case 'wechat':
				$info = $this->where(WXID, $sign)->first();
				break;
			default:
				$info = null;
		}
		return $info;
	}
	
	/**
	 *  1.在fx_company表创建一条公司记录
	 *  2.在boss_employee中创建一条超级管理员权限的用户
	 *  3.在boss_position中里面创建一个新公司的新职位超级管理员权限
	 *  4.在boss_privilege为权限列表
	 */
	public function newCompany($post)
	{
		try {
			DB::beginTransaction();
			//创建新公司
			$this->contact_user  = $post['name'];
			$this->contact_phone = $post['phone'];
			$this->store_type    = $post['type'];
			$this->scale         = $post['number'];
			$this->status        = self::STATE_REGISTER;
			$this->save();
			//查询权限
			/*$privilege     = Privilegemodel::get(['id'])->toArray();
			$privilege_ids = '';
			foreach ($privilege as $key => $value) {
				$privilege_ids .= $value['id'] . ',';
			}
			//创建新职位
			$position                   = new Positionmodel();
			$position->company_id       = $this->id;
			$position->name             = '超级管理员';
			$position->pc_privilege_ids = $privilege_ids;
			$position->save();*/
			//创建新员工
			$employee              = new Employeemodel();
			$employee->name        = $post['name'];
			$employee->phone       = $post['phone'];
			$employee->company_id  = $this->id;
//			$employee->position_id = $position->id;
			$employee->position_id = 1;
			$employee->type        = 'ADMIN';
			$employee->position    = 'PRINCIPAL';
			$employee->save();
			$employee->bxid = $employee->id;
			$employee->save();
			$this->bxid = $employee->id;
			$this->save();
			DB::commit();
			return $employee->id;
		} catch (Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}
}
