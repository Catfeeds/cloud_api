<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/8
 * Time:        20:08
 * Describe:    合同后台操作
 */
class Contract extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('contractmodel');
    }

    /**
     * 合同列表
     */
    public function showContract() {
        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $this->load->model('employeemodel');
        $post      = $this->input->post(null, true);
        $page      = isset($post['page']) ? intval($post['page']) : 1;
        $offset    = PAGINATE * ($page - 1);
        $where     = [];
        $store_ids = explode(',', $this->employee->store_ids);
        $filed     = ['id', 'contract_id', 'resident_id', 'room_id', 'type', 'created_at',
            'status', 'employee_id', 'store_id'];

        if (!empty($post['store_id'])) {
            $where['store_id'] = intval($post['store_id']);
        };
        if (!empty($post['status'])) {
            $where['status'] = trim($post['status']);
        };
        if (!empty($post['contract_id'])) {
            $search = trim($post['contract_id']);
        } else {
            $search = '';
        };
        //合同开始结束时间的起止日期
        if (!empty($post['begin_time_start'])) {
            $bt_start = trim($post['begin_time_start']);
        } else {
            $bt_start = date('Y-m-d H:i:s', 0);
        };

        if (!empty($post['begin_time_stop'])) {
            $bt_stop = trim($post['begin_time_stop']);
        } else {
            $bt_stop =  Residentmodel::max('begin_time');
        };

        if (!empty($post['end_time_start'])) {
            $et_start = trim($post['end_time_start']);
        } else {
            $et_start = date('Y-m-d H:i:s', 0);
        };

        if (!empty($post['end_time_stop'])) {
            $et_stop = trim($post['end_time_stop']);
        } else {
            $et_stop = Residentmodel::max('end_time');
        };

        $resident = Residentmodel::whereBetween('begin_time', [$bt_start, $bt_stop])
            ->whereBetween('end_time', [$et_start, $et_stop])
            ->get(['id'])
            ->toArray();
        $residents = [];
        if (!empty($resident)) {
            foreach ($resident as $key => $value) {
                $residents[] = $resident[$key]['id'];
            }
        }

        $contract = new Contractmodel();
        $count = $contract->count( $store_ids, $residents, $where, $search);

        if ($page > $count || $page < 1) {
            $this->api_res(0, ['list' => []]);
            return;
        } else {
            $order = Contractmodel::with('employee')
                ->with('resident')
                ->with('store')
                ->with('roomunion')
                ->where($where)
                ->where(function ($query) use ($search) {
                    $query->orWhereHas('resident', function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%");
                    })->orWhereHas('roomunion', function ($query) use ($search) {
                        $query->where('number', 'like', "%$search%");
                    });
                })
                ->whereIn('store_id', $store_ids)
                ->whereIn('resident_id', $residents)
                ->take(PAGINATE)
                ->skip($offset)
                ->get($filed)
                ->map(function ($s) {
                    $s['begin_time'] = date('Y-m-d', strtotime($s['resident']['begin_time']));
                    $s['end_time']   = date('Y-m-d', strtotime($s['resident']['end_time']));
                    return $s;
                })->toArray();
        }
        $this->api_res(0, ['list' => $order, 'count' => $count]);
    }
}