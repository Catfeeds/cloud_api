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

    /**
     * 返回服务类型列表
     */
    public function index()
    {
        $post   = $this->input->post(NULL,TRUE);
        $page   = isset($post['page'])?intval($post['page']):1;
        $where  = [];
        isset($post['id'])?$where['id']=intval($post['id']):$where=[];

        $offset = PAGINATE*($page-1);
        $filed  = ['id','name','feature','description','image_url'];
        $count  = ceil(Servicetypemodel::count()/PAGINATE);

        if($page>$count||$page<1){
            $this->api_res(0,['list'=>[]]);
            return;
        }else{
            $type   = Servicetypemodel::where($where)->take(PAGINATE)->skip($offset)
                                        ->orderBy('id','desc')->get($filed)->toArray();
        }
        foreach ($type as $key=>$value){
            $type[$key]['image_url'] = $this->fullAliossUrl($value['image_url']);
        }
        $this->api_res(0,['count'=>$count,'list'=>$type]);
    }

    /**
     * 添加服务类型
     */
    public function addServicetype()
    {
        $post           = $this->input->post(NULL,true);
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
        $service->image_url     = substr(trim($post['image_url']),strlen(config_item('cdn_path')));

        if($service->save())
        {
            $this->api_res(0);
        }else{
            $this->api_res(10102);
        }
    }

    /**
     * 编辑服务类型
     */
    public function updateServicetype()
    {
            $post   = $this->input->post(NULL,true);
            if(!$this->validation())
            {
                $fieldarr   = ['name','feature','description'];
                $this->api_res(1002,['errmsg'=>$this->form_first_error($fieldarr)]);
                return false;
            }

            $id                     = intval($post['id']);
            $service                = Servicetypemodel::where('id',$id)->first();
            $service->name          = $post['name'];
            $service->feature       = $post['feature'];
            $service->description   = $post['description'];
            $service->image_url     = substr(trim($post['image_url']),strlen(config_item('cdn_path')));

            if($service->save())
            {
                $this->api_res(0);
            }else{
                $this->api_res(10102);
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