<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/23
 * Time:        9:52
 * Describe:    [boss端]服务管理--服务类型
 */

class Servicetype extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('servicetypemodel');
    }

    public function index()
    {
        $type = Servicetypemodel::all();

        $this->api_res(0,$type);
    }

    /**
     * 添加服务类型
     */
    public function addServicetype()
    {
        try
        {
            $post       = $this->input->post(NULL,true);
            if(!$this->validation())
            {
                $fieldarr   = ['name','feature','description'];
                $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
                return false;
            }
            $service                = new Servicetypemodel();
            $service->name          = $post['name'];
            $service->feature       = $post['feature'];
            $service->description   = $post['description'];
            $service->image_url     = trim($post['image_url']);

            if($service->save())
            {
                $this->api_res(0);
                return true;
            }else{
                $this->api_res(10102);
                return false;
            }
        }catch (Exception $e)
        {
            $this->api_res(500);
            return false;
        }
    }

    /**
     * 上传图片
     */
    public function imageUpload()
    {
        $config     = [
            'allowed_types' => 'gif|jpg|png',
            'max_size'      => '5000',
        ];
        $this->load->library('alioss', $config);
        if(!$this->alioss->do_upload('image')){
            $this->api_res(10106);
            return false;
        }

        $data = $this->alioss->data();
        $image_path = $data['oss_path'];
        $this->api_res(0,['image_url'=>$image_path]);
        return true;
    }

    /**
     * 编辑服务类型
     */
    public function updateServicetype()
    {
        try{
            $post = $this->input->post(NULL,true);

            if(!$this->validation())
            {
                $fieldarr   = ['name','feature','description'];
                $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
                return false;
            }

            $id                     = trim($post['id']);
            $service                = Servicetypemodel::where('id',$id)->first();
            $service->name          = $post['name'];
            $service->feature       = $post['feature'];
            $service->description   = $post['description'];
            $service->image_url     = trim($post['image_url']);

            if($service->save())
            {
                $this->api_res(0);
                return true;
            }else{
                $this->api_res(10102);
                return false;
            }
        }catch (Exception $e)
        {
            $this->api_res(500);
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
                'label' => '类型名称',
                'rules' => 'trim|required|max_length[32]',
            ),
            array(
                'field' => 'feature',
                'label' => '服务特点',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'description',
                'label' => '服务类型详细描述',
                'rules' => 'trim|required',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('','');

        return $this->form_validation->run();
    }

}