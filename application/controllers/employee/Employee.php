<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        14:46
 * Describe:    员工管理
 */
class Employee extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('employeemodel');
    }

    /**
     * 显示登录者负责的门店
     */
    public function showMyStores() {
        $post   = $this->input->post(null, true);
        $city   = isset($post['city']) ? $post['city'] : null;
        $stores = Employeemodel::getMyCitystores($city);
        //$stores = Employeemodel::getMyStores(); //测试用
        if (!$stores) {
            $this->api_res(1009);
            return;
        }
        $this->api_res(0, ['stores' => $stores]);
    }

    /**
     * 显示登录者负责的城市
     */
    public function showMyCities() {
        $cities = Employeemodel::getMyCities();
        $cities->prepend(""); //前端用于占位
        if (!$cities) {
            $this->api_res(1009);
            return;
        }
        $this->api_res(0, ['cities' => $cities]);
    }

    /**
     * 显示登录者公司所负责门店的所有城市
     */
    public function showMyCompanyCities() {
        $cities = Employeemodel::getMyCompanyCities();
        if (!$cities) {
            $this->api_res(1009);
            return;
        }
        $this->api_res(0, ['cities' => $cities]);
    }

    /**
     * 显示员工信息
     */
    public function listEmp() {
        $this->load->model('positionmodel');
        $post   = $this->input->post(null, true);
        $page   = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $field  = ['id', 'name', 'phone', 'position_id', 'store_ids', 'hiredate', 'status'];
        //define('COMPANY_ID', 4); //测试用
        $where = isset($post['store_id']) ? ['store_id' => $post['store_id']] : [];
        if (isset($post['city']) && !empty($post['city'])) {
            $this->load->model('storemodel');
            $store_ids = Storemodel::where('company_id', COMPANY_ID)
                ->where('city', $post['city'])->get(['id'])->map(function ($s) {
                return $s->id;
            });
            $count = ceil((Employeemodel::whereIn('store_ids', $store_ids)->where($where)->count()) / PAGINATE);
            if ($page > $count) {
                $this->api_res(0, ['count' => $count, 'list' => []]);
                return;
            }
            $employees = Employeemodel::with(['position' => function ($query) {
                $query->select('id', 'name');
            }])->whereIn('store_ids', $store_ids)->where($where)
                ->offset($offset)->limit(PAGINATE)->orderBy('status', 'asc')
                ->orderBy('hiredate', 'asc')->get($field);
        } else {
            $count = ceil((Employeemodel::where('company_id', COMPANY_ID)->count()) / PAGINATE);
            if ($page > $count) {
                $this->api_res(0, ['count' => $count, 'list' => []]);
                return;
            }
            $employees = Employeemodel::with(['position' => function ($query) {
                $query->select('id', 'name');
            }])->where('company_id', COMPANY_ID)->offset($offset)
                ->limit(PAGINATE)->orderBy('status', 'asc')
                ->orderBy('hiredate', 'asc')->get($field);
            $this->load->model('storemodel');
        }
        $employees = $this->getStoreNames($employees);
        $this->api_res(0, ['count' => $count, 'list' => $employees]);
    }

    /**
     * 按名称模糊查找
     */
    public function searchEmp() {
        $field = ['id', 'name', 'phone', 'position_id', 'store_ids', 'hiredate', 'status'];
        $this->load->model('positionmodel');
        $post   = $this->input->post(null, true);
        $name   = !empty($post['name']) ? $post['name'] : null;
        $page   = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);

        $this->load->model('storemodel');
        $store_ids = Storemodel::where('company_id', COMPANY_ID)->get(['id'])->map(function ($s) {
            return $s->id;
        });
        $count = ceil((Employeemodel::whereIn('store_ids', $store_ids)
                ->where('name', 'like', "%$name%")->count()) / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $employees = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->whereIn('store_ids', $store_ids)->where('name', 'like', "%$name%")
            ->offset($offset)->limit(PAGINATE)->orderBy('status', 'asc')
            ->orderBy('hiredate', 'asc')->get($field);
        $employees = $this->getStoreNames($employees);
        $this->api_res(0, ['count' => $count, 'list' => $employees]);
    }

    /**
     * 获取城市门店信息
     */
    public function getStore() {
        $filed = ['id', 'name', 'city'];
        $this->load->model('storemodel');
        $category = Storemodel::get($filed)->groupBy('city');

        if (!$category) {
            $this->api_res(1009);
            return;
        }
        return $category;
    }

    /**
     * 显示城市门店
     */
    public function showStore() {
        $category = $this->getStore();
        $post     = $this->input->post(null, true);
        if (isset($post['id']) && !empty($post['id'])) {
            $id      = trim($post['id']);
            $emloyee = Employeemodel::find($id);
            if (!$emloyee) {
                $this->api_res(1009);
                return false;
            }
            $store_ids = $emloyee->store_ids;
            $store_ids = explode(',', $store_ids);
            $this->load->model('storemodel');
            $store_names = Storemodel::whereIn('id', $store_ids)->get(['name'])->map(function ($s) {
                return $s->name;
            });
            if (!$store_names) {
                $store_name_tmp = null;
            } else {
                $name_tmp_string = '';
                foreach ($store_names as $store_name) {
                    $name_tmp_string .= $store_name . ',';
                }
                $name_tmp_string = rtrim($name_tmp_string, ',');
                $store_name_tmp  = $name_tmp_string;
            }

            $this->load->model('positionmodel');
            $position = Positionmodel::find($emloyee->position_id);
            if (!$position) {
                $position_name_tmp = null;
            } else {
                $position_name_tmp = $position->name;
            }

            $category = [
                'name'        => $emloyee->name,
                'phone'       => $emloyee->phone,
                'position'    => $position_name_tmp,
                'status'      => $emloyee->status,
                'store_ids'   => $emloyee->store_ids,
                'store_names' => $store_name_tmp,
            ];
        }
        $this->api_res(0, $category);
    }

    /**
     * 提交员工信息
     */
    public function submitEmp() {
        $post   = $this->input->post(null, true);
        $config = $this->validationSubmitEmp();
        if (!$this->validationText($config)) {
            $fieldarr = ['name', 'phone', 'position', 'store_ids', 'hiredate'];
            $this->api_res(1002, ['error' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $this->load->helper('check');
        if (!isMobile($post['phone'])) {
            $this->api_res(1016);
            return false;
        }
        $name        = $post['name'];
        $isNameEqual = Employeemodel::where('company_id', COMPANY_ID)->where('name', $name)->first();
        if ($isNameEqual) {
            $this->api_res(1013);
            return false;
        }
        $phone        = $post['phone'];
        $isPhoneEqual = Employeemodel::where('company_id', COMPANY_ID)->where('phone', $phone)->first();
        if ($isPhoneEqual) {
            $this->api_res(1015);
            return false;
        }
        $position = $post['position'];
        $this->load->model('positionmodel');
        $position = Positionmodel::where('company_id', COMPANY_ID)
            ->where('name', $position)->first(['id']);
        if (!$position) {
            $this->api_res(1009);
            return false;
        }
        $position_id   = $position->id;
        $store_ids     = $post['store_ids'];
        $hiredate      = $post['hiredate'];
        $store_ids_arr = explode(',', $store_ids);
        $store_id      = $store_ids_arr[0];

        $employee              = new Employeemodel();
        $employee->company_id  = COMPANY_ID;
        $employee->store_ids   = $store_ids;
        $employee->store_id    = $store_id;
        $employee->position_id = $position_id;
        $employee->name        = $name;
        $employee->phone       = $phone;
        $employee->hiredate    = $hiredate;
        $employee->status      = 'ENABLE';

        if ($employee->save()) {
            $employee_b       = Employeemodel::find($employee->id);
            $employee_b->bxid = $employee->id;
            if ($employee_b->save()) {
                $this->api_res(0, ['id' => $employee_b->id]);
            } else {
                $this->api_res(1009);
            }
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 编辑员工信息
     */
    public function updateEmp() {
        $post   = $this->input->post(null, true);
        $config = $this->validationSubmitEmp();
        array_pull($config, '2');
        array_pull($config, '3');
        array_pull($config, '4');
        $status_val = ['field' => 'status', 'label' => '员工状态', 'rules' => 'trim|required|in_list[ENABLE,DISABLE]'];
        $config     = array_add($config, '3', $status_val);
        if (!$this->validationText($config)) {
            $fieldarr = ['name', 'phone', 'status'];
            $this->api_res(1002, ['error' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $this->load->helper('check');
        if (!isMobile($post['phone'])) {
            $this->api_res(1016);
            return false;
        }

        $id = isset($post['id']) ? $post['id'] : null;
        if (!$id) {
            $this->api_res(1002, ['error' => '未指定员工id']);
            return false;
        }
        $name        = $post['name'];
        $isNameEqual = Employeemodel::where('company_id', COMPANY_ID)->where('name', $name)->first();
        if ($isNameEqual && ($isNameEqual->id != $id)) {
            $this->api_res(1013);
            return false;
        }
        $phone        = $post['phone'];
        $isPhoneEqual = Employeemodel::where('company_id', COMPANY_ID)->where('phone', $phone)->first();
        if ($isPhoneEqual && ($isPhoneEqual->id != $id)) {
            $this->api_res(1015);
            return false;
        }
        $position = isset($post['position']) ? $post['position'] : null;
        if (!$position) {
            $this->api_res(1002, ['error' => '请输入职位名称']);
            return false;
        }
        $this->load->model('positionmodel');
        $position = Positionmodel::where('company_id', COMPANY_ID)
            ->where('name', $position)->first(['id']);
        if (!$position) {
            $this->api_res(1009);
            return false;
        }
        $store_ids = isset($post['store_ids']) ? $post['store_ids'] : null;
        if (!$store_ids) {
            $this->api_res(1002, ['error' => '请输入负责门店']);
            return false;
        }
        $store_ids_arr = explode(',', $store_ids);
        $store_id      = $store_ids_arr[0];
        $position_id   = $position->id;
        $status        = $post['status'];

        $employee = Employeemodel::find($id);
        if (!$employee) {
            $this->api_res(1007);
            return;
        }
        $employee->position_id = $position_id;
        $employee->store_ids   = $store_ids;
        $employee->store_id    = $store_id;
        $employee->name        = $name;
        $employee->phone       = $phone;
        $employee->status      = $status;

        if ($employee->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }

    }

    /**
     * 每个5秒检测指定员工是否绑定$openid与$unionid
     */
    public function isBindWxid() {
        $post    = $this->input->post(null, true);
        $id      = isset($post['id']) ? $post['id'] : null;
        $emloyee = Employeemodel::find($id);
        if ($emloyee) {
            if (($emloyee->openid == NULL) && ($emloyee->unionid == NULL)) {
                $this->api_res(0, ['NO']);
                return;
            } else {
                $this->api_res(0, ['YES']);
            }
        } else {
            $this->api_res(1007);
        }
    }

    /**
     * 二维码添加员工
     */
    public function bindwechat() {
        $post   = $this->input->post(null, true);
        $config = $this->validationCodeAddEmp();
        if (!$this->validationText($config)) {
            $this->api_res(1002, ['error' => $this->form_first_error(['id', 'code'])]);
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
        $openid       = $user['openid'];
        $unionid      = $user['unionid'];
        $access_token = $user['access_token'];

        $info_url  = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $user_info = $this->httpCurl($info_url, 'get', 'json');
        if (array_key_exists('errcode', $user_info)) {
            log_message('error', '请求info:' . $user_info['errmsg']);
            $this->api_res(1006);
            return false;
        }

        $employee           = Employeemodel::find($post['id']);
        $employee->openid   = $openid;
        $employee->unionid  = $unionid;
        $employee->nickname = $user_info['nickname'];
        $employee->gender   = $user_info['sex'];
        $employee->province = $user_info['province'];
        $employee->city     = $user_info['city'];
        $employee->country  = $user_info['country'];
        $employee->avatar   = $user_info['headimgurl'];
        if ($employee->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 删除员工信息（将员工状态设置为离职）
     */
    public function delEmp() {
        $post = $this->input->post(null, true);
        if (isset($post['id']) && !empty($post['id'])) {
            $id = $post['id'];
            $id = explode(',', $id);
            //var_dump($id);
            $position = Employeemodel::whereIn('id', $id)->update(['status' => 'DISABLE']);
            if ($position) {
                $this->api_res(0, ['message' => '员工已删除，请及时转移相关业务']);
                return false;
            } else {
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
    public function validationCodeAddEmp() {
        $config = array(
            array(
                'field' => 'id',
                'label' => '员工id',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'code',
                'label' => '二维码',
                'rules' => 'trim|required',
            ),
        );

        return $config;
    }

    /**
     * 添加员工验证
     */
    public function validationSubmitEmp() {
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
                'field' => 'hiredate',
                'label' => '入职时间',
                'rules' => 'trim|required',
            ),
        );

        return $config;
    }

    /**
     * 获取store_names
     */
    public function getStoreNames($employees) {
        foreach ($employees as $employee) {
            $store_ids   = $employee->store_ids;
            $store_ids   = explode(',', $store_ids);
            $store_names = Storemodel::whereIn('id', $store_ids)->get(['name'])->map(function ($s) {
                return $s->name;
            });
            $name_tmp_string = '';
            foreach ($store_names as $store_name) {
                $name_tmp_string .= $store_name . ',';
            }
            $name_tmp_string       = rtrim($name_tmp_string, ',');
            $employee->store_names = $name_tmp_string;
        }
        return $employees;
    }

}
