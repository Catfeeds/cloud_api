<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use EasyWeChat\Foundation\Application;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/31
 * Time:        11:18
 * Describe:    优惠活动
 */
/**************************************************************/
/*         处理各种优惠活动的控制器, 目前还有许多要修改的地方         */
/**************************************************************/
date_default_timezone_set('PRC');

class Activity extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('activitymodel');
    }

    /*
     * 获取奖项
     * */
    public function activityIni()
    {
        $this->load->model('coupontypemodel');
        $this->load->model('storemodel');
        $data['coupontype'] = Coupontypemodel::where('type', 'CASH')->get(['id', 'name']);
        $data['store'] = Storemodel::get(['id', 'name', 'city']);
        if (!$data) {
            $this->api_res(500);
        } else {
            $this->api_res(0, $data);
        }
    }

    /**
     * 活动列表
     */
    public function listActivity()
    {
        $post = $this->input->post(NULL, true);
        $this->load->model('storeactivitymodel');
        $this->load->model('coupontypemodel');
        $this->load->model('drawmodel');
        $this->load->model('employeemodel');
        $this->load->model('storemodel');
        $this->load->model('couponmodel');
        $this->load->model('activityprizemodel');
        $store = Storemodel::get(['id'])->toArray();
        $result = array_column($store, 'id');
        $str_store = implode(',', $result);
        $store_id = empty($post['store_id']) ? $str_store : trim($post['store_id']);
        $ac_name = isset($post['activity_name']) ? trim($post['activity_name']) : '';
        $page = isset($post['page']) ? intval($post['page']) : 1;
        $store_id = explode(',', $store_id);
        $offset = ($page - 1) * PAGINATE;
        $filed = ['id', 'name', 'start_time', 'end_time', 'description', 'coupon_info', 'type',
            'limit', 'employee_id', 'qrcode_url', 'activity_type', 'prize_id', 'share_img',
            'share_title', 'share_des','rule','back_url'];

        $where_id = empty($post['id']) ? [] : ['id' => $post['id']];
        $where_type = empty($post['type']) ? [] : ['activity_type' => $post['type']];
        $activity = Activitymodel::where('activity_type', '!=', 'NORMAL')
            ->where('activity_type', '!=', '')
            ->where('name', 'like', "%$ac_name%")
            ->where($where_type)
            ->where($where_id)
            ->orderBy('start_time')
            ->where(function ($query) use ($store_id) {
                $query->orWhereHas('store', function ($query) use ($store_id) {
                    $query->whereIN('store_id', $store_id);
                });
            })
            ->get($filed)
            ->map(function($activity_each){
                if($activity_each->back_url){
                    $activity_each->back_url    = $this->fullAliossUrl($activity_each->back_url);
                    return $activity_each;
                }else{
                    return $activity_each;
                }
            })
            ->groupBy('type')
            ->toArray();
        $count = Activitymodel::where('activity_type', '!=', 'NORMAL')
            ->where('activity_type', '!=', '')
            ->where('name', 'like', "%$ac_name%")
            ->where($where_type)
            ->where($where_id)
            ->orderBy('start_time')
            ->where(function ($query) use ($store_id) {
                $query->orWhereHas('store', function ($query) use ($store_id) {
                    $query->whereIN('store_id', $store_id);
                });
            })
            ->count();
        $count = ceil($count / PAGINATE);
        if (!$activity) {
            $this->api_res(0, ['count' => 0, 'list' => []]);
            return false;
        }
        $lo_data = [];
        $no_date = [];
        if (isset($activity['LOWER'])) {
            $lo_data = $this->ac_up($activity['LOWER']);
        }
        if (isset($activity['NORMAL'])) {
            $no_date = $this->ac_up($activity['NORMAL']);
        }
        $data_limit = array_merge($no_date, $lo_data);
        $data = array_slice($data_limit, $offset, PAGINATE);
        $this->api_res(0, ['count' => $count, 'list' => $data]);
    }


    /*
     * 数据处理
     * */
    private function ac_up($activity)
    {
        foreach ($activity as $key => $coupon) {
            $prize = Activityprizemodel::where('id', $coupon['prize_id'])->select(['prize', 'count', 'grant','limit'])->first();
            $p = unserialize($prize->prize);
            $count = unserialize($prize->count);
            $grant = unserialize($prize->grant);
            log_message('debug','PRIZELIMIT'.$prize->limit);
            if(!empty($prize->limit)){
                $prize_limit = unserialize($prize->limit);
            }else{
                $prize_limit    = '';
            }
            $coupon_one = Coupontypemodel::where('id', $p['one'])->select(['name'])->first();
            $coupon_two = Coupontypemodel::where('id', $p['two'])->select(['name'])->first();
            $coupon_three = Coupontypemodel::where('id', $p['three'])->select(['name'])->first();
            $str = $coupon_one->name.'/'.$coupon_two->name.'/'.$coupon_three->name;

            if ($coupon['activity_type'] == 'OLDBELTNEW' || $coupon['activity_type'] == 'CHECKIN') {
                $participate = Couponmodel::where('activity_id', $coupon['id'])->groupBy('resident_id')->count();
                $lucky_draw = Couponmodel::where('activity_id', $coupon['id'])->count();
            } ELSE {
                $participate = Drawmodel::where('activity_id', $coupon['id'])->count();
                $lucky_draw = Drawmodel::where(['activity_id' => $coupon['id'], 'is_draw' => '1'])->count();
            }
            $employee_name = Employeemodel::where('id', $coupon['employee_id'])->first(['name']);
            $store_id = Storeactivitymodel::where('activity_id', $coupon['id'])->with('store')->get(['store_id'])->toarray();
            $store_str = '';
            foreach ($store_id as $value) {
                $store_str .= $value['store']['name'] . '/';
            }

            $coupon_count = Couponmodel::where('activity_id', $coupon['id'])->count();
            $data[$key]['id'] = $coupon['id'];
            $data[$key]['description'] = $coupon['description'];
            $data[$key]['user'] = $employee_name->name;
            $data[$key]['name'] = $coupon['name'];
            $data[$key]['start_time'] = date("Y-m-d H:i", strtotime($coupon['start_time']));
            $data[$key]['end_time'] = date("Y-m-d H:i", strtotime($coupon['end_time']));
            $data[$key]['prize'] = $str;
            $data[$key]['count'] = $count;
            $data[$key]['grant'] = $grant;
            $data[$key]['prize_limit'] = $prize_limit;
            $limit = unserialize($coupon['limit']);
            $data[$key]['customer'] = $limit['com'];
            $data[$key]['rule'] = $coupon['rule'];
            $data[$key]['back_url'] = $coupon['back_url'];
            $data[$key]['coupon_count'] = $coupon_count;
            $data[$key]['type'] = $coupon['activity_type'];
            $data[$key]['qrcode_url'] = $coupon['qrcode_url'];
            $data[$key]['limit'] = $limit['limit'];
            $data[$key]['participate'] = $participate;
            $data[$key]['lucky_draw'] = $lucky_draw;
            $data[$key]['share_img'] = $this->fullAliossUrl($coupon['share_img']);
            $data[$key]['share_des'] = $coupon['share_des'];
            $data[$key]['share_title'] = $coupon['share_title'];
            if ($coupon['type'] == 'LOWER') {
                $data[$key]['status'] = 'Lowerframe';
            } elseif (time() < strtotime($coupon['start_time'])) {
                $data[$key]['status'] = 'Notbeginning';
            } elseif (time() > strtotime($coupon['end_time'])) {
                Activitymodel::where('id', $coupon['id'])->update(['type' => 'LOWER']);
                $data[$key]['status'] = 'End';
            } elseif (time() < strtotime($coupon['end_time']) && time() > strtotime($coupon['start_time'])) {
                $data[$key]['status'] = 'Normal';
            }
            $data[$key]['store_name'] = $store_str;
        }
        return $data;
    }

    /*
     * 根据城市获取门店
     * */
    public function getStore()
    {
        $post = $this->input->post(null, true);
        $this->load->model('storemodel');
        $city_name = empty($post['city']) ? '' : $post['city'];
        $city = explode(',', $city_name);
        $store = Storemodel::wherein('city', $city)->get(['id', 'name'])->toArray();
        $this->api_res(0, $store);
    }

    /*
     * 新增活动
     * 大转盘的活动
     * */
    public function addTrntable()
    {
        $post = $this->input->post(null, true);
        $this->load->model('storeactivitymodel');
        $this->load->model('activityprizemodel');
        $config = $this->validation();
        array_pull($config, '0');
        if (!$this->validationText($config)) {
            $fieldarr = ['name', 'start_time', 'end_time', 'description', 'limit', 'one_prize', 'slogan',
                'two_prize', 'three_prize', 'one_count', 'two_count', 'three_count', 'store_id', 'images',
                'share_des', 'share_title'];
            $this->api_res(1002, ['error' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $arr = [$post['one_prize'], $post['two_prize'], $post['three_prize']];
        if (count($arr) != count(array_unique($arr))) {
            $this->api_res(11101);
            return false;
        }
        $prize['prize'] = serialize(['one' => $post['one_prize'], 'two' => $post['two_prize'], 'three' => $post['three_prize']]);
        $prize['count'] = serialize(['one' => $post['one_count'], 'two' => $post['two_count'], 'three' => $post['three_count']]);
        $prize_id = Activityprizemodel::insertGetId($prize);
        $limit = [
            'com' => $post['customer'],
            'limit' => $post['limit'],
        ];
        $activity['coupon_info'] = $post['slogan'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['name'] = $post['name'];
        $activity['description'] = $post['description'];
        $activity['limit'] = serialize($limit);
        $activity['employee_id'] = $this->current_id;
        $activity['prize_id'] = $prize_id;
        $activity['share_img'] = $this->splitAliossUrl($post['images']);
        $activity['share_des'] = $post['share_des'];
        $activity['share_title'] = $post['share_title'];
        $activity['activity_type'] = 'TRNTABLE';
        $insertId = Activitymodel::insertGetId($activity);
        $store_id = explode(',', $post['store_id']);
        foreach ($store_id as $value) {
            $data = [
                'store_id' => $value,
                'activity_id' => $insertId,
            ];
            $store = Storeactivitymodel::insert($data);
        }
        if ($insertId && $store) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /*
     * 刮刮乐活动addScratch
     * */
    public function addScratch()
    {
        $post = $this->input->post(null, true);
        $this->load->model('storeactivitymodel');
        $this->load->model('activityprizemodel');
        $config = $this->validation();
        array_pull($config, '0');
        array_pull($config, '4');
        if (!$this->validationText($config)) {
            $fieldarr = ['name', 'start_time', 'end_time', 'description', 'limit', 'one_prize',
                'two_prize', 'three_prize', 'one_count', 'two_count', 'three_count', 'store_id',
                'images', 'share_des', 'share_title',
            ];
            $this->api_res(1002, ['error' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $arr = [$post['one_prize'], $post['two_prize'], $post['three_prize']];
        if (count($arr) != count(array_unique($arr))) {
            $this->api_res(11101);
            return false;
        }
        $prize['prize'] = serialize(['one' => $post['one_prize'], 'two' => $post['two_prize'], 'three' => $post['three_prize']]);
        $prize['count'] = serialize(['one' => $post['one_count'], 'two' => $post['two_count'], 'three' => $post['three_count']]);
        $prize_id = Activityprizemodel::insertGetId($prize);
        $limit = [
            'com' => $post['customer'],
            'limit' => $post['limit'],
        ];
        $activity['name'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['description'] = $post['description'];
        $activity['limit'] = serialize($limit);
        $activity['employee_id'] = $this->current_id;
        $activity['prize_id'] = $prize_id;
        $activity['share_img'] = $this->splitAliossUrl($post['images']);
        $activity['share_des'] = $post['share_des'];
        $activity['share_title'] = $post['share_title'];
        $activity['activity_type'] = 'SCRATCH';
        $insertId = Activitymodel::insertGetId($activity);
        $store_id = explode(',', $post['store_id']);
        foreach ($store_id as $value) {
            $data = ['store_id' => $value, 'activity_id' => $insertId];
            $store = Storeactivitymodel::insert($data);
        }
        if ($insertId && $store) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /*
     * 入住送礼
     * */
    public function checkIn()
    {
        $post = $this->input->post(null, true);
        $this->load->model('storeactivitymodel');
        $this->load->model('activityprizemodel');
        if($post['one_count'] < $post['one_grant'] || $post['one_count'] < $post['one_grant'] || $post['one_count'] < $post['one_grant']){
            $this->api_res(11110);
            return false;
        }
        $store_ids = Storeactivitymodel::where(function ($query) {
            $query->orWhereHas('activity', function ($query) {
                $query->where('activity_type', 'CHECKIN')->where('type', '!=', 'LOWER')->where('end_time', '>=', Carbon::now());
            });
        })->get(['store_id'])->toArray();
        $store_id = explode(',', $post['store_id']);
        if (0 != count($store_ids)) {
            $stores = [];
            foreach ($store_ids as $value) {
                $stores[] = $value['store_id'];
            }
            if (array_intersect($stores, $store_id)) {
                $this->api_res(11103);
                return false;
            }
        }
        $config = $this->ValidationCheckin();
        if (!$this->validationText($config)) {
            $fieldarr = ['name', 'start_time', 'end_time', 'store_id', 'one_prize', 'two_prize', 'three_prize',
                'one_count', 'two_count', 'three_count', 'one_grant', 'two_grant', 'three_grant',
            ];
            $this->api_res(1002, ['error' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $arr = [$post['one_prize'], $post['two_prize'], $post['three_prize']];
        if (count($arr) != count(array_unique($arr))) {
            $this->api_res(11101);
            return false;
        }

        $prize['prize'] = serialize(['one' => $post['one_prize'], 'two' => $post['two_prize'], 'three' => $post['three_prize']]);
        $prize['count'] = serialize(['one' => $post['one_count'], 'two' => $post['two_count'], 'three' => $post['three_count']]);
        $prize['grant'] = serialize(['one' => $post['one_grant'], 'two' => $post['two_grant'], 'three' => $post['three_grant']]);
        $prize_id = Activityprizemodel::insertGetId($prize);
        $activity['description'] = $post['description'];
        $activity['name'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['employee_id'] = $this->current_id;
        $activity['prize_id'] = $prize_id;
        $activity['activity_type'] = 'CHECKIN';
        $insertId = Activitymodel::insertGetId($activity);

        foreach ($store_id as $value) {
            $data = ['store_id' => $value, 'activity_id' => $insertId];
            $store = Storeactivitymodel::insert($data);
        }
        if ($insertId && $store) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /*
     * 老带新活动
     * */
    public function oldBeltnew()
    {
        $post = $this->input->post(null, true);
        $this->load->model('storeactivitymodel');
        $this->load->model('activityprizemodel');
        if($post['one_count'] < $post['one_grant'] || $post['one_count'] < $post['one_grant'] || $post['one_count'] < $post['one_grant']){
            $this->api_res(11110);
            return false;
        }
        $store_ids = Storeactivitymodel::where(function ($query) {
            $query->orWhereHas('activity', function ($query) {
                $query->where('activity_type', 'OLDBELTNEW')->where('type', '!=', 'LOWER')->where('end_time', '>=', Carbon::now());
            });
        })->get(['store_id'])->toArray();
        $store_id = explode(',', $post['store_id']);
        if (0 != count($store_ids)) {
            $stores = [];
            foreach ($store_ids as $value) {
                $stores[] = $value['store_id'];
            }
            if (array_intersect($stores, $store_id)) {
                $this->api_res(11103);
                return false;
            }
        }
        $config = $this->ValidationCheckin();
        if (!$this->validationText($config)) {
            $fieldarr = ['name', 'start_time', 'end_time', 'store_id', 'old_prize', 'old_count', 'old_grant'
            ];
            $this->api_res(1002, ['error' => $this->form_first_error($fieldarr)]);
            return false;
        }
        $prize['prize'] = serialize(['one' => $post['one_prize'], 'two' => $post['two_prize'], 'three' => $post['three_prize']]);
        $prize['count'] = serialize(['one' => $post['one_count'], 'two' => $post['two_count'], 'three' => $post['three_count']]);
        $prize['grant'] = serialize(['one' => $post['one_grant'], 'two' => $post['two_grant'], 'three' => $post['three_grant']]);
        $prize_id = Activityprizemodel::insertGetId($prize);
        $activity['name'] = $post['name'];
        $activity['description'] = $post['description'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['employee_id'] = $this->current_id;
        $activity['prize_id'] = $prize_id;
        $activity['activity_type'] = 'OLDBELTNEW';
        $insertId = Activitymodel::insertGetId($activity);
        foreach ($store_id as $value) {
            $data = ['store_id' => $value, 'activity_id' => $insertId];
            $store = Storeactivitymodel::insert($data);
        }
        if ($insertId && $store) {
            $this->api_res(0);
        } else {
            $this->api_res(1009);
        }
    }

    /*
     * 下架活动
     * */
    public function LowerActivity()
    {
        $post = $this->input->post(null, true);
        $activity_id = isset($post['id']) ? $post['id'] : null;
        if (!$activity_id) {
            $this->api_res(1002);
            return false;
        }
        $activity = Activitymodel::find($activity_id);
        $activity->type = 'LOWER';
        if ($activity->save()) {
            $data = ['status' => 'Lowerframe'];
            $this->api_res(0, $data);
        } else {
            $this->api_res(500);
        }
    }

    /*
     * 生成活动二维码
     * */
    public function activityCode()
    {
        $post = $this->input->post(null, true);
        $id = isset($post['id']) ? $post['id'] : null;
        if (!$id) {
            $this->api_res(1002);
            return false;
        }
        $this->load->helper('common');
        $activity = Activitymodel::find($id);
        if (!$activity) {
            $this->api_res(1007);
            return false;
        }
        if ($activity->type == 'LOWER') {
            $this->api_res(11102);
            return false;
        }
        try {
            if ($activity->activity_type == 'TRNTABLE') {
                //转盘
                $url = config_item('web_domain') . '/#/turntable/' . $activity->id;
                $this->api_res(0, ['url' => $url]);
            } elseif ($activity->activity_type == 'SCRATCH') {
                //刮刮乐
                $url = config_item('web_domain') . '/#/scraping/' . $activity->id;
                $this->api_res(0, ['url' => $url]);
            } else {
                return false;
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
    }

    public function validation()
    {
        return array(
            array(
                'field' => 'type',
                'label' => '活动类型',
                'rules' => 'trim|required|in_list[ATTRACT,NORMAL,DISCOUNT]',
            ),
            array(
                'field' => 'name',
                'label' => '活动名称',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'end_time',
                'label' => '结束时间',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'slogan',
                'label' => '口号',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'description',
                'label' => '描述',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'limit',
                'label' => '抽奖限制',
                'rules' => 'trim|required',
            ),
            array(

                'field' => 'store_id',
                'label' => '门店',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'one_prize',
                'label' => '奖品1',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'two_prize',
                'label' => '奖品2',
                'rules' => 'trim|required',

            ),
            array(
                'field' => 'three_prize',
                'label' => '奖品3',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'one_count',
                'label' => '奖品1num',
                'rules' => 'trim|required|greater_than[0]',
                'errors' => array(
                    'greater_than'  => '奖品数量不能小于等于0',
                )
            ),
            array(
                'field' => 'two_count',
                'label' => '奖品2num',
                'rules' => 'trim|required|greater_than[0]',
                'errors' => array(
                    'greater_than'  => '奖品数量不能小于等于0',
                )

            ),
            array(
                'field' => 'three_count',
                'label' => '奖品3num',
                'rules' => 'trim|required|greater_than[0]',
                'errors' => array(
                    'greater_than'  => '奖品数量不能小于等于0',
                )
            ),
            array(
                'field' => 'images',
                'label' => '分享图片',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'share_des',
                'label' => '分享描述',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'share_title',
                'label' => '分享标题',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'customer',
                'label' => '用户限制',
                'rules' => 'trim|required',
            ),

        );
    }

    public function ValidationCheckin()
    {
        return array(
            array(
                'field' => 'name',
                'label' => '活动名称',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'one_prize',
                'label' => '三月奖品',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'two_prize',
                'label' => '半年奖品',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'three_prize',
                'label' => '一年奖品',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'one_count',
                'label' => '三月奖品数量',
                'rules' => 'trim|required|max_length[255]|greater_than[0]',
                'errors' => array(
                    'greater_than'  => '奖品数量不能小于等于0',
                )
            ),
            array(
                'field' => 'two_count',
                'label' => '半年奖品数量',
                'rules' => 'trim|required|greater_than[0]',
                'errors' => array(
                    'greater_than'  => '奖品数量不能小于等于0',
                )
            ),
            array(
                'field' => 'three_count',
                'label' => '一年奖品数量',
                'rules' => 'trim|required|greater_than[0]',
                'errors' => array(
                    'greater_than'  => '奖品数量不能小于等于0',
                )
            ),
            array(
                'field' => 'one_grant',
                'label' => '三月奖品发放',
                'rules' => 'trim|required|max_length[255]|greater_than[0]',
            ),
            array(
                'field' => 'two_grant',
                'label' => '半年奖品发放',
                'rules' => 'trim|required|greater_than[0]',
            ),
            array(
                'field' => 'three_grant',
                'label' => '一年奖品发放',
                'rules' => 'trim|required|greater_than[0]',
            ),
            array(

                'field' => 'store_id',
                'label' => '门店',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'start_time',
                'label' => '开始时间',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'end_time',
                'label' => '结束时间',
                'rules' => 'trim|required',
            ),
        );
    }

    /**
     * 添加吸粉活动
     */
    public function addAttractActivity()
    {
        $input = $this->input->post(null, true);
        $field  = ['name','image','start_time','end_time','rule','description','prizes[]'];
        if (!$this->validationText($this->validateAttractConfig())) {
            $this->api_res(0, ['error' => $this->form_first_error($field)]);
            return;
        }


        $this->load->model('activitymodel');
        $this->load->model('attractprizemodel');

        //判断有没有存在的吸粉活动，如果有则不能新增
        if (Activitymodel::where([
            'type'=>Activitymodel::STATE_NORMAL,
            'status'=>Activitymodel::STATE_NORMAL,
            'activity_type'=> Activitymodel::TYPE_ATTRACT,
            ])->exists()) {
            $this->api_res(11109);
            return;
        }

        $image_url  = $input['image'];
//        $image_path = $this->downloadAttractImage($image_url);
        //把图片下载到本地获取$image_path

        try{
            DB::BeginTransaction();
            $activity   = new Activitymodel();
            $activity->name = $input['name'];
            $activity->type = Activitymodel::STATE_NORMAL;
            $activity->status   = Activitymodel::STATE_NORMAL;
            $activity->rule = $input['rule'];
            $activity->description  = $input['description'];
            $activity->start_time   = $input['start_time'];
            $activity->end_time     = $input['end_time'];
            $activity->activity_type    = Activitymodel::TYPE_ATTRACT;
            $activity->employee_id  = $this->employee->id;
            $activity->data = [];
            $activity->back_url = $this->splitAliossUrl($image_url);
//            $activity->back_path= $image_path;
            $activity->save();
            $limit_array = [];
            $prizes = [];
            foreach ($input['prizes'] as $prize) {
                $limit_array[]  = $prize['limit'];
                $prizes[]   = [
                    'activity_id'   => $activity->id,
                    'count'         => $prize['count'],
                    'sent'          => 0,
                    'limit'         => $prize['limit'],
                    'single'        => $prize['single'],
                    'coupontype_id' => $prize['coupontype_id'],
                ];
            }
            Attractprizemodel::insert($prizes);
            $activity->data = array_merge($activity->data,['limit'=>$limit_array]);
            $qrcode_url = $this->generateAttractQrcode($activity->id);
            $activity->qrcode_url   = $qrcode_url;
            $activity->save();
            /**
             * 处理兼容问题
             */
            $this->handleAttractCompatibility($activity,$input['prizes']);


            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0,$qrcode_url);
    }

    /**
     * 处理吸粉活动兼容性问题
     */
    private function handleAttractCompatibility($activity,$prizes)
    {
        //@1:处理参与门店
        $this->load->model('storemodel');
        $this->load->model('storeactivitymodel');
        $store_ids  = Storemodel::pluck('id')->toArray();
        $store_activity_arr = [];
        foreach ($store_ids as $store_id) {
            $store_activity_arr[]   = [
                'store_id'      => $store_id,
                'activity_id'   => $activity->id,
            ];
        }
        Storeactivitymodel::insert($store_activity_arr);
        //@2:处理奖品
        $this->load->model('activityprizemodel');
        $prize['prize'] = serialize(['one' => $prizes[2]['coupontype_id'], 'two' => $prizes[1]['coupontype_id'], 'three' => $prizes[0]['coupontype_id']]);
        $prize['count'] = serialize(['one' => $prizes[2]['count'], 'two' => $prizes[1]['count'], 'three' => $prizes[0]['count']]);
        $prize['grant'] = serialize(['one' => $prizes[2]['single'], 'two' => $prizes[1]['single'], 'three' => $prizes[0]['single']]);
        $prize['limit'] = serialize(['one' => $prizes[2]['limit'], 'two' => $prizes[1]['limit'], 'three' => $prizes[0]['limit']]);
        $prize_id   = Activityprizemodel::insertGetId($prize);
        $activity->prize_id = $prize_id;
        $activity->save();
    }

    /**
     * 生成吸粉活动二维码
     */
    private function generateAttractQrcode($activity_id)
    {
        $sceneId   = sprintf('1%03d%06d',$activity_id,0);
        $this->load->helper('common');
        $app    = new Application(getWechatCustomerConfig());
        $qrcode = $app->qrcode;
        $result = $qrcode->forever($sceneId);
        $qrcodeUrl  = $qrcode->url($result->ticket);
        return $qrcodeUrl;
    }


    /**
     * 新增吸粉活动的规则
     */
    public function validateAttractConfig()
    {
        return array(
            array(
                'field' => 'name',
                'label' => '活动名称',
                'rules' => 'required|trim',
                'error' => array(
                    'required'  => '请填写%s',
                )
            ),
            array(
                'field' => 'image',
                'label' => '图片地址',
                'rules' => 'required|trim',
                'error' => array(
                    'required'  => '请上传%s',
                )
            ),
            array(
                'field' => 'start_time',
                'label' => '活动开始时间',
                'rules' => 'required|trim',
                'error' => array(
                    'required'  => '请选择%s',
                )
            ),
            array(
                'field' => 'end_time',
                'label' => '活动结束时间',
                'rules' => 'required|trim',
                'error' => array(
                    'required'  => '请选择%s',
                )
            ),
            array(
                'field' => 'rule',
                'label' => '活动规则',
                'rules' => 'required|trim',
                'error' => array(
                    'required'  => '请填写%s',
                )
            ),
            array(
                'field' => 'description',
                'label' => '活动描述',
                'rules' => 'required|trim',
                'error' => array(
                    'required'  => '请填写%s',
                )
            ),
            array(
                'field' => 'prizes[]',
                'label' => '奖品配置',
                'rules' => 'required|trim',
                'error' => array(
                    'required'  => '请设置%s',
                )
            ),
        );
    }


    /**
     * @param $url
     * @return string
     * @throws Exception
     * 把吸粉活动对应的图片下载到本地
     */
    private function downloadAttractImage($url)
    {
        $path   = APPPATH.'/cache/attract/'.date('Y-m-d',time()).'/';
        $pathinfo   = pathinfo($url);
        $filename   = $pathinfo['filename'].rand(10,99).'.'.$pathinfo['extension'];
        $fullpath   = $path.$filename;
        if (!is_dir(APPPATH.'/cache/attract/')) {
            if (!mkdir(APPPATH.'/cache/attract/',0777)) {
                throw new Exception('无法创建目录, 请稍后重试');
            }
        }
        if (!is_dir($path)) {
            if (!mkdir($path, 0777)) {
                throw new Exception('无法创建目录, 请稍后重试');
            }
        }
        $file   = file_get_contents($url);
        file_put_contents($fullpath,$file,0777);
        return $fullpath;
    }

    /*
 * 活动统计
 * */
    public function activityCount(){
        $post = $this->input->post(null, true);
        $this->load->model('activityprizemodel');
        $this->load->model('storeactivitymodel');
        $this->load->model('drawmodel');
        $this->load->model('residentmodel');
        $this->load->model('couponmodel');
        $this->load->model('activityvisitmodel');
        $this->load->model('activitysharemodel');
        if(empty($post['id'])){
            $this->api_res(1002);
            return;
        }
        $filed = ['id', 'name', 'prize_id', 'start_time', 'end_time', 'status'];
        $activity_prize  = Activitymodel::with('prize')->where('id', $post['id'])->select($filed)->first()->toArray();
        $store_id = [];
        if(!empty($post['store_id'])){$store_id['store_id'] = $post['store_id'];}
        $id = $post['id'];
        $prize = [];
        $prize['id'] = unserialize($activity_prize['prize']['prize']);
        $prize['surplus'] = unserialize($activity_prize['prize']['count']);
        $prize['receive'] = ['one' => 0, 'two' => 0, 'three' => 0];
        $prize['used'] = ['one' => 0, 'two' => 0, 'three' => 0];
        $draw = [];
            Couponmodel::where(function($query) use($store_id){
                if(!empty($store_id)){
                    $query->wherehas('resident', function($query) use($store_id){
                        $query->where($store_id);
                    });}
            })->where('activity_id', $id)->get()->map(function ($query) use (&$prize) {
                $query = $query->toArray();
                foreach ($prize['id'] as $key => $value) {
                    if ($value == $query['coupon_type_id']) {
                        $prize['receive'][$key]++;
                    }
                }
            });

            Couponmodel::where(function($query) use($store_id){
                if(!empty($store_id)){
                    $query->wherehas('resident', function($query) use($store_id){
                        $query->where($store_id);
                    });}
            })->where(['activity_id' => $post['id'], 'status' => Couponmodel::STATUS_USED])->get()->map(function ($query) use (&$prize) {
                $query = $query->toArray();
                foreach ($prize['id'] as $key => $value) {
                    if ($value == $query['coupon_type_id']) {
                        $prize['used'][$key]++;
                    }
                }
            });
            $draw['lottery'] = 0;            $draw['luck_draw'] = 0;
            $draw['lottery_today'] = 0;      $draw['luck_draw_today'] = 0;
            $draw['lottery_yesterday'] = 0;  $draw['luck_draw_yesterday'] = 0;
            Drawmodel::where(function($query) use($store_id){
                if(!empty($store_id)){
                    $query->wherehas('resident', function($query) use($store_id){
                        $query->where($store_id);
                    });}
            })->where(['activity_id' => $post['id']])->get()->map(function($query)use(&$draw){
                if($query->is_draw == 1){
                    $draw['lottery'] ++;
                }
                $draw['luck_draw'] ++;
                if($query->draw_time > Carbon::today()){
                    $query->is_draw == 1?$draw['lottery_today'] ++:$draw['luck_draw_today'] ++;
                }
                if($query->draw_time > Carbon::yesterday() && $query->draw_time < Carbon::today()){
                    $query->is_draw == 1?$draw['lottery_yesterday'] ++:$draw['luck_draw_yesterday'] ++;
                }
            });
            $draw['visit'] = 0;           $draw['share'] = 0;
            $draw['visit_today'] = 0;     $draw['share_today'] = 0;
            $draw['visit_yesterday'] = 0; $draw['share_yesterday'] = 0;
            Activityvisitmodel::where(function($query) use($store_id){
                if(!empty($store_id)){
                $query->wherehas('resident', function($query) use($store_id){
                $query->where($store_id);
                });}
            })->where(['activity_id' => $id])->get()->map(function($query)use (&$draw){
                if(!empty($query)) {
                    $draw['visit']++;
                }
                if($query->created_at > Carbon::today()){
                    $draw['visit_today'] ++;
                }
                if($query->created_at > Carbon::yesterday() && $query->created_at < Carbon::today()){
                    $draw['visit_yesterday'] ++;
                }
            });

            Activitysharemodel::where(function($query) use($store_id){
                if(!empty($store_id)){
                    $query->wherehas('resident', function($query) use($store_id){
                        $query->where($store_id);
                    });}
            })->where(['activity_id' => $id])->get()->map(function($query) use(&$draw){
                if(!empty($query)) {
                    $draw['share']++;
                }
                if($query->created_at > Carbon::today()){
                    $draw['share_today'] ++;
                }
                if($query->created_at > Carbon::yesterday() && $query->created_at < Carbon::today()){
                    $draw['share_yesterday'] ++;
                }
            });


        $this->api_res(0, ['activity' => $activity_prize, 'prize' => $prize, 'draw' => $draw]);
    }


    private function ac_list( $store, $de, $id, $date){
        $this->load->model('drawmodel');
        $this->load->model('storemodel');
        $this->load->model('residentmodel');
        $this->load->model('activityvisitmodel');
        $this->load->model('activitysharemodel');
        $store_id   = [];
        if(!empty($store_id)){$store_id['id']= $store;}
        $draw['date'] = $date;
        $draw['de']   = $de;
        foreach($date as $value){
            $draw[$value]['luck_draw'] = 0;
            $draw[$value]['lottery']   = 0;
            $draw[$value]['visit']     = 0;
            $draw[$value]['share']     = 0;
        }
        Drawmodel::where(function($query) use($store_id){
            if(!empty($store_id)){
                $query->wherehas('resident', function($query) use($store_id){
                    $query->where($store_id);
                });}
        })->where(['activity_id' => $id])->get()->map(function($query)use(&$draw){
            foreach($draw['date'] as $value){
                if(strtotime($query->draw_time) > strtotime($value) && strtotime($query->draw_time) < strtotime($value)+ $draw['de']){
                    if($query->is_draw == 1){
                        $draw[$value]['lottery'] ++;
                    }
                    $draw[$value]['luck_draw'] ++;
                }
            }
        });

        Activityvisitmodel::where(function($query) use($store_id){
            if(!empty($store_id)){
                $query->wherehas('resident', function($query) use($store_id){
                    $query->where($store_id);
                });}
        })->where(['activity_id' => $id])->get()->map(function($query)use(&$draw){
            foreach($draw['date'] as $value) {
                if (strtotime($query->created_at) > strtotime($value) && strtotime($query->created_at) < strtotime($value) + $draw['de']) {
                    $draw[$value]['visit']++;
                }
            }
        });

        Activitysharemodel::where(function($query) use($store_id){
            if(!empty($store_id)){
                $query->wherehas('resident', function($query) use($store_id){
                    $query->where($store_id);
                });}
        })->where(['activity_id' => $id])->get()->map(function($query)use(&$draw){
            foreach($draw['date'] as $value) {
                if (strtotime($query->created_at) > strtotime($value) && strtotime($query->created_at) < strtotime($value) + $draw['de']) {
                    $draw[$value]['share']++;
                }
            }
        });
        return $draw;
    }

    public function date(){
        $post = $this->input->post(null, true);
        if(empty($post['start_time']) || empty($post['id']) || empty($post['num']) || empty($post['end_time'])){
            $this->api_res(1002);
            return;
        }
        $start_time = $post['start_time'];
        $end_time   = empty($post['end_time'])?null:$post['end_time'];
        $time = time();
        if(strtotime($end_time) < $time){
            $time = strtotime($end_time);
        }
        $str = strtotime($end_time) - strtotime($start_time);
        $num = $post['num'];
        $store_id = empty($post['store_id'])?null:$post['store_id'];
        $start_time = strtotime($start_time);
        if($str > time() - $start_time){
            $str = time() - $start_time;
        }
        $de =  ceil($str / $num);
        $date = [];
        for($i=0; $i<$num; $i++) {
           $d = $time - $str + $de * $i;
           $date[] = date("Y-m-d", $d);
        }
        $result =  $this->ac_list($store_id, $de, $post['id'], $date);
        $this->api_res(0, $result);
    }
}
