<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/28 0028
 * Time:        16:28
 * Describe:
 */
class Template extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('contracttemplatemodel');
    }

    /**
     * 模板列表
     */
    public function listTemplate() {
        $post   = $this->input->post(null, true);
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');
        $page   = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $field  = ['id', 'name', 'url', 'rent_type','store_id','room_type_id'];
        $count  = ceil(Contracttemplatemodel::count() / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $templates = Contracttemplatemodel::offset($offset)->limit(PAGINATE)->orderBy('id', 'desc')
            ->with('store')->with('room')->get($field)
            ->map(function ($result) {
                $result->url = $this->fullAliossUrl($result->url);
                return $result;
            })
            ->toArray();
        $this->api_res(0, ['count' => $count, 'list' => $templates]);
    }

    /**
     * 添加模板
     */
    public function addTemplate() {
        $this->load->library('fadada');
        $field = ['name', 'type'];
        if (!$this->validationText($this->validateAdd())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $post         = $this->input->post(NULL, true);
        $name         = isset($post['name']) ? $post['name'] : null;
        $rent_type    = isset($post['type']) ? $post['type'] : null;
        $room_type_id = empty($post['room_type_id']) ? null : $post['room_type_id'];
        $file_url     = $post['file_url'];
        $url          = $this->splitAliossUrl($file_url);
        $store_id     = $this->employee->store_id;
        $company_id   = $this->company_id;
        if (Contracttemplatemodel::where(['name' => $name, 'store_id' => $store_id])->exists()) {
            $this->api_res(1008);
            return;
        }
        $templateId = date('YmdHis') . mt_rand(10, 99);
        $res        = $this->fadada->uploadTemplate($file_url, $templateId);
        if (!$res) {
            throw new Exception($this->fadada->showError());
        }
        $template                    = new Contracttemplatemodel();
        $template->name              = $name;
        $template->rent_type         = $rent_type;
        $template->url               = $url;
        $template->contract_tpl_path = $url;
        $template->company_id        = $company_id;
        $template->store_id          = $store_id;
        $template->fdd_tpl_id        = $templateId;
        if ($template->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 删除模板
     */
    public function deleteTemplate() {
        $post = $this->input->post('template_id', true);
        if (Contracttemplatemodel::find($post)->delete()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 批量删除模板
     */
    public function destroyTemplate() {
        $id = $this->input->post('template_id', true);
        if (!is_array($id)) {
            $this->api_res(1005);
            return;
        }
        if (Contracttemplatemodel::destroy($id)) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 查找模板（按名称 模糊查询）
     */
    public function searchTemplate() {
        $name  = $this->input->post('name', true);
        $page  = intval($this->input->post('page', true) ? $this->input->post('page', true) : 1);
        $field = ['id', 'name'];
        $count = ceil(Contracttemplatemodel::where('name', 'like', "%$name%")->count() / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $stores = Contracttemplatemodel::where('name', 'like', "%$name%")->limit(PAGINATE)->orderBy('id', 'desc')->get($field);
        $this->api_res(0, ['count' => $count, 'list' => $stores]);
    }

    /**
     * 查看模板信息
     */
    public function getTemplate() {
        $template_id = $this->input->post('template_id', true);
        if (!$template_id) {
            $this->api_res(1005);
            return;
        }
        $template        = Contracttemplatemodel::select(['name', 'url', 'rent_type'])->find($template_id);
        $template['url'] = $this->fullAliossUrl($template['url']);
        if (!$template) {
            $this->api_res(1007);
            return;
        } else {
            $this->api_res(0, ['template' => $template]);
        }
    }

    /**
     * 编辑模板信息
     */
    public function updateTemplate() {
        $this->load->library('fadada');
        $template_id = $this->input->post('template_id', true);
        $field       = ['name', 'type'];
        if (!$template_id) {
            $this->api_res(1002);
            return;
        }
        if (!$this->validationText($this->validateAdd())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $post      = $this->input->post(NULL, true);
        $name      = isset($post['name']) ? $post['name'] : null;
        $rent_type = isset($post['type']) ? $post['type'] : null;
        $file_url  = $post['file_url'];
        $url       = $this->splitAliossUrl($file_url);

        $templateId = date('YmdHis') . mt_rand(10, 99);
        $res        = $this->fadada->uploadTemplate($file_url, $templateId);
        if (!$res) {
            throw new Exception($this->fadada->showError());
        }
        $template                    = Contracttemplatemodel::findOrFail($template_id);
        $template->name              = $name;
        $template->rent_type         = $rent_type;
        $template->url               = $url;
        $template->contract_tpl_path = $url;
        $template->fdd_tpl_id        = $templateId;
        if ($template->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 获取门店下的合同模板
     */
    public function showTemplate() {
        $where     = [];
        $store_id  = $this->input->post('store_id', true);
        $rent_type = $this->input->post('rent_type', true);
        if (!$rent_type) {
            $this->api_res(1005);
            return;
        }
        isset($store_id) ? $where['store_id'] = $store_id : null;
        $where['rent_type']                   = $rent_type;
        $template                             = Contracttemplatemodel::where($where)->get(['id', 'name']);
        $this->api_res(0, ['template' => $template]);
    }

    public function validateAdd() {
        return array(
            array(
                'field' => 'name',
                'label' => '模板名称',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'type',
                'label' => '模板类型',
                'rules' => 'required|trim|in_list[LONG,SHORT,RESERVE]',
            ),
        );
    }
}
