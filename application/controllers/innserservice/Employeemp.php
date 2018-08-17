<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use EasyWeChat\Foundation\Application;

/**
 * 员工端微信公众号
 */
class Employeemp extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }

    /**
     * 同步员工公众号关注的用户openid
     */
    public function syncUsers() {
        $this->load->model('employeemodel');
        $this->load->helper('common');
        $app = new Application(getWechatEmployeeConfig());

        $nextOpenId = null;
        for (;;) {
            try {
                $openidList = $app->user->lists($nextOpenId);
                if ($openidList->count == 0) {
                    log_message("info", "同步用户完成，总计 " . $openidList->total);
                    $this->api_res(0);
                    return;
                }
                log_message("debug", "get user count: $openidList->count" . " total: " . $openidList->total);

                $users = $app->user->batchGet($openidList->data["openid"]);
                foreach ($users->user_info_list as $user) {
                    log_message("debug", "found " . $user["nickname"] . " openid.");
                    if (!empty($user["unionid"]) && !empty($user["openid"])) {
                        try {
                            Employeemodel::where("unionid", $user["unionid"])
                                ->update(['employee_mp_openid' => $user["openid"]]);
                            log_message("info", "synced " . $user["nickname"] . " openid.");
                        } catch (Exception $e) {
                            log_message('error', '更新员工(' . $user["nickname"] . ')信息失败：' . $e->getMessage());
                        }
                    }
                }
                $nextOpenId = $openidList->next_openid;
            } catch (Exception $e) {
                log_message('error', '获取微信公众号用户列表失败' . $e->getMessage());
                throw $e;
            }
        }
    }
}
