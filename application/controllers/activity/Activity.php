<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use EasyWeChat\Foundation\Application;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/31
 * Time:        11:18
 * Describe:    优惠活动
 */
/**************************************************************/
/* 处理各种优惠活动的控制器, 目前还有许多要修改的地方         */
/**************************************************************/
class Activity extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('activitymodel');
    }

    public function listActivity()
    {
        $this->load->model('employeemodel');
        $post = $this->input->post(null,true);
        $page = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $filed = ['id','name','start_time','end_time','qrcode_url',''];
        $where = isset($post['store_id']) ? ['store_id' => $post['store_id']] : [];
        if (!empty($post['city'])){
            $store_ids = Employeemodel::getMyCitystoreids($post['city']);
        }
    }

    public function create()
    {
        $apartments  = Apartmentmodel::where('open', 'Y')->get();
        $coupontypes = Coupontypemodel::orderBy('created_at', 'DESC')->select('id', 'name')->get();

        $this->twig->render('activity/create.html.twig', compact('apartments', 'coupontypes'));
    }

    public function store()
    {
        try {
            $data = $this->fillAndCheckInput();

            $activity = new Activitymodel();
            $activity->type        = $data['type'];
            $activity->name        = $data['name'];
            $activity->description = $data['description'];
            $activity->start_time  = $data['start_time'];
            $activity->end_time    = $data['end_time'];
            $activity->coupon_info = json_encode($data['coupon_info']);
            $activity->save();

            //写入中间表数据
            $activity->apartments()->attach($data['apartment']);
            $activity->coupontypes()->attach($data['coupon_info']);

        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            Util::error($e->getMessage());
        }

        Util::success('活动添加成功!');
    }

    public function update()
    {
        try {
            $id         = trim($this->input->post('id', true));
            $activity   = Activitymodel::findOrFail($id);
            $data       = $this->fillAndCheckInput();

            $activity->name        = $data['name'];
            $activity->type        = $data['type'];
            $activity->description = $data['description'];
            $activity->start_time  = $data['start_time'];
            $activity->end_time    = $data['end_time'];
            $activity->save();

            $activity->apartments()->sync($data['apartment']);
            $activity->coupontypes()->sync($data['coupon_info']);
        } catch (Exception $e) {
            Util::error($e->getMessage());
        }

        Util::success('活动修改成功!');
    }

    /**
     * 显示编辑页面
     */
    public function edit($id)
    {
        try {
            $activity       = Activitymodel::findOrFail($id);
            $coupontypes    = Coupontypemodel::orderBy('created_at', 'DESC')->select('id', 'name')->get();
            $apartments     = Apartmentmodel::where('open', 'Y')->get(['id', 'name'])->toArray();

            foreach ($activity->apartments as $apartment) {
                $ids[] = $apartment->id;
            }

            foreach ($apartments as $key => $apartment) {
                if (in_array($apartment['id'], $ids)) {
                    $apartments[$key]['checked'] = true;
                }
            }

            foreach ($activity->coupontypes->sortBy('pivot.min') as $coupontype) {
                $couponInfo[] = [
                    'id'    => $coupontype->pivot->coupon_type_id,
                    'min'   => $coupontype->pivot->min,
                    'cnt'   => $coupontype->pivot->count,
                ];
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            redirect(site_url('activity'));
        }

        $this->twig->render('activity/edit.html.twig', compact('activity', 'apartments', 'coupontypes', 'couponInfo'));
    }

    public function helpRecords($activityId)
    {
        try {
            $activity   = Activitymodel::findOrFail($activityId);

            if (Activitymodel::TYPE_ATTRACT != $activity->type) {
                throw new Exception('无效的操作, 因为该活动类型不是吸粉活动!');
            }

            $helpRecords    = $activity->helprecords()
                ->where('remark', '!=', '')
                ->get()
                ->groupBy('customer_id');

            foreach ($helpRecords as $customerId => $records) {
                $customer   = Customermodel::find($customerId);
                $status     = '进行中';

                if (count($coupons = $customer->coupons()->where('activity_id', $activityId)->get())) {
                    $status     = '已领取';
                }

                if ($coupons AND $coupons->where('status', Couponmodel::STATUS_ASSIGNED)->count()) {
                    $status     = '申请领取';
                }

                $list[]     = array(
                    'user'      => $customer,
                    'count'     => $records->count(),
                    'status'    => $status,
                );
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            exit;
            log_message('error', $e->getMessage());
        }

        $this->twig->render('activity/help_records.html.twig', compact('list', 'activity'));
    }

    public function recordDetail()
    {
        $customerId     = trim($this->input->get('customer_id', true));
        $activityId     = trim($this->input->get('activity_id', true));

        try {
            $records    = Helprecordmodel::join('customers', function ($join) use($customerId, $activityId) {
                $join->on('help_records.helper_id', '=', 'customers.id')
                    ->where('help_records.customer_id', '=', $customerId)
                    ->where('help_records.activity_id', '=', $activityId)
                    ->where('help_records.remark', '!=', '');
            })
                ->get();
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            Util::error($e->getMessage());
        }

        Util::success('查询成功', $records);
    }

    /**
     * 生成该活动的二维码
     * 场景值,第一位1表示是吸粉活动的二维码,2-4位表示活动的id,后6位表示参与活动的用户的id
     * 目前场景值是有漏洞的, 目前考虑到用户量不会超过百万, 活动量不会过千
     */
    public function qrcode()
    {
        $id = $this->input->post('id', true);

        try {
            $activity   = Activitymodel::findOrFail($id);
            $url        = $activity->qrcode_url;

            if (empty($url)) {
                $sceneId    = sprintf("1%03d%06d", $activity->id, 0);
                $app        = new Application(getCustomerWechatConfig());
                $qrcode     = $app->qrcode;
                $result     = $qrcode->forever($sceneId, 30 * 24 * 3600);
                $url        = $qrcode->url($result->ticket);

                $activity->qrcode_url   = $url;
                $activity->save();
            }

            $data['imgUrl'] = $url;
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            Util::error($e->getMessage());
        }

        Util::success('获取成功', $data);
    }

    /**
     * 验证提交的表单
     */
    private function fillAndCheckInput()
    {
        $data = array(
            'name'             => trim($this->input->post('name', true)),
            'type'             => trim($this->input->post('activity_type', true)),
            'description'      => trim($this->input->post('description', true)),
            'start_time'       => trim($this->input->post('start_time', true)),
            'end_time'         => trim($this->input->post('end_time', true)),
            'apartment'        => $this->input->post('apartment', true),
            'l1_coupontype_id' => trim($this->input->post('l1_coupontype_id', true)),
            'l1_coupon_number' => trim($this->input->post('l1_coupon_number', true)),
            'l1_min_number'    => trim($this->input->post('l1_min_number', true)),
            'l2_coupontype_id' => trim($this->input->post('l2_coupontype_id', true)),
            'l2_coupon_number' => trim($this->input->post('l2_coupon_number', true)),
            'l2_min_number'    => trim($this->input->post('l2_min_number', true)),
            'l3_coupontype_id' => trim($this->input->post('l3_coupontype_id', true)),
            'l3_coupon_number' => trim($this->input->post('l3_coupon_number', true)),
            'l3_min_number'    => trim($this->input->post('l3_min_number', true)),
        );

        if (!in_array($data['type'], [Activitymodel::TYPE_NORMAL, Activitymodel::TYPE_ATTRACT, Activitymodel::TYPE_DISCOUNT])) {
            throw new Exception('活动类型错误!');
        }

        if (empty($data['apartment']) || !is_array($data['apartment'])) {
            throw new Exception('请至少选择一个公寓!');
        }

        if (empty($data['name'])) {
            throw new Exception('请填写活动的名称!');
        }

        if (empty($data['description'])) {
            throw new Exception('请填写活动的简介!');
        }

        if (empty($data['start_time']) || empty($data['end_time'])) {
            throw new Exception('请填写活动的开始与结束时间!');
        }

        if (!strtotime($data['start_time']) || !strtotime($data['end_time'])) {
            throw new Exception('活动开始(或结束)的时间格式错误!');
        }

        if (empty($data['l1_coupontype_id']) && empty($data['l2_coupontype_id']) && empty($data['l3_coupontype_id'])) {
            throw new Exception('请至少选择一种优惠券!');
        }

        if ($data['l1_coupontype_id']) {
            $data['coupon_info'][$data['l1_coupontype_id']] = array(
                'count' => max($data['l1_coupon_number'], 1),
                'min'   => max($data['l1_min_number'], 1),
            );
        }

        if ($data['l2_coupontype_id']) {
            if ($data['l2_min_number'] <= $data['l1_min_number']) {
                throw new Exception('请调整个各个证券级别的顺序');
            }
            if ($data['l2_coupontype_id'] == $data['l1_coupontype_id']) {
                throw new Exception('请选择不同的优惠券');
            }
            $data['coupon_info'][$data['l2_coupontype_id']] = array(
                'count' => max($data['l2_coupon_number'], 1),
                'min'   => max($data['l2_min_number'], 1),
            );
        }

        if ($data['l3_coupontype_id']) {
            if ($data['l3_min_number'] <= $data['l2_min_number']) {
                throw new Exception('请调整个各个优惠券级别的顺序');
            }
            if ($data['l3_coupontype_id'] == $data['l2_coupontype_id'] OR
                $data['l3_coupontype_id'] == $data['l1_coupontype_id']) {
                throw new Exception('请选择不同的优惠券!');
            }

            $data['coupon_info'][$data['l3_coupontype_id']] = array(
                'count' => max($data['l3_coupon_number'], 1),
                'min'   => max($data['l3_min_number'], 1),
            );
        }

        return $data;
    }

    /**
     * 统计吸粉数据, 以公寓为统计单位
     * 统计数据包括 总参与人数, 优惠券领取张数(1,2,3), 吸粉数, 净增吸粉数
     */
    public function report()
    {
        //查活动表, 感觉会快一些
        $activityIds = Activitymodel::where('type', Activitymodel::TYPE_ATTRACT)
            ->get()
            ->groupBy('id')
            ->keys()
            ->toArray();

        foreach ($activityIds as $id) {
            $data[$id] = array(
                'person_time'           => 0,       //参与人次
                'person_amount'         => 0,       //实际总人数
                'attract_number'        => 0,       //吸粉数量
                'attract_number_real'   => 0,       //净吸粉数量, 减去取关的人数
                'coupon_give_out'       => [        //发放出去的券的统计
                    'level_1' => 0,
                    'level_2' => 0,
                    'level_3' => 0,
                ],
            );
        }

        foreach ($activityIds as $id) {
            $activity   = Activitymodel::findOrFail($id);
            $collection = Activityrecordmodel::where('activity_id', $id)->get();

            if ($collection->isEmpty()) {
                continue;
            }

            $couponTypes = $activity->coupontypes;
            foreach ($couponTypes as $couponType) {
                $coupons[] = array(
                    'levlel_min'    => $couponType->pivot->min,
                    'coupon_cnt'    => $activity->coupons()->where('coupon_type_id', $couponType->id)->count(),
                );
            }

            $coupons        = collect($coupons)->sortBy('levlel_min')->pluck('coupon_cnt', 'levlel_min');
            $fansIds        = Helprecordmodel::where('activity_id', $id)->pluck('helper_id')->all();
            $fansAmountReal = Customermodel::where('subscribe', 1)->whereIn('id', $fansIds)->count();

            $data[$id]['person_time']           = $collection->count();
            $data[$id]['person_amount']         = $collection->groupBy('customer_id')->count();
            $data[$id]['attract_number']        = count($fansIds);
            $data[$id]['attract_number_real']   = $fansAmountReal;
            $data[$id]['apartment']             = $activity->apartments()->first()->name;
            $data[$id]['coupon_count']          = $activity->coupons()->count();
            $data[$id]['coupon_by_levle']       = $coupons;
            $couponCnt[] = $coupons;
            unset($coupons);
        }

        $over = collect($data);
        $overCnt = array(
            $over->sum('person_time'),
            $over->sum('person_amount'),
            $over->sum('attract_number'),
            $over->sum('attract_number_real'),
            collect($couponCnt)->sum('30'),
            collect($couponCnt)->sum('60'),
            collect($couponCnt)->sum('100'),
            collect($couponCnt)->sum('30') + collect($couponCnt)->sum('60') + collect($couponCnt)->sum('100')
        );

        $this->twig->render('activity/report.html.twig', compact('data', 'overCnt'));
    }
}
