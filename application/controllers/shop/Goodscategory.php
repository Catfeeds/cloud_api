<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/26
 * Time:        19:08
 * Describe:    商品管理-商品管理
 */

class Goodscategory extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('goodscategorymodel');
    }

    /**
     * 商品分类列表
     */
    public function goodsCategory() {
        $post = $this->input->post(NULL, true);
        //page整数  by weijinlong
        $page   = isset($post['page']) ? $post['page'] : 1;
        $offset = PAGINATE * ($page - 1);
        $count  = ceil(Goodscategorymodel::count() / PAGINATE);
        $filed  = ['id', 'name', 'is_show', 'sort'];

        if ($page > $count) {
            $this->api_res(0, ['list' => [], 'count' => $count]);
        } else {
            $goodscate = Goodscategorymodel::take(PAGINATE)->skip($offset)->orderBy('sort', 'desc')->get($filed);
            $this->api_res(0, ['list' => $goodscate, 'count' => $count]);
        }
    }

    /**
     * 添加商品分类
     */
    public function addCategory() {
        $post = $this->input->post(NULL, true);
        if (!$this->validation()) {
            $fieldarr = ['name', 'is_show', 'sort'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $category          = new Goodscategorymodel();
        $category->name    = trim($post['name']);
        $category->is_show = trim($post['is_show']);
        $category->sort    = trim($post['sort']);
        if ($category->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(666);
        }
    }

    /**
     * 删除商品分类
     */
    public function deleteCategory() {
        $post     = $this->input->post(NULL, TRUE);
        $id       = isset($post['id']) ? $post['id'] : NULL;
        $category = Goodscategorymodel::destroy($id);

        if ($category) {
            $this->api_res(0);
        } else {
            $this->api_res(666);
        }
    }

    /**
     * 编辑商品分类
     */
    public function updateCategory() {
        $post = $this->input->post(NULL, true);
        if (!$this->validation()) {
            $fieldarr = ['name', 'is_show', 'sort'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $id                = trim($post['id']);
        $category          = Goodscategorymodel::where('id', $id)->first();
        $category->name    = trim($post['name']);
        $category->is_show = trim($post['is_show']);
        $category->sort    = trim($post['sort']);
        if ($category->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(666);
        }
    }

    /**
     * 验证
     */
    public function validation() {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'name',
                'label' => '类型名称',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'is_show',
                'label' => '服务特点',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'sort',
                'label' => '服务类型详细描述',
                'rules' => 'trim|required',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('', '');

        return $this->form_validation->run();
    }

}
