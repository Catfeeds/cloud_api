<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/18 0018
 * Time:        17:08
 * Describe:    预约看房
 */
class Server extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('serviceordermodel');
        $this->load->model('employeemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('customermodel');
    }

    /**
     * 服务订单列表
     */
    public function listServer()
    {

        $post = $this->input->post(NULL, true);
        $page = isset($post['page']) ? intval($post['page']) : 1;//当前页数
        $page_count = isset($post['page_count']) ? intval($post['page_count']) : 4;//当前页显示条数
        $offset = $page_count * ($page - 1);
        $filed = ['id','room_id','customer_id','type','name', 'phone', 'time','deal', 'remark'];

        $store_id   = $this->employee->store_id;

        $count_total = ceil(Serviceordermodel::where('store_id',$store_id)->count());//总条数
        $count = ceil($count_total / $page_count);//总页数
        if ($page > $count) {
            return;
        }
        $server = Serviceordermodel::with('roomunion','customer')->where('store_id',$store_id)
                                    ->orderBy('id', 'desc')->get($filed)->toArray();
        $this->api_res(0, ['list' => $server, 'page' => $page, 'count_total' => $count_total, 'count' => $count]);
    }
    /**
     * 显示一条服务的详情
     */
    public function show()
    {
        $post = $this->input->post(null,true);
        if ($post['id']){
            $id = intval($post['id']);
        }else{
            $this->api_res(0,[]);
            return;
        };

        $server = Serviceordermodel::with('roomunion','customer')->find($id)->toArray();
        $this->api_res(0,$server);
    }

    //创建一个订单
    public function create(){
        try {
            $data                  = $request->all();
            $data['apartment_id']  = $this->authUser->apartment_id;

            $room   = $roomRepo->findWhere([
                'id'            => $data['room_id'],
                'apartment_id'  => $data['apartment_id'],
            ])->first();

            if (0 == $room->resident_id) {
                throw new \Exception('检索不到该房间的住户信息, 请核实!');
            }

            $data['customer_id']    = $room->resident->customer_id;
            $data['employee_id']    = $this->authUser->id;

            //这里可能还要向用户推送模板消息
            $record  = $this->repository->addItem($data);

        } catch (\Exception $e) {
            return $this->respError($e->getMessage());
        }

        return $this->respSuccess($record, new ServiceTransformer(), '添加成功');


    }


    //更新订单
    public function update(){
        try {
            $record = $this->repository->findWhere([
                'id'            => $id,
                'apartment_id'  => $this->authUser->apartment_id,
            ])->first();

            $input  = $request->all();

            if (!empty($input['remark'])) {
                $record->remark     = $input['remark'];
            }

            switch ($input['action']) {
                case 'CONFIRM'  :
                    $record     = $this->confirm($record, $input, $orderRepo);
                    break;
                case 'PAY'      :
                    $record     = $this->payAndServe($record, $input, $orderRepo);
                    break;
                case 'SERVING'  :
                    $record     = $this->serve($record, $orderRepo);
                    break;
                case 'COMPLETE' :
                    $record     = $this->complete($record);
                    break;
                case 'CANCEL'   :
                    $record     = $this->cancel($record, $orderRepo);
                    break;
                default:
                    throw new \Exception('无法识别的操作!');
                    break;
            }

        } catch (\Exception $e) {
            return $this->respError($e->getMessage());
        }

        return $this->respSuccess($record, new ServiceTransformer(), '更新成功!');

    }

    /**
     * 将记录改为服务中的状态
     */
    private function serve($record, OrderRepo $orderRepo)
    {
        if ($this->repository->status_paid != $record->status) {
            throw new \Exception('当前状态不能进行此操作!');
        }

        //记录流水
        if ($record->money > 0) {
            $orderRepo->newServiceOne($record);
        }

        $record->status     = $this->repository->status_serving;
        $record->save();

        return $record;
    }

    //确认订单
    private function confirm($record, $input, $orderRepo)
    {
        if ($this->repository->status_submitted != $record->status) {
            throw new \Exception('订单当前状态不允许该操作!');
        }

        if (0 < $input['money']) {
            $record->money      = $input['money'];
            $record->status     = $this->repository->status_pending;
        } else {
            $record->money      = 0;
            $record->status     = $this->repository->status_serving;
        }

        $record->save();

        return $record;
    }

    /**
     * 取消服务
     */
    private function cancel($record, $orderRepo)
    {
        if (!in_array($record->status, [
            $this->repository->status_pending,
            $this->repository->status_submitted,
            $this->repository->status_serving,
        ])) {
            throw new \Exception('当前状态不允许该操作!');
        }


        if ($this->repository->status_serving AND 0 < $record->money) {
            throw new \Exception('用户已经支付, 无法取消');
        }

        //删除对应的订单
        if (0 < $record->money) {
            $order  = $orderRepo->findWhere([
                'other_id'  => $record->id,
                'type'      => $record->type,
            ])->first();

            if (count($order)) {
                $order->delete();
            }
        }

        $record->status     = $this->repository->status_canceled;
        $record->save();

        return $record;
    }

    /**
     * 完成服务
     */
    private function complete($record)
    {
        if ($this->repository->status_serving != $record->status) {
            throw new \Exception('只有服务中的订单才能进行此操作!');
        }

        $record->status     = $this->repository->status_completed;
        $record->save();

        return $record;
    }




}