<?php
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/6/2
 * Time:        10:17
 * Describe:    小业主
 */
class Owner extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('ownermodel');
    }

    /**
     * 显示业主列表
     */
    public function listOwners() {
        $post         = $this->input->post(null, true);
        $field        = ['id', 'name', 'start_date', 'end_date', 'customer_id', 'house_id', 'phone', 'status', 'address'];
        $current_page = isset($post['page']) ? intval($post['page']) : 1; //当前页数
        $pre_page     = isset($post['pre_page']) ? intval($post['pre_page']) : 10; //当前页显示条数
        $search       =  empty($post['search'])? '' : $post['search'];
        $offset       = $pre_page * ($current_page - 1);
        empty($post['store_id']) ? $store_id = [] : $store_id['store_id'] = $post['store_id'];
        empty($post['owner_id']) ? $owner_id = [] : $owner_id['id'] = $post['owner_id'];
        $this->load->model('employeemodel');
        $store_ids = $this->employee_store->store_ids;
        if (!$store_ids) {
            $this->api_res(1007);
            return;
        }
        $this->load->model('ownerhousemodel');
        $house_ids = Ownerhousemodel::whereIn('store_id', $store_ids)->where($store_id)->get(['id'])->map(function ($h) {
            return $h->id;
        });

        if (!$house_ids) {
            $this->api_res(1007);
            return;
        }
        $total       = Ownermodel::whereIn('house_id', $house_ids)->where(function($query)use($search){
            $query->orwhere(function($query)use($search){
                $query->wherehas('house',function($query)use($search){
                    $query->where('number', 'like', "%$search%");
                });});
            $query->orwhere('name', 'like', "%$search%");
        })->count();
        $total_pages = ceil($total / $pre_page); //总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
                'total_pages'              => $total_pages, 'data' => []]);
            return;
        }
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
        $oweners = Ownermodel::with(['customer' => function ($query) {
            $query->select('id', 'avatar');
        }])->with(['house' => function ($query){
            $query->with('store');
        }])->where(function($query)use($search){
            $query->orwhere(function($query)use($search){
            $query->wherehas('house',function($query)use($search){
            $query->where('number', 'like', "%$search%");
        });});
            $query->orwhere('name', 'like', "%$search%");
        })->where($owner_id)
        ->whereIn('house_id', $house_ids)->take($pre_page)
        ->skip($offset)->orderBy('id', 'desc')->get($field)->toArray();
        if (!$oweners) {
            $this->api_res(0);
            return;
        }
        $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
            'total_pages'              => $total_pages, 'data' => $oweners]);
    }


    /**
     * 显示业主详细信息（押金未找到）
     */
    public function showDetail() {
        $post  = $this->input->post(null, true);
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
        $rent_increase_rate        = trim($owner->rent_increase_rate, '[');
        $rent_increase_rate        = trim($rent_increase_rate, ']');
        $owner->rent_increase_rate = explode(',', $rent_increase_rate);

        $no_rent_days          = trim($owner->no_rent_days, '[');
        $no_rent_days          = trim($no_rent_days, ']');
        $owner->no_rent_days   = explode(',', $no_rent_days);
        $owner->agent_info     = json_decode($owner->agent_info);
        $owner->id_card_urls   = json_decode($owner->id_card_urls);
        $owner->bank_card_urls = json_decode($owner->bank_card_urls);
        $this->api_res(0, $owner);
    }

    /**
     * 显示小业主账单
     */
    public function showEarning() {
        $post     = $this->input->post(null, true);
        $this->load->model('ownerearningmodel');
        $this->load->model('ownerdeductionmodel');
        $this->load->model('ownerhousemodel');
        $where    = [];
        $store_id = [];
        $id = [];
        if(!empty($post['id'])){$id['id'] = $post['id'];}
        if(!empty($post['store_id'])){$store_id['store_id'] = $post['store_id'];}
        if(!empty($post['year'])){$where['year'] = $post['year'];}
        if(!empty($post['season'])){$where['season'] = $post['season'];}
        if(!empty($post['status'])){$where['status'] = $post['status'];}
        $current_page = isset($post['page']) ? intval($post['page']) : 1; //当前页数
        $pre_page = PAGINATE;
        $offset       = $pre_page * ($current_page - 1);
        $total = Ownerdeductionmodel::wherehas('ownerEarning', function($query)use($where){
            $query->where($where);
        })->wherehas('house', function($query)use($store_id){
            $query->where($store_id);
        })->count();
        $total_pages = ceil($total / $pre_page); //总页数
        if($total <= 0){
            $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
                'total_pages'              => $total_pages, 'data' => []]);
        }
        $owner_earnings  = Ownerdeductionmodel::with('ownerEarning')->with(['house' => function($query){
            $query->with('owner');
        }])->wherehas('ownerEarning', function($query)use($where){
            $query->where($where);
        })->wherehas('house', function($query)use($store_id){
            $query->where($store_id);
        })->where($id)->take($pre_page)->skip($offset)->get()->map(function($query){
            if(!empty($query->house->owner->bank_card_urls)){
                $query->house->owner->bank_card_urls = $this->fullAliossUrl($query->house->owner->bank_card_urls);
            }
            if(!empty($query->ownerEarning->receipt_path)){
                $query->ownerEarning->receipt_path = $this->fullAliossUrl($query->ownerEarning->receipt_path);
            }
            return $query;
        });
        if (!$owner_earnings ) {
            $this->api_res(1007);
            return;
        }
        $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
            'total_pages'              => $total_pages, 'data' => $owner_earnings]);
    }


    /**
     * 验证
     */
    public function validationCodeAddEmp() {
        $config = array(
            array(
                'field' => 'number',
                'label' => '员工id',
                'rules' => 'trim|required',
            ),
        );

        return $config;
    }
    /*
     * 添加小业主
     * */
    public function addOwner(){
        $this->load->model('residentmodel');
        $this->load->model('ownerhousemodel');
        $post = $this->input->post(null, true);
        $config = $this->validationCodeEdit();
        $field = ['store_id', 'layer', 'area', 'room_count', 'hall_count', 'kitchen_count', 'bathroom_count', 'number', 'unit', 'layer_total',
            'name', 'phone', 'card_number', 'account', 'bank_card_number', 'own_account', 'bank_name', 'minimum_rent', 'start_date', 'end_date',
            'contract_years', 'rent_increase_rate', 'no_rent_days',];
        if (!$this->validationText($config)) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        if (!$this->checkPhoneNumber($post['phone'])) {
            $this->api_res(1002, ['error' => '请检查手机号']);
            return;
        }
        $post['card_type'] = 'P';
        if (!$this->checkIdCardNumber($post['card_type'], $post['card_number'])) {
            $this->api_res(1002, ['error' => '请检查身份证号']);
            return;
        }
        $house = new Ownerhousemodel();
        $owner = new Ownermodel();
        try{
            DB::beginTransaction();
            $house->fill($post);
            $house->save($post);
            $post['house_id'] = $house->id;
            $owner->fill($post);
            $owner->save($post);
            DB::commit();
        }catch (Exception $e) {
            DB::rollback();
        }
        $this->api_res(0);

    }
    /*
     * 编辑小业主
     * */
    public function editOwner(){
        $this->load->model('residentmodel');
        $this->load->model('ownerhousemodel');
        $post = $this->input->post(null, true);
        $config = $this->validationCodeEdit();
        $field = ['store_id', 'layer', 'area', 'room_count', 'hall_count', 'kitchen_count', 'bathroom_count', 'number', 'unit', 'layer_total',
            'name', 'phone', 'card_number', 'account', 'bank_card_number', 'own_account', 'bank_name', 'minimum_rent', 'start_date', 'end_date',
            'contract_years', 'rent_increase_rate', 'no_rent_days',];
        if (!$this->validationText($config)) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        if (!$this->checkPhoneNumber($post['phone'])) {
            $this->api_res(1002, ['error' => '请检查手机号']);
            return;
        }
        $post['card_type'] = 'P';
        if (!$this->checkIdCardNumber($post['card_type'], $post['card_number'])) {
            $this->api_res(1002, ['error' => '请检查身份证号']);
            return;
        }
        $owner     = Ownermodel::findorFail($post['owner_id']);
        if(!$owner){
            $this->api_res(1007);
            return;
        }
        $house_id   = Ownermodel::with('house')->where('id', $post['owner_id'])->select(['id'])->first();
        $post['house_id'] = $house_id->id;
        $house = Ownerhousemodel::findorFail($house_id->id);

        if(!$house){
            $this->api_res(1007);
            return;
        }
        try{
            DB::beginTransaction();
            $owner->fill($post);
            $owner->save($post);
            $house->fill($post);
            $house->save($post);
            DB::commit();
        }catch (Exception $e) {
            DB::rollback();
        }
        $this->api_res(0);
    }


    /**
     * 验证
     */
    public function validationCodeEdit() {
        $config = array(
            array(
                'field' => 'owner_id',
                'label' => '业主id',
                'rules' => 'trim|required|numeric',
            ),
            array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'name',
                'label' => '姓名',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'phone',
                'label' => '电话',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'card_number',
                'label' => '身份证号',
                'rules' => 'required',
            ),
            array(
                'field' => 'account',
                'label' => '持卡人',
                'rules' => 'required',
            ),
            array(
                'field' => 'bank_card_number',
                'label' => '电话',
                'rules' => 'required',
            ),
            array(
                'field' => 'own_account',
                'label' => '是否本人持有',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'bank_name',
                'label' => '开户行',
                'rules' => 'required',
            ),
            array(
                'field' => 'minimum_rent',
                'label' => '保底租金',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'start_date',
                'label' => '交付日期',
                'rules' => 'required',
            ),
            array(
                'field' => 'end_date',
                'label' => '托管日期',
                'rules' => 'required',
            ),
            array(
                'field' => 'contract_years',
                'label' => '年限',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'layer',
                'label' => '楼层',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'area',
                'label' => '面积',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'room_count',
                'label' => '房间数',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'hall_count',
                'label' => '几厅',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'kitchen_count',
                'label' => '几厨',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'bathroom_count',
                'label' => '几卫',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'unit',
                'label' => '单元',
                'rules' => 'required|numeric',
            ),
            array(
                'field' => 'layer_total',
                'label' => '总楼层',
                'rules' => 'required|numeric',
            ),
        );

        return $config;
    }


    /**
     * 检查手机号码的有效性
     */
    public function checkPhoneNumber($phone) {
        $this->load->helper('check');
        if (!isMobile($phone)) {
            log_message('debug', '请检查手机号码');
            return false;
        }
        return true;
    }

    /**
     * 检查证件号码的有效性
     */
    public function checkIdCardNumber($type, $cardNumber) {
        $this->load->helper('check');
        if (Residentmodel::CARD_ZERO == $type AND !isIdNumber($cardNumber)) {
            log_message('debug', '请检查证件号码的有效性');
            return false;
        }

        return true;
    }
}
