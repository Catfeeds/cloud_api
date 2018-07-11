<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      zj1<401967974@qq.com>
 * Date:        2018/4/10
 * Time:        17:43
 * Describe:
 */

class Funxadmin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('funxadminmodel');
    }

    /**
     * 提交编辑admin
     */
    public function updateAdmin()
    {
            $post   = $this->input->post(NULL,TRUE);
            if(!$this->validateText($post)){

                $fieldarr=['fxid','name','phone','position','hiredate','status'];
                $this->api_res(1002,[
                    'errmsg'=>$this->form_first_error($fieldarr),
                    //'errmsgs'=>validation_errors(),
                    ]);
                return false;
            }

            $admin_user         = Funxadminmodel::where('fxid',$post['fxid'])->first();
            if(!$admin_user){
                $this->api_res(1003);
                return false;
            }
            $admin_user->name   = $post['name'];
            $admin_user->phone  = $post['phone'];
            $admin_user->position   = $post['position'];
            $admin_user->hiredate   = $post['hiredate'];
            $admin_user->status     = $post['status'];
            if($admin_user->save()){
                $this->api_res(0);
            }else{
                $this->api_res(500);
            }
      
    }

    /**
     * 扫描二维码添加员工
     */
    public function qrcodeAddAdmin(){

        $code   = str_replace(' ','',trim(strip_tags($this->input->post('code',true))));;
        $appid  = config_item('wx_web_appid');
        $secret = config_item('wx_web_secret');
        $url    = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
        $user   = $this->httpCurl($url,'get','json');
        if(array_key_exists('errcode',$user))
        {
            //echo $user['errmsg'];
            $this->api_res(10006);
            return false;
        }
      
            $admin_user     = new Funxadminmodel();
            //$access_token   = $user['access_token'];
            //$refresh_token  = $user['refresh_token'];
            $admin_user->openid         = $user['openid'];
            $admin_user->unionid        = $user['unionid'];
            $admin_user->fxid       = ($admin_user->pluck('fxid')->max())+1;
            if($admin_user->save()){
                $this->api_res(0,['fxid'=>$admin_user->fixd]);
            }else{
                $this->api_res(500);
            }
    }

    /**
     * 返回admin列表
     */
    public function listAdmin()
    {

      
            $input          = $this->input->post(NULL, FALSE);
            $page           = isset($input['page'])?$input['page']:1;
            if(!$page){
                $this->api_res(10011);
                return false;
            }
            $offset         = PAGINATE*($page-1);
            $field          = ['fxid','name','position','phone','status','hiredate'];
            $count          = ceil(Funxadminmodel::count()/PAGINATE);
            $admin_users    = Funxadminmodel::offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field)->toArray();
            $this->api_res(0,['count'=>$count,'list'=>$admin_users]);

   

    }

    /**
     * 查找admin信息
     * 传入name？
     */
    public function searchAdmin(){

        $post   = $this->input->post();
        $name   = isset($post['name'])?$post['name']:NULL;
        if(!$name){
            $this->api_res(10012);
            return false;
        }
        $name   = str_replace(' ','',trim(strip_tags($name)));
     
            $search_user = Funxadminmodel::where('name',$name)->first()->toArray();
            if(!$search_user){
                $this->api_res(1003);
                return false;
            }

            $this->api_res(0,[$search_user]);
       

    }

    /**
     * 删除admin(软删除)
     * 传入fxid
     */
    public function deleteAdmin(){

        $post   = $this->input->post(NULL,true);
        $fxid   = isset($post['fxid'])?$post['fxid']:NULL;
        $fxid   = $this->fiterFxid($fxid);
        if(!$fxid){
            $this->api_res(1004);
            return false;
        }
       
            if((Funxadminmodel::where('fxid',$fxid)->delete())==1){
                $this->api_res(0);
            }else{
                $this->api_res(10013);
            }

      

    }


    /**
     * 验证text
     */
    public function validateText($post=[])
    {

        $this->load->library('form_validation');

        $this->form_validation->set_rules($this->validateRules())->set_error_delimiters('','');

        return $this->form_validation->run();
    }

    /**
     * 验证规则 Rules
     */
    public function validateRules()
    {
        $rules=array(
            array(
                'field' => 'fxid',
                'label' => 'id',
                'rules' => 'required|trim|numeric|exact_length[6]',
            ),
            array(
                'field' => 'name',
                'label' => '用户名',
                'rules' => 'required|trim|min_length[2]|max_length[4]',
                'errors'=> array(
                    'required' => '用户名不能为空.',
                )
            ),
            array(
                'field' => 'phone',
                'label' => '手机号',
                'rules' => 'trim|required|numeric|exact_length[11]',
                //'rules' => 'trim|required',
            ),
            array(
                'field' => 'position',
                'label' => '职位',
                'rules' => 'required|trim|in_list[ADMIN,PRODUCT]'
            ),
            array(
                'field' => 'hiredate',
                'label' => '入职日期',
                'rules' => 'required|trim'
            ),
            array(
                'field' => 'status',
                'label' => '状态',
                'rules' => 'required|trim|in_list[ENABLE,DISABLE]'
            )
        );
        return $rules;
    }

}