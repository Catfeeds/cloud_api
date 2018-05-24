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

    /**
     * 显示员工权限信息
     */
    public function listEmp()
    {
        $this->load->model('positionmodel');
        $this->load->model('storemodel');

        $post = $this->input->post(null, true);
        $page = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $filed = ['name', 'phone', 'position_id', 'store_names', 'hiredate', 'status'];
        $where = isset($post['store_id']) ? ['store_id' => $post['store_id']] : [];
        if (isset($post['city']) && !empty($post['city'])) {
            $store_ids = Storemodel::where('city', $post['city'])->get(['id'])->map(function ($s) {
                return $s['id'];
            });
            $count = ceil((Employeemodel::whereIn('store_id', $store_ids)->where($where)->count()) / PAGINATE);
            if ($page > $count) {
                $this->api_res(0, ['count' => $count, 'list' => []]);
                return;
            }
            $category = Employeemodel::with(['position' => function ($query) {
                $query->select('id', 'name');
            }])->whereIn('store_id', $store_ids)->where($where)
                ->offset($offset)->limit(PAGINATE)->orderBy('id', 'desc')->get($filed);
            $this->api_res(0, ['count' => $count, 'list' => $category]);
            return;
        }
        $count = ceil((Employeemodel::all()->count()) / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->offset($offset)->limit(PAGINATE)->orderBy('id', 'desc')->get($filed);

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
        $count  = ceil((Employeemodel::where('name','like',"%$name%")->count())/PAGINATE);
        if($page > $count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->where('name','like',"%$name%")->offset($offset)
            ->limit(PAGINATE)->orderBy('id','desc')->get($field);
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
       /* $post = $this->input->post(null, true);
        $name = isset($post['name']) ? $post['name'] : null;
        $phone = isset($post['phone']) ? $post['phone'] : null;
        $hiredate = isset($post['hiredate']) ? $post['hiredate'] : null;
        $position = isset($post['position']) ? $post['position'] : null;
        $this->load->model('positionmodel');
        DB::beginTransaction();
        $result = Positionmodel::insert(
            [   'name' => $position,
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time())
            ]);
        if (!$result) {
            DB::rollBack();
            $this->api_res(1009);
            return;
        }
        DB::commit();
        $position_id = Positionmodel::insertGetId(
            [ 'name' => $position,
              'created_at' => date('Y-m-d H:i:s', time()),
              'updated_at' => date('Y-m-d H:i:s', time())
            ]);
        try {
            $result = Employeemodel::insert(
                [   'name' => $name,
                    'phone' => $phone,
                    'hiredate' => $hiredate,
                    'position_id' => $position_id,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time())
                ]);
            if (!$result) {
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        */

        $category = $this->getStore();
        $this->api_res(0, $category);
    }

    /**
     * 提交员工信息
     */
    public function submitEmp()
    {
        $post = $this->input->post(null, true);
        if(!$this->validation())
        {
            $fieldarr   = ['name', 'phone', 'position', 'store_ids','store_names', 'hiredate'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $position = isset($post['position']) ? $post['position'] : null;
        $this->load->model('positionmodel');
        $position_id = Positionmodel::insertGetId(
            [   'name' => $position,
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time())
            ]);

        $store_ids = isset($post['store_ids']) ? $post['store_ids'] : null;
        $store_names = isset($post['store_names']) ? $post['store_names'] : null;
        $name = isset($post['name']) ? $post['name'] : null;
        $phone = isset($post['phone']) ? $post['phone'] : null;
        $hiredate = isset($post['hiredate']) ? $post['hiredate'] : null;

        $this->load->model('storemodel');
        $ids= Storemodel::where('company_id',COMPANY_ID)->get(['id'])->map(function($a){
            return $a->id;
        })->toArray();

        $store_ids_arr = json_decode($store_ids,true);
        if(!empty(array_diff($store_ids_arr, $ids))){
            $this->api_res(1002);
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
        if(!$this->validation())
        {
            $fieldarr   = ['name', 'phone', 'position', 'store_ids', 'store_names', 'hiredate'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return false;
        }
        $this->load->model('positionmodel');
        $id = isset($post['id']) ? $post['id'] : null;
        $position = isset($post['position']) ? $post['position'] : null;
        $employee = Employeemodel::find($id);
        $position_id = $employee->position_id;
        $update = Positionmodel::find($position_id);
        $update->name = $position;
        if(!$update){
            $this->api_res(1009);
            return;
        }
        if($update->save()){
            $this->api_res(0);
        } else{
            $this->api_res(1009);
        }

        $store_ids  = $this->input->post('store_ids',true);
        $store_names  = $this->input->post('store_names',true);
        $name = isset($post['name']) ? $post['name'] : null;
        $phone = isset($post['phone']) ? $post['phone'] : null;
        $hiredate = isset($post['hiredate']) ? $post['hiredate'] : null;

        $this->load->model('storemodel');
        $ids= Storemodel::where('company_id',COMPANY_ID)->get(['id'])->map(function($a){
            return $a->id;
        })->toArray();

        $store_ids_arr = json_decode($store_ids,true);
        if(!empty(array_diff($store_ids_arr, $ids))){
            $this->api_res(1002);
            return;
        }
        $store_id = $store_ids_arr[0];

        $employee->store_ids    = $store_ids;
        $employee->store_names  = $store_names;
        $employee->store_id     = $store_id;
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
     * 验证
     */
    public function validation()
    {
        $this->load->library('form_validation');
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
                'label' => '职位名称',
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

        $this->form_validation->set_rules($config)->set_error_delimiters('','');
        return $this->form_validation->run();
    }

}
