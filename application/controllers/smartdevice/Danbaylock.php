<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/10
 * Time:        14:16
 * Describe:    蛋贝
 */
class Danbaylock extends MY_Controller
{
    protected $deviceId;
    protected $token;
    private   $baseUrl      = 'http://www.danbay.cn/system/';
    protected $signature    = 'danbay:update-token';
    protected $description  = 'update-token-for-danbay-api-request';
    protected $loginUrl     = 'http://www.danbay.cn/system/connect';

    const PWD_TYPE_GUEST    = 3;
    const PWD_TYPE_BUTLER   = 2;
    const PWD_TYPE_TEMP     = 0;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('M_redis');
        $this->deviceId = 'dccf6c99c17845481eba84692d4027e4';
    }

    /**
     * 增加临时密码
     */
    public function addTempPwd()
    {
        $pwd    = mt_rand(100000, 999998);
        $res    = $this->sendRequet('deviceCtrl/lockPwd/addPwd',[
            'password'  => $pwd,
            'pwdType'   => 0,
        ]);

        return ['pwd_id'   => $res['pwdID'],
                'password' => $pwd,
                ];
    }

    /**
     * 新的房客随机密码
     */
    public function newRandomGuestPwd()
    {
        $pwd = mt_rand(100000, 999998);

        $res = $this->sendRequet('deviceCtrl/lockPwd/addPwd',[
            'password'  => $pwd,
            'pwdType'   => self::PWD_TYPE_GUEST,
        ]);

        return $res;
        /*[
            'pwd_id'   => $res['pwdID'],
            'password' => $pwd,
        ];*/
    }

    /**
     * 清除所有的房客密码
     */
    public function clearAllGuestPwd()
    {
        collect($this->getLockPwdList())->where('pwdType', self::PWD_TYPE_GUEST)
            ->each(function ($item) {
                return $this->removePwd($item['pwdType'], $item['pwdID']);
            });

        return true;
    }

    /**
     * 编辑指定密码
     */
    public function editGuestPwd($pwdID, $newPwd)
    {
        $res = $this->sendRequet('deviceCtrl/lockPwd/editPwd', [
            'pwdType'   => 3,
            'password'  => $newPwd,
            'pwdID'     => $pwdID,
        ]);
    }

    /**
     * 移除指定密码
     */
    public function removePwd($pwdType, $pwdID)
    {
        return $this->sendRequet('deviceCtrl/lockPwd/delPwd', [
            'pwdType'   => $pwdType,
            'pwdID'     => $pwdID,
        ], 'POST', true);
    }

    /**
     * 获取指定门锁的密码列表
     */
    public function getLockPwdList()
    {
        return $this->sendRequet('deviceInfo/getLockPwdList');
    }

    /**
     * 向蛋贝服务器发送请求
     */
    private function sendRequet($uri, $options = [], $method = 'POST', $enctypeMultipart = false)
    {
        $options['deviceid'] = $this->deviceId;
        $options['mtoken']  = $this->getToken();
        $res = $this->httpCurl(
            $this->baseUrl . $uri,
            $method,
            $options
        );
        $res = json_decode($res, true);

        if (200 != $res['status']) {
            throw new \Exception($res['message']);
        }
        return $res['result'];
    }

    /**
     * 服务器端模拟登录蛋贝系统,获取mtoken
     * 获取思路: 成功蛋贝后, 蛋贝会将请求重定向到 ticket_consume_url, 并在 query 里面携带 mtoken, 获取响应头里面的 Location, 并从中解析出 mtoken
     */
    private function getMtokenByLogin()
    {
        $responseHeaders    = $this->httpCurl('POST', $this->loginUrl, [
            'form_params'     => [
                'mc_username'        => config_item(''),
                'mc_password'        => config_item(''),
                'random_code'        => 'whatever',
                'return_url'         => 'res_failed',
                'ticket_consume_url' => 'res_success',
            ],
            'allow_redirects' => false,
        ])->getHeaders();

        $redirectUrl = urldecode($responseHeaders['Location'][0]);

        if (strstr($redirectUrl, 'res_failed')) {
            throw new \Exception('蛋贝系统登录失败!可能是账号或密码出错!');
        }

        if (!strstr($redirectUrl, 'res_success')) {
            throw new Exception('蛋贝登录失败!可能是系统故障!');
        }

        //重定向后的url包含ticket和mtoken两个参数
        //从中分解出mtoken
        $parameters = explode('mtoken=', $redirectUrl);
        $parameters = $parameters[1];
        $parameters = explode('ticket=', $parameters);
        $mtoken     = $parameters[0];

        if (strlen($mtoken) != 64) {
            throw new \Exception("登录出错, mtoken长度错误,可能是蛋贝系统又出问题了!", 500);
        }

        $this->api_res(0,$mtoken);
        return $mtoken;
    }


    /**
     * 获取 token
     */
    private function setToken()
    {
        $token = $this->M_redis->getDanBYToken();
        if (!$token) {
            throw new \Exception('token 过期,请稍后重试!');
        }
        $this->token = $token;
        return $this;
    }

    /**
     * 获取请求凭证 token
     */
    private function getToken()
    {
        if ($this->token) {
            return $this->token;
        }
        $this->setToken();
        return $this->token;
    }

    public function test()
    {
        $data       = $this->getMtokenByLogin();
        var_dump($data);
    }

}