<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/22 0022
 * Time:        10:41
 * Describe:    员工
 */
class Employee extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
    }

    /**
     * 个人中心主页
     */
    public function showCenter()
    {
        $post = $this->input->post(null, true);
        if (isset($post['id']) && !empty($post['id'])) {
            $id = $post['id'];
            $employee = Employeemodel::find($id);
            $this->load->model('storemodel');
            $store = Storemodel::find($employee->store_id);
            $category = [$employee->name, $store->name];
            $this->api_res(0, $category);
        } else {
            $this->api_res(1002);
            return;
        }
    }

    /**
     * 显示员工列表
     */
    public function listEmp()
    {
        $post = $this->input->post(null, true);
        $field = ['id', 'name', 'avatar', 'phone', 'position_id'];
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 10;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $count_total = Employeemodel::where('company_id', COMPANY_ID)
            ->where('status', 'ENABLE')->count();
        $count = ceil($count_total / $page_count);//总页数
        if ($page > $count) {
            return;
        }
        $this->load->model('positionmodel');
        $category = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->where('company_id', COMPANY_ID)
            ->where('status', 'ENABLE')->take($page_count)->skip($offset)
            ->orderBy('id', 'desc')->get($field)->toArray();
        $this->api_res(0, ['list' => $category, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
    }

    /**
     * 二维码添加员工
     */
    public function qrcodeAddEmp()
    {
        $post   = $this->input->post(NULL,true);
        $config = $this->validationCodeAddEmp();
        if(!$this->validationText($config))
        {
            $this->api_res(1002,['error'=>$this->form_first_error(['code', 'name', 'phone'])]);
            return false;
        }
        $code   = $post['code'];
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
        $employee             = new Employeemodel();
        $employee->name       = $post['name'];
        $employee->phone      = $post['phone'];
        $employee->openid     = $user['openid'];
        $employee->unionid    = $user['unionid'];
        $employee->status     = 'NORMAL';
        if($employee->save()){
            $employee->save();
            $this->api_res(0);
        }else{
            $this->api_res(1009);
            return false;
        }
    }

    /**
     * 切换门店
     */
    public function switchStore(){

    }

    /**
     * 获取员工可操作的门店
     */
    public function showStore(){

    }

    /**
     * 二维码添加员工验证
     */
    public function validationCodeAddEmp()
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
                'field' => 'code',
                'label' => '生成码',
                'rules' => 'trim|required',
            ),
        );

        return $config;
    }

}
