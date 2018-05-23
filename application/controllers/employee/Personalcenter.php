<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/22
 * Time:        9:33
 * Describe:    个人中心
 */
class Personalcenter extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('employeemodel');
    }

    /**
     * 个人中心主页
     */
    public function center()
    {
        $post = $this->input->post(null, true);
        if(!$this->validation())
        {
            $fieldarr = ['id'];
            $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
            return;
        }
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
        $filed = ['id', 'name', 'phone'];
        $category = employeemodel::get($filed);
        if (!$category) {
            $this->api_res(1002);
            return;
        }
        $this->api_res(0, ['list' => $category]);
    }

    /**
     * 二维码添加员工
     */
    public function addEmp()
    {
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
                'field' => 'id',
                'label' => '员工id',
                'rules' => 'trim|required',
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