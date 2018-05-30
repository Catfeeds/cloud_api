<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        14:46
 * Describe:    员工管理
 */
class Employee extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
    }

    /*public function showMyStores()
    {
        $employee = Employeemodel::getMyStores();
        if (!$employee) {
            $this->api_res(1009);
        }
        $this->api_res(0, $employee);
    }*/

    /**
     * 显示员工权限信息
     */
    public function listEmp()
    {
        $this->load->model('positionmodel');
        $post = $this->input->post(null, true);
        $page = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $field = ['id', 'name', 'phone', 'position_id', 'store_names', 'hiredate', 'status'];
        //define('COMPANY_ID', 4); //测试用
        $where = isset($post['store_id']) ? ['store_id' => $post['store_id']] : [];
        if (isset($post['city']) && !empty($post['city'])) {
            $this->load->model('storemodel');
            $store_ids = Storemodel::where('company_id', COMPANY_ID)
                ->where('city', $post['city'])->get(['id'])->map(function ($s) {
                    return $s->id;
                });
            $count = ceil((Employeemodel::whereIn('store_ids', $store_ids)->where('status', 'ENABLE')->count()) / PAGINATE);
            if ($page > $count) {
                $this->api_res(0, ['count' => $count, 'list' => []]);
                return;
            }
            $category = Employeemodel::with(['position' => function ($query) {
                $query->select('id', 'name');
            }])->whereIn('store_ids', $store_ids)->where($where)->where('status', 'ENABLE')
                ->offset($offset)->limit(PAGINATE)->orderBy('id', 'desc')->get($field);
            $this->api_res(0, ['count' => $count, 'list' => $category]);
            return;
        }
        $count = ceil((Employeemodel::where('company_id', COMPANY_ID)->where('status', 'ENABLE')->count()) / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->where('company_id', COMPANY_ID)->where('status', 'ENABLE')->offset($offset)
            ->limit(PAGINATE)->orderBy('id', 'desc')->get($field);
        $this->api_res(0, ['count' => $count, 'list' => $category]);
    }

    /**
     * 按名称模糊查找
     */
    public function searchEmp()
    {
        $field = ['name', 'phone', 'position_id', 'store_names', 'hiredate', 'status'];
        $this->load->model('positionmodel');
        $post   = $this->input->post(null,true);
        $name   = isset($post['name'])?$post['name']:null;
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = PAGINATE * ($page-1);
        //define('COMPANY_ID', 4); //测试用
        $count  = ceil((Employeemodel::where('company_id', COMPANY_ID)
                ->where('name','like',"%$name%")->count())/PAGINATE);
        if($page > $count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $this->load->model('storemodel');
        $store_ids = Storemodel::where('company_id', COMPANY_ID)->get(['id'])->map(function ($s) {
                return $s->id;
            });
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->whereIn('store_ids', $store_ids)->where('name','like',"%$name%")
            ->where('status', 'ENABLE')->offset($offset)
            ->limit(PAGINATE)->orderBy('id', 'desc')->get($field);
        $this->api_res(0,['count'=>$count,'list'=>$category]);
    }

    /**
     * 获取城市门店信息
     */
    public function getStore()
    {
        $filed = ['id', 'name','city'];
        $this->load->model('storemodel');
        $category = Storemodel::get($filed)->groupBy('city');
        if(!$category){
            $this->api_res(1009);
            return;
        }
        return $category;
    }

    /**
     * 显示城市门店
     */
    public function showStore()
    {
        $category = $this->getStore();
        $post = $this->input->post(null, true);
        if (isset($post['id']) && !empty($post['id'])) {
            $id = trim($post['id']);
            $emloyee = Employeemodel::find($id);
            if (!$emloyee) {
                $this->api_res(1009);
                return false;
            }

            $this->load->model('positionmodel');
            $position = Positionmodel::find($emloyee->position_id);
            if (!$position) {
                $this->api_res(1009, ['error' => '没有找到职位信息']);
                return false;
            }

            $category = [
                'name' => $emloyee->name,
                'phone' => $emloyee->phone,
                'position' => $position->name,
                'status' => $emloyee->status,
                'store_ids' => $emloyee->store_ids,
                'store_names' => $emloyee->store_names,
            ];
        }
        $this->api_res(0, $category);
    }

    /**
     * 提交员工信息
     */
    public function submitEmp()
    {
        $post = $this->input->post(null, true);
        $config = $this->validation();
        if(!$this->validationText($config))
        {
            $fieldarr = ['name', 'phone', 'position', 'store_ids', 'store_names', 'hiredate'];
            $this->api_res(1002,['error'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $name = $post['name'];
        $position = $post['position'];
        $this->load->model('positionmodel');
        $position_arr = Positionmodel::where('name', $position)->get(['id'])->toArray();
        if (!$position_arr) {
            $this->api_res(1009);
            return false;
        }
        $position_id = $position_arr[0]['id'];
        $store_ids = $post['store_ids'];
        $store_names = $post['store_names'];
        $phone = $post['phone'];
        $hiredate = $post['hiredate'];

        $this->load->model('storemodel');
        if (!defined('COMPANY_ID')) {
            $this->api_res(1002,['error'=>'公司信息不符']);
            return;
        }
        $ids= Storemodel::where('company_id',COMPANY_ID)->get(['id'])->map(function($a){
            return $a->id;
        })->toArray();

        $store_ids_arr = explode(',' ,$store_ids);
        if(!empty(array_diff($store_ids_arr, $ids))){
            $this->api_res(1002,['error'=>'门店不符']);
            return;
        }
        $store_id = $store_ids_arr[0];

        $employee               = new Employeemodel();
        $employee->store_ids    = $store_ids;
        $employee->store_names  = $store_names;
        $employee->store_id     = $store_id;
        $employee->position_id  = $position_id;
        $employee->name         = $name;
        $employee->phone        = $phone;
        $employee->hiredate     = $hiredate;

        if ($employee->save())
        {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 编辑员工信息
     */
    public function updateEmp()
    {
        $post = $this->input->post(null, true);
        $config = $this->validation();
        array_pull($config, '5');
        $status_val = ['field' => 'status', 'label' => '员工状态', 'rules' => 'trim|required|in_list[ENABLE,DISABLE]'];
        $config = array_add($config, '5', $status_val);
        if(!$this->validationText($config))
        {
            $fieldarr   = ['name', 'phone', 'position', 'store_ids', 'store_names', 'status'];
            $this->api_res(1002,['error'=>$this->form_first_error($fieldarr)]);
            return false;
        }

        $id = isset($post['id']) ? $post['id'] : null;
        $position = $post['position'];
        $this->load->model('positionmodel');
        $position_arr = Positionmodel::where('name', $position)->get(['id'])->toArray();
        if (!$position_arr) {
            $this->api_res(1009);
            return;
        }
        $position_id = $position_arr[0]['id'];
        $store_ids  = $this->input->post('store_ids',true);
        $store_names  = $this->input->post('store_names',true);
        $name = $post['name'];
        $phone = $post['phone'];
        $status = $post['status'];

        $this->load->model('storemodel');
        if (!defined('COMPANY_ID')) {
            $this->api_res(1002,['error'=>'公司信息不符']);
            return;
        }
        $ids= Storemodel::where('company_id',COMPANY_ID)->get(['id'])->map(function($a){
            return $a->id;
        })->toArray();

        $store_ids_arr = explode(',' ,$store_ids);
        if(!empty(array_diff($store_ids_arr, $ids))){
            $this->api_res(1002,['error'=>'门店不符']);
            return;
        }
        $store_id = $store_ids_arr[0];

        $employee = Employeemodel::find($id);
        $employee->position_id  = $position_id;
        $employee->store_ids    = $store_ids;
        $employee->store_names  = $store_names;
        $employee->store_id     = $store_id;
        $employee->name         = $name;
        $employee->phone        = $phone;
        $employee->status       = $status;

        if ($employee->save())
        {
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }

    }

    /**
     * 添加员工 二维码
     */
    public function qrcodeAddCompany(){
        $post   = $this->input->post(NULL,true);
        $id     = isset($post['id'])?$post['id']:NULL;
        $code   = isset($post['code'])?$post['code']:NULL;

        $id     = str_replace(' ','',trim(strip_tags($id)));
        $code   = str_replace(' ','',trim(strip_tags($code)));

        $appid  = config_item('wx_web_appid');
        $secret = config_item('wx_web_secret');
        $url    = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
        $user   = $this->httpCurl($url,'get','json');
        if(array_key_exists('errcode',$user))
        {
            $this->api_res(1003);
            return false;
        }
        $company             = Employeemodel::where('id',$id)->first();
        $company->openid     = $user['openid'];
        $company->unionid    = $user['unionid'];
        if($company->save()){
            $company->status = 'NORMAL';
            $company->save();
            $this->api_res(0);
        }else{
            $this->api_res(1009);
            return false;
        }
    }

    /**
     * 删除员工信息（将员工状态设置为离职）
     */
    public function delEmp()
    {
        $post = $this->input->post(null, true);
        if (isset($post['id']) && !empty($post['id'])) {
            $id = $post['id'];
            $position = Employeemodel::find($id);
            $position->status = 'DISABLE';
            if($position->save()){
                $this->api_res(0,['message' => '员工已删除，请及时转移相关业务']);
                return false;
            }else{
                $this->api_res(1009);
                return false;
            }
        } else {
            $this->api_res(1002);
            return false;
        }
    }


    /**
     * 验证
     */
    public function validation()
    {
        $config = array(
            array(
                'field' => 'name',
                'label' => '员工姓名',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'phone',
                'label' => '手机号',
                'rules' => 'trim|required|max_length[13]|numeric',
            ),
            array(
                'field' => 'position',
                'label' => '职位id',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'store_ids',
                'label' => '可操作的门店',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'store_names',
                'label' => '门店名称',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'hiredate',
                'label' => '入职时间',
                'rules' => 'trim|required',
            ),
        );

        return $config;
    }

}
