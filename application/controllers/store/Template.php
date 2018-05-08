<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/28 0028
 * Time:        16:28
 * Describe:
 */
class Template extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contracttemplatemodel');
    }

    /**
     * 模板列表
     */
    public function listTemplate(){
        $post   = $this->input->post(null,true);
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = PAGINATE*($page-1);
        $field  = ['id','name'];
        $count  = ceil(Contracttemplatemodel::count()/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $stores = Contracttemplatemodel::offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field)->toArray();
        $this->api_res(0,['count'=>$count,'list'=>$stores]);
    }

    /**
     * 添加模板
     */
    public function addTemplate(){
        $post   = $this->input->post(NULL,true);
        //找到员工所在的门店id
        $name   = isset($post['name'])?$post['name']:null;
        $file_url   = isset($post['file_url'])?$post['file_url']:null;
        if(empty($name) || empty($file_url)){
            $this->api_res(1002);
            return;
        }
        $template   = new Contracttemplatemodel();
        $template->name = $name;
        $template->url  = $this->splitAliossUrl($file_url);
        if($template->save()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 删除模板
     */
    public function deleteTemplate(){
        $post   = $this->input->post('template_id',true);
        if(Contracttemplatemodel::find($post)->delete()){
            $this->api_res(0);
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 查找模板（按名称 模糊查询）
     */
    public function searchTemplate(){
        $name   = $this->input->post('name',true);
        $page   = intval($this->input->post('page',true)?$this->input->post('page',true):1);
        $field  = ['id','name'];
        $count  = ceil(Contracttemplatemodel::where('name','like',"%$name%")->count()/PAGINATE);
        if($page>$count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $stores  = Contracttemplatemodel::where('name','like',"%$name%")->limit(PAGINATE)->orderBy('id','desc')->get($field);
        $this->api_res(0,['count'=>$count,'list'=>$stores]);
    }
}