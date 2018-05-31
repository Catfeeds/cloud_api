<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use GuzzleHttp\Client;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/5/10
 * Time:        14:16
 * Describe:    蛋贝(对单个门锁进行操作)
 */
class Danbaylock
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

    public function __construct($deviceId)
    {
        $this->deviceId = $deviceId;
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

        return [
            'pwd_id'   => $res['pwdID'],
            'password' => $pwd,];
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
        $res = (new Client())->request(
            $method,
            $this->baseUrl . $uri,
            $this->buildRequestBody($options, $enctypeMultipart)
        )->getBody()->getContents();
        $res = json_decode($res, true);

        if (200 != $res['status']) {
            log_message('error','DANBAY参数请求失败');
        }
        return $res;
    }

    /**
     * 构建请求体
     */
    private function buildRequestBody($options, $enctypeMultipart = false)
    {
        $form = collect($options)
            ->put('deviceId', $this->deviceId)
            ->put('mtoken', $this->getToken())
            ->when($enctypeMultipart, function ($items) {
                return $items->transform(function ($item, $key) {
                    return [
                        'name'  => $key,
                        'contents' => $item,
                    ];
                })->values();
            })->toArray();
        $formKey = $enctypeMultipart ? 'multipart' : 'form_params';
        return [$formKey => $form];
    }

    /**
     * 刷新蛋贝token
     */
    public function handle()
    {
        $token = $this->getMtokenByLogin();
        /*if($this->m_redis->storeDanbyToken($token)){
            return $token;
        }else{
            $this->api_res(1010);
        }*/
    }

    /**
     * 服务器端模拟登录蛋贝系统,获取mtoken
     * 获取思路: 成功蛋贝后, 蛋贝会将请求重定向到 ticket_consume_url, 并在 query 里面携带 mtoken, 获取响应头里面的 Location, 并从中解析出 mtoken
     */
    public function getMtokenByLogin()
    {
        $responseHeaders    = (new Client())->request('POST', $this->loginUrl, [
            'form_params'     => [
                'mc_username'        => config_item('danbayUserName'),
                'mc_password'        => config_item('danbayPassword'),
                'random_code'        => 'whatever',
                'return_url'         => 'res_failed',
                'ticket_consume_url' => 'res_success',
            ],
            'allow_redirects' => false,
        ])->getHeaders();

        $redirectUrl = urldecode($responseHeaders['Location'][0]);

        if (strstr($redirectUrl, 'res_failed')) {
            log_message('error','蛋贝系统登录失败!可能是账号或密码出错!');
        }

        if (!strstr($redirectUrl, 'res_success')) {
            log_message('error','蛋贝登录失败!可能是系统故障!');
        }

        //重定向后的url包含ticket和mtoken两个参数
        //从中分解出mtoken
        $parameters = explode('mtoken=', $redirectUrl);
        $parameters = $parameters[1];
        $parameters = explode('ticket=', $parameters);
        $mtoken     = $parameters[0];

        if (strlen($mtoken) != 64) {
            log_message('error',"登录出错, mtoken长度错误,可能是蛋贝系统又出问题了!");
        }
        return $mtoken;
    }

    /**
     * 获取 token
     */
    private function setToken()
    {
        $token = $this->m_redis->getDanBYToken();
        if (!$token) {
            log_message('error','token 过期,请稍后重试!');
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
        $data       = $this->getLockPwdList();
        //$this->api_res(0,$data);
    }

}