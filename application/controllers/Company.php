<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/12
 * Time:        13:12
 * Describe:    梵响客户信息操作
 */

class Company extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('companymodel');
    }

    /**
     * 返回客户列表
     */
    public function listCompany()
    {
        try{
            $input          = $this->input->post(NULL,TRUE);
            $page           = isset($input['page'])?$input['page']:1;
            $name           = isset($input['name'])?$input['name']:NULL;
            $offset         = PAGINATE*($page-1);
            $field          = ['id','name','address','contact_user','contact_phone','license','status'];
            $cdn_path       = config_item('cdn_path');
            if (!empty($name)){
                $name       = $this->fiterStr($name);
                $count      = ceil(Companymodel::where('name','like','%'."$name".'%')->count()/PAGINATE);
                $company    = Companymodel::where('name','like','%'."$name".'%')->take(PAGINATE)
                                ->skip($offset)->orderBy('id','desc')->get($field)->toArray();
                $this->api_res(0,['count'=>$count,'list'=>$company,'path'=>$cdn_path]);
                return true;
            }
            $count          = ceil(Companymodel::count()/PAGINATE);
            $company        = Companymodel::take(PAGINATE)->skip($offset)
                            ->orderBy('id','desc')->get($field)->toArray();
            $this->api_res(0,['count'=>$count,'list'=>$company]);

        }catch (Exception $e){
            $this->api_res(500);
            return false;
        }
    }

    /**
     * 添加客户信息
     */
    public function addCompany()
    {
        try{
            $post   = $this->input->post(NULL,TRUE);
            if(!$this->validateText($post)){
                $fieldarr=['name','address','contact_user','contact_phone'];
                $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
                return false;
            }

            $company                = new Companymodel();
            $company->name          = $post['name'];
            $company->address       = $post['address'];
            $company->contact_user  = $post['contact_user'];
            $company->contact_phone = $post['contact_phone'];
            $company->license       = $post['license_path'];

            if($company->save()){

                $this->api_res(0,['id'=>$company->id]);
                return true;
            }else{
                $this->api_res(10102);
                return false;
            }
        }catch (Exception $e){
            $this->api_res(500);
            return false;
        }
    }

    /**
     * 上传营业执照
     */
    public function licenseUpload()
    {

        $config     = [
                'allowed_types' => 'gif|jpg|png',
                'max_size'      => '5000',
                ];
        $this->load->library('alioss', $config);
        if(!$this->alioss->do_upload('license')){
            $this->api_res(10106);
            return false;
        }

        $data = $this->alioss->data();
        $license_path = $data['oss_path'];
        $this->api_res(0,['license_path'=>$license_path]);
        return true;
    }

    /**
     * 扫描二维码添加客户
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
            $this->api_res(10006);
            return false;
        }
        try{
            $company             = Companymodel::where('id',$id)->first();
            $company->openid     = $user['openid'];
            $company->unionid    = $user['unionid'];
            if($company->save()){
                $company->status = 'NORMAL';
                $company->save();
                $this->api_res(0);
            }else{
                $this->api_res(10105);
            }
        }catch (Exception $e){
            $this->api_res(500);
        }
    }

    /**
     * 修改客户信息
     */
    public function updateCompany()
    {
        try{
            $post   = $this->input->post(NULL,TRUE);
            if(!$this->validateText($post)){
                $fieldarr=['name','address','contact_user','contact_phone'];
                $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
                return false;
            }

            $id                     = $post['id'];
            $company                = Companymodel::where('id',$id)->first();
            $company->name          = $post['name'];
            $company->address       = $post['address'];
            $company->contact_user  = $post['contact_user'];
            $company->contact_phone = $post['contact_phone'];
            //$company->status        = $post['status'];

            if($company->save()){
                $this->api_res(0);
                return true;
            }else{
                $this->api_res(10103);
                return false;
            }
        }catch (Exception $e){
            $this->api_res(500);
            return false;
        }
    }

    /**
     * 查看营业执照
     */
    public function queryLicense()
    {
        try{
            $post       = $this->input->post(NULL,TRUE);
            $company_id         = isset($post['company_id'])?$post['company_id']:NULL;
            if(!$company_id){
                $this->api_res(10101);
            }
            $license_pre= config_item('cdn_path');

            $license_path   = Companymodel::find($company_id,['license'])->toArray();
            $license_path   = $license_path['license'];

            if($license_path){
                $this->api_res(0,['path'=>$license_pre.$license_path]);
                return true;
            }else{
                $this->api_res(10101);
                return false;
            }
        }catch (Exception $e){
            $this->api_res(500);
            return false;
        }
    }

    /**
     * 删除客户信息
     * 软删除
     */
    public function deleteCompany()
    {
        try{
            $post       = $this->input->post(NULL,TRUE);
            $post       = $post['id'];
            $id         = isset($post)?explode(',',$post):NULL;
            $company    = Companymodel::destroy($id);

            if($company){
                $this->api_res(0);
                return true;
            }else{
                $this->api_res(10104);
                return false;
            }
        }catch (Exception $e){
            $this->api_res(500);
            return false;
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
                'field' => 'name',
                'label' => '公司名称',
                'rules' => 'required|trim',
                'errors'=> array(
                    'required' => '用户名不能为空.',
                )
            ),
            array(
                'field' => 'address',
                'label' => '公司地址',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'contact_user',
                'label' => '联系人',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'contact_phone',
                'label' => '联系人电话',
                'rules' => 'trim|required|numeric|exact_length[11]',
            ),
        );
        return $rules;
    }

}