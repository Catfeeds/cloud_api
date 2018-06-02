<?php
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        10:17
 * Describe:    小业主
 */

class Owner extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ownermodel');
    }

    public function listOwners ()
    {
        $post = $this->input->post(null, true);
        $field = ['id', 'name', 'start_date', 'end_date', 'customer_id', 'house_id'];
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 10;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $this->load->model('employeemodel');
        $store_ids = Employeemodel::getMyStoreids();
        if (!$store_ids) {
            $this->api_res(1007);
        }
        $this->load->model('ownerhousemodel');
        $house_ids = Ownerhousemodel::whereIn('store_id', $store_ids)->get(['id'])->map(function ($h) {
            return $h->id;
        });
        if (!$house_ids) {
            $this->api_res(1007);
        }
        $count_total = Ownermodel::whereIn('house_id', $house_ids)->count();
        $count = ceil($count_total / $page_count);//总页数
        if ($page > $count) {
            return;
        }
        $this->load->model('customermodel');
        $oweners = Ownermodel::with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->with(['house' => function ($query) {
            $query->select('id', 'number');
        }])->whereIn('house_id', $house_ids)->take($page_count)
            ->skip($offset)->orderBy('id', 'desc')->get($field)->toArray();
        if (!$oweners) {
            $this->api_res(1002);
        }
        $this->api_res(0, ['list' => $oweners, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
    }
}