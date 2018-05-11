<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/11
 * Time:        2:08
 * Describe:    智能设备-超仪电表
 */
class Joyelectric extends MY_Controller
{
    private $clientId;
    private $publicKeyPath;
    private $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl          = config_item('joyMeterApiUrl');
        $this->clientId         = config_item('joyMeterClientId');
        $this->publicKeyPath    = config_item('joyPublicKeyPath');
    }

    /**
     * 生成access_token
     * @return null|string
     */
    public function getAccessToken()
    {
        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicKeyPath));

        $data   = json_encode([
            'client_id' => $this->clientId,
            'datetime'  => date('YmdHis'),
        ]);

        return openssl_public_encrypt($data ,$encrypted, $publicKey) ? base64_encode($encrypted) : null;
    }


    /**
     * 像超仪服务器发送请求
     */
    public function request($uri, array $data)
    {
        $data['access_token']   = $this->getAccessToken();

        $res = $this->httpCurl('POST', $this->baseUrl . $uri, [
            'form_params' => $data,
        ]);

        $res = json_decode($res, true);

        return $res['status'] == 1 ? $res['data'] : null;
    }


    /**
     * 查询电表状态（电表的连网状态和通电状态）
     */
    public function meterStatus( $deviceNumber)
    {
        return $this->request('queryMeterStatus.do', [
            'meterNo'   => $deviceNumber,
        ]);
    }


    /**
     * 查询设备几天的耗电量
     * 日期格式为date('Y-m-d')
     */
    public function powerCostPerDay($deviceNumber, $startDate, $endDate)
    {
        return $this->request('queryPowerCostPerDay.do', [
            'meterNo'       => $deviceNumber,
            'start_time'    => $startDate,
            'end_time'      => $endDate,
        ]);
    }


    /**
     * 查询设备在一段时间内的充值信息(充值电量)
     * 日期格式为date('Y-m-d')
     */
    public function rechargeInfo($deviceNumber, $startDate, $endDate)
    {
        return $this->request('queryRechargeInfo.do', [
            'meterNo'    => $deviceNumber,
            'start_time' => $startDate,
            'end_time'   => $endDate,
        ]);
    }


    /**
     * 根据表号查询用户信息
     */
    public function userInfoByMeterNo($deviceNumber)
    {
        return $this->request('findUserByMeterNo.do', [
            'meterNo'   => $deviceNumber,
        ]);
    }


    /**
     * 根据表号充值(量)
     */
    public function rechargeByMeterNo($deviceNumber, $money)
    {
        return $this->request('rechargeByMeterNo.do', [
            'meterNo'   => $deviceNumber,
            'money'     => $money,
        ]);
    }


    /**
     * 根据表号退费
     */
    public function refundByMeterNo($deviceNumber, $money)
    {
        return $this->request('refundByMeterNo.do', [
            'meterNo' => $deviceNumber,
            'money'   => $money,
        ]);
    }


    /**
     * 根据表号清空余额
     */
    public function clearBalanceByMeterNo($deviceNumber)
    {
        return $this->request('clearBalanceByMeterNo.do', [
            'meterNo'  => $deviceNumber,
        ]);
    }


    /**
     * 根据表号控制继电器
     * action表示动作目的, 1:打开, 0:关闭
     */
    public function operateTheMeter($deviceNumber, $action = 0)
    {
        return $this->request('mbusControlByMeterNo.do', [
            'meterNo'   => $deviceNumber,
            'action'    => $action,
        ]);
    }


    /**
     * 根据表号发送短信通知
     */
    public function sendMessageToUser($deviceNumber)
    {
        return $this->request('sendSmsByMeterNo.do', [
            'meterNo'   => $deviceNumber,
        ]);
    }


    /**
     * 根据表号抄表（多表）
     */
    public function readMultipleByMeterNo(array $deviceNumbers)
    {
        $res = $this->request('readByMeterNo.do', [
            'meterNo'   => implode($deviceNumbers, ','),
        ]);

        return collect($res)->pluck('this_read', 'meter_no')->toArray();
    }


    /**
     * 根据表号抄表(单表)
     */
    public function readByMeterNumber($deviceNumber)
    {
        return $this->request('readByMeterNo.do', [
            'meterNo'   => $deviceNumber,
        ]);
    }


    /**
     * 根据表号入住,退住接口
     * action: in->入住, out->退住
     * time: date('Y-m-d H:i:s')
     */
    public function checkInOut($deviceNumber, $action, $peopleCount = 1, $time)
    {
        return $this->request('checkInOut.do', [
            'meterNo'   => $deviceNumber,
            'json'      => json_encode([
                'meterNo'   => $deviceNumber,
                'action'    => $action,
                'peoples'   => $peopleCount,
                'datatime'  => $time,
            ]),
        ]);
    }


    /**
     * 根据日期查询抄表记录
     * 时间格式: date('Y-m-d')
     */
    public function readRecordsByDate($startDate, $endDate)
    {
        return $this->request('findReadInfoByDate.do', [
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);
    }


    /**
     * 根据时间(年月日时分秒)查询抄表记录
     * 时间格式: date('Y-m-d H:i:s')
     */
    public function readRecordsByTime($startTime, $endTime)
    {
        return $this->request('findReadInfoByDateTime.do', [
            'startDateTime' => $startTime,
            'endDateTime'   => $endTime,
        ]);
    }

    /**
     * 注册房源信息
     */
    public function registerRoomInfo(array $data)
    {
        return $this->request('registRoomInfo.do', [
            'roomInfo' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]);
    }
}