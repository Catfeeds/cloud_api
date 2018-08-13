<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
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
            'limit', 'employee_id', 'qrcode_url', 'activity_type', 'prize_id'];
        $activity = Activitymodel::where('activity_type', '!=', 'NORMAL')
            ->where('activity_type', '!=', '')
            ->where('coupon_info', 'like', "%$ac_name%")
            ->offset($offset)
            ->limit(PAGINATE)
            ->orderBy('end_time', 'desc')
            ->where(function ($query) use ($store_id) {
                $query->orWhereHas('store', function ($query) use ($store_id) {
                    $query->whereIN('store_id', $store_id);
                });
            })
            ->get($filed)
            ->ToArray();
        $count = Activitymodel::where('activity_type', '!=', 'NORMAL')
            ->where('activity_type', '!=', '')
            ->where('coupon_info', 'like', "%$ac_name%")
            ->orderBy('end_time', 'desc')
            ->where(function ($query) use ($store_id) {
                $query->orWhereHas('store', function ($query) use ($store_id) {
                    $query->whereIN('store_id', $store_id);
                });
            })
            ->count();
        $count = ceil($count / PAGINATE);
        if (!$activity) {
            $this->api_res(1007);
            return false;
        }
        $data = array();
        foreach ($activity as $key => $coupon) {
            $prize = Activityprizemodel::where('id', $coupon['prize_id'])->select(['prize'])->first();
            $p = unserialize($prize->prize);
            $couponarr = Coupontypemodel::whereIn('id', $p)->get(['name'])->toArray();
            $str = '';
            foreach ($couponarr as $value) {
                $str .= $value['name'] . '/';
            }
            $participate = Drawmodel::where('activity_id', $coupon['id'])->orderby('costomer_id')->count();
            $Lottery_number = Drawmodel::where('activity_id', $coupon['id'])->count();
            $lucky_draw = Drawmodel::where(['activity_id' => $coupon['id'], 'is_draw' => '1'])->count();
            $employee_name = Employeemodel::where('id', $coupon['employee_id'])->first(['name']);
            $data[$key]['id'] = $coupon['id'];
            $data[$key]['user'] = $employee_name->name;
            $data[$key]['name'] = $coupon['coupon_info'];
            $data[$key]['start_time'] = date("Y-m-d H:i", strtotime($coupon['start_time']));
            $data[$key]['end_time'] = date("Y-m-d H:i", strtotime($coupon['end_time']));
            $data[$key]['prize'] = $str;
            $limit = unserialize($coupon['limit']);
            $data[$key]['customer'] = $limit['com'];
            $data[$key]['type'] = $coupon['activity_type'];
            $data[$key]['limit'] = $limit['limit'];
            $data[$key]['participate'] = $participate;
            $data[$key]['Lottery_number'] = $Lottery_number;
            $data[$key]['lucky_draw'] = $lucky_draw;
            if ($coupon['type'] == 'LOWER') {
                $data[$key]['status'] = 'Lowerframe';
            } elseif (time() < strtotime($coupon['start_time'])) {
                $data[$key]['status'] = 'Notbeginning';
            } elseif (time() > strtotime($coupon['end_time'])) {
                $data[$key]['status'] = 'End';
            } elseif (time() < strtotime($coupon['end_time']) && time() > strtotime($coupon['start_time'])) {
                $data[$key]['status'] = 'Normal';
            }
        }
        $this->api_res(0, ['count' => $count, 'list' => $data]);
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
        $activity['coupon_info'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['name'] = $post['slogan'];
        $activity['description'] = $post['description'];
        $activity['limit'] = serialize($limit);
        $activity['employee_id'] = CURRENT_ID;
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
        $activity['coupon_info'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['description'] = $post['description'];
        $activity['limit'] = serialize($limit);
        $activity['employee_id'] = CURRENT_ID;
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
        $store_ids = Storeactivitymodel::where(function ($query) {
            $query->orWhereHas('activity', function ($query) {
                $query->where('activity_type', 'CHECKIN')->where('type','!=','LOWER')->where('end_time', '>=',Carbon::now());
            });
        })->get(['store_id'])->toArray();
        $store_id = explode(',', $post['store_id']);
        if( 0 != count($store_ids)) {
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
        $activity['coupon_info'] = $post['name'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['employee_id'] = CURRENT_ID;
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
        $store_ids = Storeactivitymodel::where(function ($query) {
            $query->orWhereHas('activity', function ($query) {
                $query->where('activity_type', 'OLDBELTNEW')->where('type','!=','LOWER')->where('end_time','>=',Carbon::now());
            });
        })->get(['store_id'])->toArray();
        $store_id = explode(',', $post['store_id']);
        if( 0 != count($store_ids)) {
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
        $activity['coupon_info'] = $post['name'];
        $activity['description'] = $post['description'];
        $activity['start_time'] = $post['start_time'];
        $activity['end_time'] = $post['end_time'];
        $activity['employee_id'] = CURRENT_ID;
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
        $this->load->library('form_validation');
        $config = array(
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
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'two_count',
                'label' => '奖品2num',
                'rules' => 'trim|required',

            ),
            array(
                'field' => 'three_count',
                'label' => '奖品3num',
                'rules' => 'trim|required',
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
        return $config;
    }

    public function ValidationCheckin()
    {
        $this->load->library('form_validation');
        $config = array(
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
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'two_count',
                'label' => '半年奖品数量',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'three_count',
                'label' => '一年奖品数量',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'one_grant',
                'label' => '三月奖品发放',
                'rules' => 'trim|required|max_length[255]',
            ),
            array(
                'field' => 'two_grant',
                'label' => '半年奖品发放',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'three_grant',
                'label' => '一年奖品发放',
                'rules' => 'trim|required',
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
        return $config;
    }


}
