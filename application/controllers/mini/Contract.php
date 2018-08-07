<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/10 0010
 * Time:        16:38
 * Describe:
 */
class Contract extends MY_Controller {

    /**
     * 自动签章接口的结果通知
     */
    public function notify() {
        $this->load->library('form_validation');
        $this->load->library('fadada');
        $this->load->model('fddrecordmodel');

        $config = array(
            array(
                'field' => 'transaction_id',
                'label' => 'transaction_id',
                'rules' => 'required',
            ),
            array(
                'field' => 'contract_id',
                'label' => 'contract_id',
                'rules' => 'required',
            ),
            array(
                'field' => 'result_code',
                'label' => 'result_code',
                'rules' => 'required',
            ),
            array(
                'field' => 'result_desc',
                'label' => 'result_desc',
                'rules' => 'required',
            ),
            array(
                'field' => 'timestamp',
                'label' => 'timestamp',
                'rules' => 'required',
            ),
            array(
                'field' => 'msg_digest',
                'label' => 'msg_digest',
                'rules' => 'required',
            ),
        );

        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE) {
            exit('缺少参数!');
        }

        //获取请求参数
        $transactionId = trim($this->input->post('transaction_id', true));
        $contractId    = trim($this->input->post('contract_id', true));
        $resultCode    = trim($this->input->post('result_code', true));
        $resultDesc    = trim($this->input->post('result_desc', true));
        $downloadUrl   = trim($this->input->post('download_url', true));
        $viewpdfUrl    = trim($this->input->post('viewpdf_url', true));
        $timestamp     = trim($this->input->post('timestamp', true));
        $msgDigest     = trim($this->input->post('msg_digest', true));

        try {
            //验证请求可靠性, 通过 msg_digest
            $msgDigestArray = array(
                'md5'  => [$timestamp],
                'sha1' => [config_item('fadada_api_app_secret'), $transactionId],
            );

            $checkMsgDigest = $this->fadada->getMsgDigest($msgDigestArray);

            if ($checkMsgDigest != $msgDigest) {
                throw new Exception('msg_digest 校验不通过');
            }

            $record = Fddrecordmodel::where('transaction_id', $transactionId)->firstOrFail();

            //3000 表示成功, 3001 表示失败
            if ($resultCode == 3000) {
                $status = Fddrecordmodel::STATUS_SUCCEED;
            } else {
                $status = Fddrecordmodel::STATUS_FAILED;
            }
            $record->status = $status;
            $record->remark = $resultDesc;
            $record->save();
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
        return true;
    }

    /**
     * 未归档的合同包括 住户未生成和住户未签署以及住户签署员工未签署
     */
    public function listUnSign() {
        $input             = $this->input->post(null, true);
        $page              = (int) (isset($input['page']) ? $input['page'] : 1);
        $per_page          = (int) (isset($input['per_page']) ? $input['per_page'] : PAGINATE);
        $offset            = ($page - 1) * $per_page;
        $where['store_id'] = $this->employee->store_id;
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('contractmodel');

        if (isset($input['room_number'])) {
            $room_ids = Roomunionmodel::where('number', $input['room_number'])
                ->where('store_id', $this->employee->store_id)
                ->get()
                ->map(function ($a) {
                    return $a->id;
                });
        } else {
            $room_ids = Roomunionmodel::where('store_id', $this->employee->store_id)
                ->get()
                ->map(function ($a) {
                    return $a->id;
                });
        }

        $rooms = Residentmodel::with('roomunion')
            ->where($where)
            ->whereIn('room_id', $room_ids)
            ->whereIn('status', ['NOT_PAY', 'PRE_RESERVE'])
            ->orderBy('updated_at', 'ASC')
            ->offset($offset)
            ->limit($per_page)
            ->get()
            ->map(function ($room) {
                $room2               = $room->toArray();
                $room2['begin_time'] = date('Y-m-d', strtotime($room->begin_time->toDateTimeString()));
                return $room2;
            });
        $total_page = ceil(($rooms->count()) / PAGINATE);

        $data['data']         = $rooms->toArray();
        $data['per_page']     = $per_page;
        $data['current_page'] = $page;
        $data['total']        = $rooms->count();
        $data['total_page']   = $total_page;

        $this->api_res(0, $data);
    }

    /**
     * 自动签署
     * customer_id 在接入平台申请到 CA 时候获取, 不同的公寓有不同的 customer_id
     * 目前流程是客户签完章之后, 再签公章
     * 返回合同的预览链接 url
     */
    public function autoSign() {
        $this->load->library('fadada');
        $this->load->model('contractmodel');
        $this->load->model('fddrecordmodel');
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        try {
            $contractId = trim($this->input->post('contract_id'));
            $contract   = Contractmodel::findOrFail($contractId);
            //乙方签署完成后, 将合同状态设置为签署中(signing)
            //乙方签署完成后, 甲方才可以签
            //同时, 也要避免甲方重复签署, 即先查找甲方的签署记录, 查不到成功记录才继续签
            if ($contract->status == Contractmodel::STATUS_SIGNING) {
                $transaction = $contract->transactions()
                    ->where('role', Fddrecordmodel::ROLE_A)
                    ->where('status', '!=', Fddrecordmodel::STATUS_FAILED)
                    ->first();
                if (empty($transaction)) {
                    //查询, 获取公寓的法大大customer_id
                    $customerId = $contract->resident->roomunion->store->fdd_customer_id;
                    if (!$customerId) {
                        throw new Exception('该公寓没有客户编号,请设置CA后重试!');
                    }
                    $transactionId = 'A' . date('YmdHis') . mt_rand(10, 59);
                    //生成新的交易记录
                    $record                 = new Fddrecordmodel();
                    $record->remark         = '甲方发起了签署!';
                    $record->status         = Fddrecordmodel::STATUS_INITIATED;
                    $record->contract_id    = $contract->id;
                    $record->transaction_id = $transactionId;
                    $record->role           = Fddrecordmodel::ROLE_A;
                    $record->save();
                    //向法大大系统发送请求, 签署合同
                    $res = $this->fadada->extsignAuto(
                        $transactionId,
                        $contract->contract_id,
                        $contract->doc_title,
                        $customerId,
                        config_item('fadada_platform_sign_key_word'),
                        config_item('fdd_notify_url') //结果回调
                    );
                    if ($res == false) {
                        $this->api_res(10080, [$this->fadada->showError()]);
                        return;
                    }
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
        $this->api_res(0, ['url' => $contract->view_url]);
    }

    /**
     * 电子合同的归档
     */
    public function archive() {
        $this->load->library('fadada');
        $this->load->model('fddrecordmodel');
        $this->load->model('contractmodel');
        $this->load->model('residentmodel');
        $this->load->model('ordermodel');
        $this->load->model('roomunionmodel');

        try {
            $contractId = trim($this->input->post('contract_id'));
            $contract   = Contractmodel::findOrFail($contractId);

            if ($contract->status != Contractmodel::STATUS_SIGNING) {
                $this->api_res(10081);
                return;
            }

            //查找签署记录, 确保甲乙双方都已经成功签署过
            $arrToCompare = [Fddrecordmodel::ROLE_B, Fddrecordmodel::ROLE_A];
            $records      = $contract->transactions()
                ->where('status', Fddrecordmodel::STATUS_SUCCEED)
                ->pluck('role')
                ->toArray();

            if ($arrToCompare != array_intersect($arrToCompare, $records)) {
                $this->api_res(10082);
                return;
            }

            //调用合同的存档接口
            $res = $this->fadada->contractFiling($contract->contract_id);

            if ($res == false) {
                $this->api_res(10080, [$this->fadada->showError()]);
                return;
            }

            if ($res['code'] != 1000) {
                log_message('error', $res['msg']);
                $this->api_res(10080, ['error' => $res['msg']]);
                return;
            }

            $contract->status = Contractmodel::STATUS_ARCHIVED;
            $contract->save();

            $resident        = $contract->resident;
            $ordersUnhandled = $resident->orders()
                ->whereIn('status', [Ordermodel::STATE_AUDITED, Ordermodel::STATE_PENDING, Ordermodel::STATE_CONFIRM])
                ->count();

            if (0 == $ordersUnhandled) {
                $resident->update(['status' => Residentmodel::STATE_NORMAL]);
                $resident->roomunion->update(['status' => Roommodel::STATE_RENT]);
            }

        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }

        $this->api_res(0, ['res' => $res]);
    }


    /*********************************************** new ********************************************/

    /**
     * 批量给用户已经签署的合同盖章
     */
    public function batchSign()
    {
        $this->load->library('fadada');
        $this->load->model('contractmodel');
        $this->load->model('fddrecordmodel');
        $this->load->model('residentmodel');
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('ordermodel');
        $contract_ids   = explode(',',$this->input->post('contract_ids'));
        $contracts   = Contractmodel::whereIn('id',$contract_ids)->get();
        foreach ($contracts as $contract) {
            if ($contract->status != Contractmodel::STATUS_SIGNING) {
                $this->api_res(10081);
                return;
            }
            $transaction = $contract->transactions()
                ->where('role', Fddrecordmodel::ROLE_A)
                ->where('status', '!=', Fddrecordmodel::STATUS_FAILED)
                ->first();
            if (empty($transaction)) {
                if (!isset($contract->resident->roomunion->store->fdd_customer_id)) {
                    $this->api_res(10083);
                    return;
                } else {
                    $customerId = $contract->resident->roomunion->store->fdd_customer_id;
                }
                $transactionId = 'A' . date('YmdHis') . mt_rand(10, 59);
                //生成新的交易记录
                $record                 = new Fddrecordmodel();
                $record->remark         = '甲方发起了签署!';
                $record->status         = Fddrecordmodel::STATUS_INITIATED;
                $record->contract_id    = $contract->id;
                $record->transaction_id = $transactionId;
                $record->role           = Fddrecordmodel::ROLE_A;
                $record->save();
                //向法大大系统发送请求, 签署合同
                $res = $this->fadada->extsignAuto(
                    $transactionId,
                    $contract->contract_id,
                    $contract->doc_title,
                    $customerId,
                    config_item('fadada_platform_sign_key_word'),
                    config_item('base_url').'mini/contract/autosignnotify' //结果回调
                );
                if ($res == false) {
                    $this->api_res(10080);
                    return;
                }
            } else {
                if (!$this->signToArchive($contract)) {
                    $this->api_res(10084);
                    return;
                }
            }
        }
        $this->api_res(0);
    }

    /**
     * archive
     */
    private function signToArchive($contract) {
        if ($contract->status != Contractmodel::STATUS_SIGNING) {
            log_message('error', "$contract->id 合同目前状态无法进行此操作");
            return false;
        }
        //查找签署记录, 确保甲乙双方都已经成功签署过
        $arrToCompare = [Fddrecordmodel::ROLE_B, Fddrecordmodel::ROLE_A];
        $records      = $contract->transactions()
            ->where('status', Fddrecordmodel::STATUS_SUCCEED)
            ->pluck('role')
            ->toArray();
        if ($arrToCompare != array_intersect($arrToCompare, $records)) {
            log_message('error', "$contract->id 请先确认双方都已经成功签署了合同");
            return false;
        }
        //调用合同的存档接口
        $res = $this->fadada->contractFiling($contract->contract_id);
        if ($res == false) {
            log_message('error', "$contract->id {$this->fadada->showError()}");
            return false;
        }
        if ($res['code'] != 1000) {
            log_message('error', $contract->id . $res['msg']);
            return false;
        }
        $contract->status = Contractmodel::STATUS_ARCHIVED;
        $contract->save();
        $resident        = $contract->resident;
        $ordersUnhandled = $resident->orders()
            ->whereIn('status', [Ordermodel::STATE_AUDITED, Ordermodel::STATE_PENDING, Ordermodel::STATE_CONFIRM])
            ->count();
        if (0 == $ordersUnhandled) {
            $resident->update(['status' => Residentmodel::STATE_NORMAL]);
            $resident->roomunion->update(['status' => Roomunionmodel::STATE_RENT]);
        }
        return true;
    }

    /**
     * 甲方签章的结果回调
     * 同时进行合同归档
     */
    public function autoSignNotify()
    {
        $this->load->library('form_validation');
        $this->load->library('fadada');
        $this->load->model('fddrecordmodel');

        $config = array(
            array(
                'field' => 'transaction_id',
                'label' => 'transaction_id',
                'rules' => 'required',
            ),
            array(
                'field' => 'contract_id',
                'label' => 'contract_id',
                'rules' => 'required',
            ),
            array(
                'field' => 'result_code',
                'label' => 'result_code',
                'rules' => 'required',
            ),
            array(
                'field' => 'result_desc',
                'label' => 'result_desc',
                'rules' => 'required',
            ),
            array(
                'field' => 'timestamp',
                'label' => 'timestamp',
                'rules' => 'required',
            ),
            array(
                'field' => 'msg_digest',
                'label' => 'msg_digest',
                'rules' => 'required',
            ),
        );

        $this->form_validation->set_rules($config);

        if ($this->form_validation->run() == FALSE) {
            exit('缺少参数!');
        }

        //获取请求参数
        $transactionId = trim($this->input->post('transaction_id', true));
        $contractId    = trim($this->input->post('contract_id', true));
        $resultCode    = trim($this->input->post('result_code', true));
        $resultDesc    = trim($this->input->post('result_desc', true));
        $downloadUrl   = trim($this->input->post('download_url', true));
        $viewpdfUrl    = trim($this->input->post('viewpdf_url', true));
        $timestamp     = trim($this->input->post('timestamp', true));
        $msgDigest     = trim($this->input->post('msg_digest', true));

        try {
            //验证请求可靠性, 通过 msg_digest
            $msgDigestArray = array(
                'md5'  => [$timestamp],
                'sha1' => [config_item('fadada_api_app_secret'), $transactionId],
            );

            $checkMsgDigest = $this->fadada->getMsgDigest($msgDigestArray);

            if ($checkMsgDigest != $msgDigest) {
                throw new Exception('msg_digest 校验不通过');
            }
            $record = Fddrecordmodel::where('transaction_id', $transactionId)->firstOrFail();
            //3000 表示成功, 3001 表示失败
            if ($resultCode == 3000) {
                $status = Fddrecordmodel::STATUS_SUCCEED;
            } else {
                $status = Fddrecordmodel::STATUS_FAILED;
            }
            $record->status = $status;
            $record->remark = $resultDesc;
            $record->save();

            $this->load->model('contractmodel');
            $this->load->model('residentmodel');
            $this->load->model('roomunionmodel');
            $this->load->model('storemodel');
            $this->load->model('ordermodel');

            $contract   = $record->contract;
            if (!$this->signToArchive($contract)) {
                log_message('error',$contract->id.'归档失败');
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
        return true;

    }
}
