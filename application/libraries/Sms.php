<?php
use GuzzleHttp\Client;

/**
 * Author:
 * Date:        2018/4/2
 * Time:        21:13
 * Describe:    使用云片发送短信验证码
 */

class Sms
{
    private $smsApi;
    private $smsKey;
    private $error;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->smsApi = config_item('yunpian_api_url');
        $this->smsKey = config_item('yunpian_api_key');
    }

    /**
     * 返回错误信息
     */
    public function showError()
    {
        return $this->error;
    }

    /**
     * 发送短信
     */
    public function send($string, $mobile)
    {
        $request = (new Client())->request('POST', $this->smsApi, [
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'charset'       => 'utf-8',
            ],
            'form_params' => [
                'text'      => $string,
                'apikey'    => $this->smsKey,
                'mobile'    => $mobile,
            ],
        ])->getBody()->getContents();

        $result = json_decode($request, true);

        if (isset($result['code']) && $result['code'] != 0) {
            $this->error = $result['msg'];
            return false;
        }

        return true;
    }
}