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

    /**
     * 显示业主列表
     */
    public function listOwners()
    {
        $post = $this->input->post(null, true);
        $field = ['id', 'name', 'start_date', 'end_date', 'customer_id', 'house_id'];
        $current_page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $pre_page = isset($post['pre_page']) ? intval($post['pre_page']) : 10;//当前页显示条数
        $offset = $pre_page * ($current_page - 1);
        $this->load->model('employeemodel');
        $store_ids = Employeemodel::getMyStoreids();
        if (!$store_ids) {
            $this->api_res(1007);
            return;
        }
        $this->load->model('ownerhousemodel');
        $house_ids = Ownerhousemodel::whereIn('store_id', $store_ids)->get(['id'])->map(function ($h) {
            return $h->id;
        });
        if (!$house_ids) {
            $this->api_res(1007);
            return;
        }
        $total = Ownermodel::whereIn('house_id', $house_ids)->count();
        $total_pages = ceil($total / $pre_page);//总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                'total_pages' => $total_pages, 'data' => []]);
            return;
        }
        $this->load->model('customermodel');
        $oweners = Ownermodel::with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->with(['house' => function ($query) {
            $query->select('id', 'number');
        }])->whereIn('house_id', $house_ids)->take($pre_page)
            ->skip($offset)->orderBy('id', 'desc')->get($field)->toArray();
        if (!$oweners) {
            $this->api_res(1002);
            return;
        }
        $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                                'total_pages' => $total_pages, 'data' => $oweners]);
    }

    /**
     * 按房号查找
     */
    public function searchOwner()
    {
        $post = $this->input->post(null, true);
        $field = ['id', 'name', 'start_date', 'end_date', 'customer_id', 'house_id'];
        $current_page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $pre_page = isset($post['pre_page']) ? intval($post['pre_page']) : 10;//当前页显示条数
        $offset = $pre_page * ($current_page - 1);
        $this->load->model('employeemodel');
        $store_ids = Employeemodel::getMyStoreids();
        if (!$store_ids) {
            $this->api_res(1007);
            return;
        }
        $this->load->model('ownerhousemodel');
        $house_ids = Ownerhousemodel::whereIn('store_id', $store_ids)->get(['id'])->map(function ($h) {
            return $h->id;
        });
        if (!$house_ids) {
            $this->api_res(1007);
            return;
        }
        $total = Ownermodel::whereIn('house_id', $house_ids)->count();
        $total_pages = ceil($total / $pre_page);//总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
                'total_pages' => $total_pages, 'data' => []]);
            return;
        }
        $this->load->model('customermodel');
        $oweners = Ownermodel::with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->with(['house' => function ($query) {
            $query->select('id', 'number');
        }])->whereIn('house_id', $house_ids)->take($pre_page)
            ->skip($offset)->orderBy('id', 'desc')->get($field)->toArray();
        if (!$oweners) {
            $this->api_res(1002);
            return;
        }
        $this->api_res(0, ['total' => $total, 'pre_page' => $pre_page, 'current_page' => $current_page,
            'total_pages' => $total_pages, 'data' => $oweners]);
    }

    /**
     * 显示业主详细信息（押金未找到）
     */
    public function showDetail()
    {
        $post = $this->input->post(null, true);
        $field = ['name', 'phone', 'house_id', 'rent_increase_rate', 'no_rent_days', 'minimum_rent',
            'contract_years', 'card_number', 'agent_info', 'id_card_urls', 'account',
            'bank_card_number', 'bank_name', 'bank_card_urls'];
        $id = isset($post['id']) ? $post['id'] : null;
        if (!$id) {
            $this->api_res(1007, ['error' => '未指定业主id']);
            return;
        }
        $this->load->model('ownerhousemodel');
        $owner = Ownermodel::with(['house' => function ($query) {
            $query->select('id', 'number', 'area', 'room_count', 'hall_count', 'kitchen_count', 'bathroom_count');
        }])->where('id', $id)->first($field);
        if (!$owner) {
            $this->api_res(1007);
            return;
        }
        /*$owner = $this->decodejson($owner);
        if (!$owner) {
            $this->api_res(1007);
            return;
        }*/
        $this->api_res(0, $owner);
    }

    /*
    //将json字段转换成字符串赋值给对象属性
    private function decodejson($owner)
    {
        if (!$owner->agent_info) return false;
        $agent_info = json_decode($owner->agent_info);
        $owner->agent_name = $agent_info->name;
        $owner->agent_phone = $agent_info->phone;
        unset($owner->agent_info);
        if (!$owner->id_card_urls) return false;
        $id_card_urls = json_decode($owner->id_card_urls);
        $owner->id_card_front = $id_card_urls->front;
        $owner->id_card_owner = $id_card_urls->owner;
        $owner->id_card_back = $id_card_urls->back;
        unset($owner->id_card_urls);
        if (!$owner->bank_card_urls) return false;
        $bank_card_urls = json_decode($owner->bank_card_urls);
        $owner->bank_card_front = $bank_card_urls->front;
        $owner->bank_card_back = $bank_card_urls->back;
        unset($owner->bank_card_urls);
        return $owner;
    }*/

    /**
     * 显示小业主账单
     */
    public function showEarning()
    {
        $post = $this->input->post(null, true);
        $owner_id = isset($post['id']) ? $post['id'] : null;
        $field = ['id', 'pay_date', 'start_date', 'end_date', 'amount',
                'earnings', 'deduction', 'sequence_number', 'pay_way'];
        $this->load->model('ownerearningmodel');
        $this->load->model('ownerdeductionmodel');
        $earnings = Ownerearningmodel::with(['deductions' => function ($query) {
                    $query->select('earnings_id', 'money', 'remark');
                    }])->where('owner_id', $owner_id)->get($field);
        if (!$earnings) {
            $this->api_res(1007);
            return;
        }
        $this->api_res(0, $earnings);
    }

    /**
     * 验证
     */
    public function validationCodeAddEmp()
    {
        $config = array(
            array(
                'field' => 'number',
                'label' => '员工id',
                'rules' => 'trim|required',
            ),
        );

        return $config;
    }

}