<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/4/27
 * Time:        10:17
 * Describe:    商城管理-商品管理
 */
class Goods extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('goodsmodel');
    }

    /**
     * 返回商品列表
     */
    public function listgoods() {
        $post   = $this->input->post(NULL, TRUE);
        $page   = isset($post['page']) ? intval($post['page']) : 1;
        $name   = isset($post['name']) ? $post['name'] : NULL;
        $offset = PAGINATE * ($page - 1);

        $filed = ['id', 'name', 'category_id', 'market_price', 'shop_price', 'quantity', 'sale_num', 'on_sale',
            'description', 'detail', 'original_link', 'goods_thumb', 'goods_carousel'];
        $where = array();
        if (!empty($post['category_id'])) {$where['category_id'] = $post['category_id'];}
        if (!empty($post['on_sale'])) {$where['on_sale'] = $post['on_sale'];}
        isset($post['id']) ? $where['id'] = intval($post['id']) : NULL;
        if (empty($where)) {
            $count = ceil(Goodsmodel::where('name', 'like', '%' . "$name" . '%')->count() / PAGINATE);
            if ($page > $count || $page < 1) {
                $this->api_res(0, ['list' => []]);
                return;
            } else {
                $goods = Goodsmodel::where('name', 'like', '%' . "$name" . '%')
                    ->take(PAGINATE)->skip($offset)->orderBy('id', 'desc')
                    ->get($filed)->toArray();
            }
        } else {
            $count = ceil(Goodsmodel::where('name', 'like', '%' . "$name" . '%')->where($where)->count() / PAGINATE);
            if ($page > $count || $page < 1) {
                $this->api_res(0, ['list' => []]);
                return;
            } else {
                $goods = Goodsmodel::where('name', 'like', '%' . "$name" . '%')->where($where)
                    ->take(PAGINATE)->skip($offset)->orderBy('id', 'desc')
                    ->get($filed)->toArray();
            }
        }
        foreach ($goods as $key => $good) {
            $goods[$key]['goods_thumb']    = $this->fullAliossUrl($good['goods_thumb']);
            $goods[$key]['goods_carousel'] = $this->fullAliossUrl(json_decode($good['goods_carousel'], true), true);
        }
        $this->api_res(0, ['list' => $goods, 'count' => $count]);
    }

    /**
     *  商品分类列表
     */
    public function getCategory() {
        $this->load->model('goodscategorymodel');
        $filed    = ['id', 'name'];
        $category = Goodscategorymodel::get($filed);
        $this->api_res(0, $category);
    }

    /**
     * 添加商品
     */
    public function addGoods() {
        $post = $this->input->post(NULL, true);
        if (!$this->validation()) {
            $fieldarr = ['name', 'category_id', 'market_price', 'shop_price', 'quantity', 'sale_num',
                'description', 'detail', 'original_link'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return false;
        }
        if (!empty($post['goods_thumb'])) {
            $goods_thumb = $this->splitAliossUrl($post['goods_thumb']);
        } else {
            $this->api_res(1002);
            return false;
        }

        if (!empty($post['goods_carousel'])) {
            $goods_carousel = json_encode($this->splitAliossUrl($post['goods_carousel'], true));
        } else {
            $this->api_res(1002);
            return false;
        }
        $goods               = new Goodsmodel();
        $goods->name         = trim($post['name']); //商品名称
        $goods->category_id  = trim($post['category_id']); //商品分类ID
        $goods->market_price = trim($post['market_price']); //市场价格
        $goods->shop_price   = trim($post['shop_price']); //商品价格
        $goods->quantity     = trim($post['quantity']); //商品数量
        $goods->sale_num     = trim($post['sale_num']); //已经卖出数量
        $goods->description  = trim($post['description']); //描述
        $goods->detail       = $this->input->post('detail');
//        $goods->detail          = trim($post['detail']);     //商品详情
        $goods->original_link  = trim($post['original_link']); //商品原始链接
        $goods->on_sale        = trim($post['on_sale']); //是否上架
        $goods->goods_thumb    = $goods_thumb; //商品缩略图
        $goods->goods_carousel = $goods_carousel; //商品轮播图
        if ($goods->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(666);
        }
    }

    /**
     * 编辑商品
     */
    public function updateGoods() {
        $post = $this->input->post(NULL, true);
        if (!$this->validation()) {
            $fieldarr = ['name', 'category_id', 'market_price', 'shop_price', 'quantity', 'sale_num',
                'description', 'detail', 'original_link'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return false;
        }
        if (!empty($post['goods_thumb'])) {
            $goods_thumb = $this->splitAliossUrl($post['goods_thumb']);
        } else {
            $this->api_res(1002);
            return false;
        }

        if (!empty($post['goods_carousel'])) {
            $goods_carousel = json_encode($this->splitAliossUrl($post['goods_carousel'], true));
        } else {
            $this->api_res(1002);
            return false;
        }
        $id                  = trim($post['id']);
        $goods               = Goodsmodel::where('id', $id)->first();
        $goods->name         = trim($post['name']); //商品名称
        $goods->category_id  = trim($post['category_id']); //商品分类ID
        $goods->market_price = trim($post['market_price']); //市场价格
        $goods->shop_price   = trim($post['shop_price']); //商品价格
        $goods->quantity     = trim($post['quantity']); //商品数量
        $goods->sale_num     = trim($post['sale_num']); //已经卖出数量
        $goods->description  = trim($post['description']); //描述
        $goods->detail       = $this->input->post('detail');
//        $goods->detail          = trim($post['detail']);     //商品详情
        $goods->original_link  = trim($post['original_link']); //商品原始链接
        $goods->on_sale        = trim($post['on_sale']); //是否上架
        $goods->goods_thumb    = $goods_thumb; //商品缩略图
        $goods->goods_carousel = $goods_carousel; //商品轮播图

        if ($goods->save()) {
            $this->api_res(0);
        } else {
            $this->api_res(666);
        }
    }

    /**
     * 批量上架/下架
     */
    public function updateOnsale() {
        $post   = $this->input->post(NULL, true);
        $id     = isset($post['id']) ? explode(',', trim($post['id'])) : null;
        $status = isset($post['on_sale']) ? trim($post['on_sale']) : null;
        if (empty($status)) {
            $this->api_res(1002);
            return false;
        } else {
            if (Goodsmodel::whereIn('id', $id)->update(['on_sale' => $status])) {
                $this->api_res(0);
            } else {
                $this->api_res(666);
            }
        }
    }

    /**
     * 删除商品
     */
    public function deleteGoods() {
        $post    = $this->input->post(NULL, TRUE);
        $post    = $post['id'];
        $id      = isset($post) ? explode(',', $post) : NULL;
        $company = Goodsmodel::destroy($id);

        if ($company) {
            $this->api_res(0);
        } else {
            $this->api_res(666);
            return false;
        }
    }

    /**
     *  表单验证规则
     */
    private function validation() {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'name',
                'label' => '商品名称',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'category_id',
                'label' => '商品类型',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'market_price',
                'label' => '市场价格',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'shop_price',
                'label' => '售价',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'quantity',
                'label' => '数量',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'sale_num',
                'label' => '已售数量',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'description',
                'label' => '描述',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'detail',
                'label' => '详情',
                'rules' => 'trim|required',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }
}
