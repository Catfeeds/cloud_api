<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;

/**
 * Author:      chenkk<cooook@163.com>
 * Date:        2018/5/24
 * Time:        11:39
 * Describe:    住户 resident center
 */

class Residentct extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('residentmodel');
        $this->load->model('employeemodel');
        $this->load->model('contractmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
        $this->load->model('smartdevicemodel');
        $this->load->model('storemodel');
    }

    /**
     * 住户列表
     */
    public function showCenter() {
        $post = $this->input->post(null, true);
//        $store_ids = Employeemodel::getMyStoreids();
        $store_ids[0] = $this->employee->store_id;
        if (!$store_ids) {
            $this->api_res(1007, ['error' => '没有找到门店']);
            return;
        }

        $current_page = isset($post['page']) ? intval($post['page']) : 1; //当前页数
        $pre_page     = isset($post['pre_page']) ? intval($post['pre_page']) : 15; //当前页显示条数
        $offset       = $pre_page * ($current_page - 1);
        $field        = ['id', 'name', 'room_id', 'customer_id', 'status', 'begin_time', 'end_time'];

        $total       = Residentmodel::where('status', 'NORMAL')->whereIn('store_id', $store_ids)->count();
        $total_pages = ceil($total / $pre_page); //总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
                'total_pages'              => $total_pages, 'data' => []]);
            return;
        }
        $category = Residentmodel::with('roomunion', 'customer', 'contract')
            ->whereHas('rent_roomunion')
            ->where('status', 'NORMAL')->whereIn('store_id', $store_ids)->take($pre_page)->skip($offset)
            ->orderBy('end_time', 'ASC')
            ->orderBy('room_id', 'ASC')
            ->get($field)
            ->map(function ($res) {
                $data               = $res->toArray();
                $data['days_left']  = Carbon::now()->startOfDay()->diffIndays($res->end_time, false);
                $data['begin_time'] = $res->begin_time->format('Y-m-d');
                $data['end_time']   = $res->end_time->format('Y-m-d');
                return $data;
            })
            ->toArray();
        $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
            'total_pages'              => $total_pages, 'data' => $category]);
    }

    /**
     * 按房号查找
     */
    public function searchResident() {
        $field    = ['id', 'name', 'room_id', 'customer_id', 'status', 'begin_time', 'end_time'];
        $input    = $this->input->post(null, true);
        $store_id = $this->employee->store_id;
//        $store_id   = 1;
        $number = $input['number'];

        $room = Roomunionmodel::where('number', $number)
            ->where('store_id', $store_id)
            ->where('status', Roomunionmodel::STATE_RENT)
            ->first();
        if (empty($room)) {
            $this->api_res(0, ['total' => 0, 'pre_page' => 0, 'current_page' => 0,
                'total_pages'              => 0, 'data'     => []]);
            return;
        }

        $resident = $room->resident()
            ->with(['roomunion' => function ($query) {
                $query->select('id', 'number');
            }])
            ->with(['customer' => function ($query) {
                $query->select('id', 'avatar');
            }])
            ->select($field)
            ->get()
            ->map(function ($resident) {
                $res               = $resident->toArray();
                $res['days_left']  = Carbon::now()->startOfDay()->diffIndays($resident->end_time, false);
                $res['begin_time'] = $resident->begin_time->format('Y-m-d');
                $res['end_time']   = $resident->end_time->format('Y-m-d');
                return $res;
            })
            ->toArray();
        if (empty($resident)) {
            $this->api_res(0, ['total' => 0, 'pre_page' => 0, 'current_page' => 0,
                'total_pages'              => 0, 'data'     => []]);
            return;
        }
        $this->api_res(0, ['total' => 1, 'pre_page' => 1, 'current_page' => 1,
            'total_pages'              => 1, 'data'     => $resident]);
    }

    /**
     * 显示住户详情
     */
    public function showDetail() {
        $post = $this->input->post(null, true);
        $id   = isset($post['id']) ? $post['id'] : null;

        $resident               = Residentmodel::with('customer', 'contract', 'roomunion')->find($id)->toArray();
        $resident['card_one']   = $this->fullAliossUrl($resident['card_one']);
        $resident['card_two']   = $this->fullAliossUrl($resident['card_two']);
        $resident['card_three'] = $this->fullAliossUrl($resident['card_three']);
        $resident['begin_time'] = date('Y-m-d', strtotime($resident['begin_time']));
        $resident['end_time']   = date('Y-m-d', strtotime($resident['end_time']));

        $this->api_res(0, $resident);
    }

    /**
     * 切换公寓
     */
    public function switchoverApartment() {
        $post      = $this->input->post(null, true);
        $store_ids = Employeemodel::getMyStoreids();
        if (!$store_ids) {
            $this->api_res(1007, ['error' => '没有找到门店']);
            return;
        }

        $current_page = isset($post['page']) ? intval($post['page']) : 1; //当前页数
        $pre_page     = isset($post['pre_page']) ? intval($post['pre_page']) : 10; //当前页显示条数
        $offset       = $pre_page * ($current_page - 1);

        $total       = count($store_ids);
        $total_pages = ceil($total / $pre_page); //总页数
        if ($current_page > $total_pages) {
            $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
                'total_pages'              => $total_pages, 'data' => []]);
            return;
        }
        $this->load->model('storemodel');
        $store_names = Storemodel::whereIn('id', $store_ids)->take($pre_page)->skip($offset)
            ->orderBy('id', 'asc')->get(['id', 'name']);
        $this->api_res(0, ['total' => $total, 'pre_page'   => $pre_page, 'current_page' => $current_page,
            'total_pages'              => $total_pages, 'data' => $store_names]);
    }

    /**
     * 员工个人中心
     */
    public function displayCenter() {
        $field    = ['id', 'name', 'position_id', 'store_id', 'avatar'];
        $employee = Employeemodel::with(['position' => function ($query) {
            $query->select('id', 'name');
        }])->where('bxid', CURRENT_ID)->first($field);
        $store                = Storemodel::where('id', $employee->store_id)->first(['name']);
        $employee->store_name = $store->name;
        $this->api_res(0, ['data' => $employee]);
    }

    /**
     * 待办事项
     */
    public function WaitToDo() {
        $post = $this->input->post(null, true);
        $this->load->model('employeemodel');
        $store_ids = Employeemodel::getMyStoreids();
        if (!$store_ids) {
            $this->api_res(1007, ['error' => '没有找到门店']);
            return;
        }
        $this->load->model('ordermodel');
        $count_tf = Ordermodel::whereIn('store_id', $store_ids)->where('type', 'REFUND')->count();
        //其中转租和通知的数量未处理，null用来占位
        $data = ['count_refund' => $count_tf, 'count_sublease' => null, 'count_messagesnd' => null];
        $this->api_res(0, $data);
    }

    /**
     * 入住率数据统计
     */
    public function dataCheckIn() {
        $post   = $this->input->post(null, true);
        $date   = isset($post['date']) ? $post['date'] : null;
        $date_m = $this->getDate($date);
        if (!$date_m) {
            $this->api_res(1007, ['error' => '指定日期不正确']);
            return;
        }
        $store_ids[0] = $this->employee->store_id;

        if (!$store_ids) {
            $this->api_res(1007, ['error' => '没有找到门店']);
            return;
        }
        $this->load->model('roomunionmodel');
        $count_yz  = Roomunionmodel::where('store_id', $store_ids[0])->whereBetween('begin_time', $date_m)->where('status', 'RENT')->count();
        $count_z   = Roomunionmodel::where('store_id', $store_ids[0])->count();
        $count_wcz = $count_z - $count_yz;
        if ($count_z != 0) {
            if ($count_yz != 0) {
                $percentage = round(($count_yz / $count_z), 4) * 100;
                $count_ybfb = $percentage . '%'; //百分比
            } else {
                $count_ybfb = 0;
            }
        } else {
            $this->api_res(1007, ['error' => '没有负责的门店']);
            return;
        }
        if ($count_ybfb != 0) {
            $count_wcz_bfb = 100 - $percentage;
            $count_wbfb    = $count_wcz_bfb . '%';
        } else {
            $count_wbfb = '100' . '%';
        }
        //入住
        $checkin = [
            'totalRent' => ['name' => '总房量', 'count' => $count_z, 'bfb' => null],
            'hasRent'   => ['name' => '已出租', 'count' => $count_yz, 'bfb' => $count_ybfb . ",$count_yz"],
            'notRent'   => ['name' => '未出租', 'count' => $count_wcz, 'bfb' => $count_wbfb . ",$count_wcz"],
        ];
        $this->api_res(0, $checkin);
    }

    /**
     * 访问途径数据统计
     */
    public function dataVisit() {
        $post   = $this->input->post(null, true);
        $date   = isset($post['date']) ? $post['date'] : null;
        $date_m = $this->getDate($date);
        if (!$date_m) {
            $this->api_res(1007, ['error' => '指定日期不正确']);
            return;
        }
        $store_ids[0] = $this->employee->store_id;
//        $store_ids = Employeemodel::getMyStoreids();
        if (!$store_ids) {
            $this->api_res(1007, ['error' => '没有找到门店']);
            return;
        }
        //获取来访方式信息
        $this->load->model('reserveordermodel');
        $field = ['未知', '58同城', '豆瓣', '租房网',
            '嗨住', 'zuber', '中介', '路过',
            '老带新', '朋友介绍', '微信', '同行转介',
            '闲鱼', '蘑菇租房', '微博', '其它',
        ];
        foreach ($field as $key => $value) {
            $count = Reserveordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_m)
                ->where('source', $key)->count();
            $data['name']  = $value;
            $data['count'] = $count;
            $datas[]       = $data;
        }
        $count_lf = Reserveordermodel::whereIn('store_id', $store_ids)->whereBetween('created_at', $date_m)->count();
        foreach ($datas as $key => $value) {
            if ($count_lf != 0) {
                if ($value['count'] != 0) {
                    $count              = $value['count'];
                    $percentage         = round(($count / $count_lf), 4) * 100;
                    $datas[$key]['bfb'] = $percentage . '%' . ",$count"; //百分比
                } else {
                    $datas[$key]['bfb'] = '0' . ',0';
                }
            }
        }
        $this->api_res(0, $datas);
    }

    /**
     * 房型数据统计
     */
    public function dataApartment() {
        $post   = $this->input->post(null, true);
        $date   = isset($post['date']) ? $post['date'] : null;
        $date_m = $this->getDate($date);
        if (!$date_m) {
            $this->api_res(1007, ['error' => '指定日期不正确']);
            return;
        }
        $data = $this->getApartmentInfo($date_m);
        //房型合计
        $hj_zfl = null;
        $hj_kx  = null;
        $hj_yz  = null;
        $hj_dq  = null;
        foreach ($data[1] as $key => $value) {
            $hj_zfl += $value['count_fxzfl'];
            $hj_kx += $value['count_fxkx'];
            $hj_yz += $value['count_fxyz'];
            $hj_dq += $value['count_fxdq'];
        }
        if ($hj_yz != 0) {
            $percentage = round(($hj_yz / $hj_zfl), 4) * 100;
            $hj_bfb     = $percentage . '%'; //百分比
        } else {
            $hj_bfb = 0;
        }
        $fx_hj = ['hj_zfl' => $hj_zfl, 'hj_kx' => $hj_kx, 'hj_yz' => $hj_yz, 'hj_dq' => $hj_dq, 'hj_bfb' => $hj_bfb];

        $this->api_res(0, ['fx_data' => $data[1], 'fx_hj' => $fx_hj]);
    }

    /**
     * 空闲数据统计
     */
    public function datafree() {
        $post   = $this->input->post(null, true);
        $date   = isset($post['date']) ? $post['date'] : null;
        $date_m = $this->getDate($date);
        if (!$date_m) {
            $this->api_res(1007, ['error' => '指定日期不正确']);
            return;
        }
        $data = $this->getApartmentInfo($date_m);

        //获取房型空闲状态信息
        $date_k7 = [date('Y-m-d', time()), date('Y-m-d', strtotime('+1 week'))];
        $date_k8 = [date('Y-m-d', strtotime('+8 days')), date('Y-m-d', strtotime('+30 days'))];
        foreach ($data[0] as $key => $rt) {
            $kx_datas[$key]['name']         = $data[1][$key]['name'];
            $kx_datas[$key]['feature']      = $data[1][$key]['feature'];
            $kx_datas[$key]['count_fxkx']   = $data[1][$key]['count_fxkx'];
            $count_fxkx7                    = Roomunionmodel::whereIn('store_id', $data[2])->where('room_type_id', $rt)->whereBetween('begin_time', $date_k7)->where('status', 'BLANK')->count();
            $kx_datas[$key]['count_fxkx7']  = $count_fxkx7;
            $count_fxkx8                    = Roomunionmodel::whereIn('store_id', $data[2])->where('room_type_id', $rt)->whereBetween('begin_time', $date_k8)->where('status', 'BLANK')->count();
            $kx_datas[$key]['count_fxkx8']  = $count_fxkx8;
            $count_fxkx31                   = $kx_datas[$key]['count_fxkx'] - $count_fxkx7 - $count_fxkx8;
            $kx_datas[$key]['count_fxkx31'] = $count_fxkx31;
        }
        //房型空闲合计
        $hj_fxkz   = null;
        $hj_fxkx7  = null;
        $hj_fxkx8  = null;
        $hj_fxkx31 = null;
        foreach ($kx_datas as $key => $value) {
            $hj_fxkz += $value['count_fxkx'];
            $hj_fxkx7 += $value['count_fxkx7'];
            $hj_fxkx8 += $value['count_fxkx8'];
            $hj_fxkx31 += $value['count_fxkx31'];
        }
        $fx_kzhj = ['hj_fxkz' => $hj_fxkz, 'hj_fxkx7' => $hj_fxkx7, 'hj_fxkx8' => $hj_fxkx8, 'hj_fxkx31' => $hj_fxkx31];

        $this->api_res(0, ['fxkx_data' => $kx_datas, 'fxkx_hj' => $fx_kzhj]);
    }

    /**
     * 获取房型信息
     */
    public function getApartmentInfo($date_m) {
        $store_ids[0] = $this->employee->store_id;
        if (!$store_ids) {
            $this->api_res(1007, ['error' => '没有找到门店']);
            return;
        }
        //获取房型id
        $this->load->model('roomunionmodel');
        $rts = Roomunionmodel::whereIn('store_id', $store_ids)->groupBy('room_type_id')->get(['room_type_id'])->map(function ($t) {
            return $t->room_type_id;
        });
        $this->load->model('roomtypemodel');
        //获取房型名与房型特点
        foreach ($rts as $rt) {
            $rtnf = Roomtypemodel::where('id', $rt)->first(['name', 'feature']);
            if (!$rtnf) {
                $this->api_res(1007, ['error' => '没有查找到房型']);
                return;
            }
            $rt_data['name']    = $rtnf->name;
            $rt_data['feature'] = $rtnf->feature;
            $rt_datas[]         = $rt_data;
        }
        foreach ($rts as $key => $rt) {
            $count_fxzfl                   = Roomunionmodel::whereIn('store_id', $store_ids)->where('room_type_id', $rt)->count();
            $count_fxyz                    = Roomunionmodel::whereIn('store_id', $store_ids)->where('room_type_id', $rt)->whereBetween('begin_time', $date_m)->where('status', 'RENT')->count();
            $count_fxdq                    = Roomunionmodel::whereIn('store_id', $store_ids)->where('room_type_id', $rt)->whereBetween('end_time', $date_m)->where('status', 'RENT')->count();
            $count_fxkx                    = $count_fxzfl - $count_fxyz;
            $rt_datas[$key]['count_fxzfl'] = $count_fxzfl;
            $rt_datas[$key]['count_fxkx']  = $count_fxkx;
            $rt_datas[$key]['count_fxyz']  = $count_fxyz;
            $rt_datas[$key]['count_fxdq']  = $count_fxdq;
            if ($count_fxzfl != 0) {
                if ($count_fxyz != 0) {
                    $percentage  = round(($count_fxyz / $count_fxzfl), 4) * 100;
                    $count_fxbfb = $percentage . '%'; //百分比
                } else {
                    $count_fxbfb = 0;
                }
            } else {
                $this->api_res(1007, ['error' => '房型不符']);
                return;
            }
            $rt_datas[$key]['count_fxbfb'] = $count_fxbfb;
        }
        return [$rts, $rt_datas, $store_ids];
    }

    /**
     * 获取date
     */
    public function getDate($date) {
        if (!$date) { //为指定日期时默认为当前月
            $date_m = [date('Y-m', time()), date('Y-m-d H-i-s', time())]; //当前月至现在
        } else {
            if (strtotime($date) > time()) {
                //指定月超过现在时间
                return null;
            } else if (strtotime($date) == strtotime(date('Y-m', time()))) {
                //指定月是现在月
                $date_m = [date('Y-m', time()), date('Y-m-d H-i-s', time())];
            } else {
                $date_m = [$date, date('Y-m-t', strtotime($date))]; //指定月为过去某月
            }
        }
        return $date_m;
    }

    /**
     * 获取房间状态
     */
    public function getDeviceType($type) {

        switch ($type) {
        case 'LOCK':
            return '门锁';
        case 'HOT_WATER_METER':
            return '热水表';
        case 'COLD_WATER_METER':
            return '冷水表';
        case 'ELECTRIC_METER':
            return '电表';
        case 'UNKNOW':
            return '不明';
        default:
            return '智能设备不明';
        }
    }

    /**
     * 获取房间状态
     */
    public function getRoomStatus($status) {
        switch ($status) {
        case 'NOT_PAY':
            return '办理入住未支付';
        case 'PRE_RESERVE':
            return '办理预订未支付';
        case 'PRE_CHECKIN':
            return '预订转入住未支付';
        case 'PRE_CHANGE':
            return '换房未支付';
        case 'PRE_RENEW':
            return '续约未支付';
        case 'RESERVE':
            return '预订';
        case 'NORMAL':
            return '正常';
        case 'NORMAL_REFUND':
            return '正常退房';
        case 'UNDER_CONTRACT':
            return '违约退房';
        case 'INVALID':
            return '无效';
        default:
            return '房间状态不明';
        }
    }

    public function changeCurrentStore() {
        $input     = $this->input->post(null, true);
        $store_id  = $input['store_id'];
        $store_ids = Employeemodel::getMyStoreids();
        if (!in_array($store_id, $store_ids)) {
            $this->api_res(10019);
            return;
        }
        $this->employee->store_id = $store_id;
        $this->employee->save();
        $this->api_res(0);
    }

}
