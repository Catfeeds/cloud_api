<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/4 0004
 * Time:        15:40
 * Describe:
 */
class Meter extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('meterreadingtransfermodel');
    }

    /**
     * 确定读数的上传并生成账单
     */
    public function confirm()
    {

        $field  = ['month','year','type'];

        $input  = $this->input->post(null,true);

        if(!$this->validationText($this->validateConfirm())){
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }

        if($this->employee->position!='APARTMENT'){
            $this->api_res(1011);
            return;
        }


        $month  = $this->checkAndGetMonth($input['month'],false);
        $year   = $this->checkAndGetYear($input['year'],false);

        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');
        $this->load->model('utilityreadingmodel');
        $this->load->model('residentmodel');
        $this->load->model('storemodel');
        $this->load->model('meterreadingmodel');
        try {

            DB::beginTransaction();

            $type   = $input['type'];

            $transfers = Meterreadingtransfermodel::with('roomunion')
                ->where('type', $type)
                ->where('store_id', $this->employee->store_id)
                ->where('confirmed', Meterreadingtransfermodel::UNCONFIRMED)
                ->get();

            $transfers->map(function ($transfer) use ($year, $month) {
                if (0.01 > $transfer->this_reading - $transfer->last_reading) return true;

                if (0 == $transfer->roomunion->resident_id) {
                    return false;
                }

                $order  = $this->addUtilityOrder($transfer, $year, $month);
                $record = $this->logMeterReading($transfer);

                $this->recordUtilityReadings($order, $transfer);

                $transfer->confirmed = !$transfer->confirmed;
                $transfer->save();

                return true;
            });

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }


    /**
     * 生成水电订单
     */
    private function addUtilityOrder($transfer, $year, $month)
    {
        $roomunion = $transfer->roomunion;

        if (!$resident = $roomunion->resident) {
            return null;
        }

        switch ($transfer->type) {
            case Meterreadingtransfermodel::TYPE_ELECTRIC :
                $type   = Ordermodel::PAYTYPE_ELECTRIC;
                // $price  = $resident->electricity_price;
                $price  = $roomunion->store->electricity_price;
                break;
            case Meterreadingtransfermodel::TYPE_WATER_H :
                $type   = Ordermodel::PAYTYPE_WATER_HOT;
                $price  = $roomunion->store->hot_water_price;
                break;
            case Meterreadingtransfermodel::TYPE_WATER_C :
                $type   = Ordermodel::PAYTYPE_WATER;
                $price  = $roomunion->store->water_price;
                break;
            default:
                throw new Exception('未识别的账单类型！');
                break;
        }

        $money = ($transfer->this_reading - $transfer->last_reading) * $price;

        if (0.01 > $money) {

            return null;
        }

        //分进角，比如 1.01 元，计为 1.1 元
        $money = ceil($money * $transfer->weight / 10) / 10;

        $this->load->helper('string');
        $order = new Ordermodel();
        $order->fill([
            'number'        => date('YmdHis').random_string('numeric', 10),
            'type'          => $type,
            'year'          => $year,
            'month'         => $month,
            'money'         => $money,
            'paid'          => $money,
            'store_id    '  => $roomunion->store_id,
            'resident_id'   => $roomunion->resident_id,
            'room_id'       => $roomunion->id,
            'customer_id'   => $roomunion->resident->customer_id,
            'uxid'          => $roomunion->resident->uxid,
            'room_type_id'  => $roomunion->room_type_id,
            'status'        => Ordermodel::STATE_GENERATED,
            'deal'          => Ordermodel::DEAL_UNDONE,
            'pay_status'    => Ordermodel::PAYSTATE_RENEWALS,
        ]);


        $order->save();



        return $order;
    }


    /**
     * 记录表读数
     */
    private function logMeterReading($transfer)
    {
        $record = new Meterreadingmodel();
        $record->room_id    = $transfer->room_id;
        $record->type       = $transfer->type;
        $record->reading    = $transfer->last_reading;
        $record->save();

        return $record;
    }

    /**
     * 记录水电账单的读数
     */
    private function recordUtilityReadings($order, $transfer)
    {
        if (!$order) {
            return null;
        }

        $record = new Utilityreadingmodel();
        $record->fill([
            'start_reading' => $transfer->last_reading,
            'end_reading'   => $transfer->this_reading,
            'weight'        => $transfer->weight,
            'order_id'      => $order->id,
        ]);
        $record->save();

        return $record;
    }

    private function validateConfirm(){

        return array(

            array(
                'field' => 'type',
                'label' => '费用类型',
                'rules' => 'required|trim|in_list[HOT_WATER_METER,COLD_WATER_METER,ELECTRIC_METER]',
            ),
            array(
                'field' => 'year',
                'label' => '年',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'month',
                'label' => '月',
                'rules' => 'required|trim',
            ),
        );
    }

}