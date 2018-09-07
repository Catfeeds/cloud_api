<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      hfq<1326432154@qq.com>
 * Date:        2018/6/8
 * Time:        21:57
 * Describe:    调价
 */
class Pricecontrol extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('roomunionmodel');
    }

    /**
     * 调价列表
     */
    public function priceControl() {
        $this->load->model('storemodel');
        $this->load->model('buildingmodel');
        $this->load->model('roomtypemodel');

        $post      = $this->input->post(null, true);
        $page      = isset($post['page']) ? intval($post['page']) : 1;
        $offset    = PAGINATE * ($page - 1);
        $filed     = ['id', 'store_id', 'building_id', 'number', 'room_type_id', 'rent_price', 'property_price', 'updated_at'];
        $where     = [];
        $store_ids = explode(',', $this->employee->store_ids);
        if (!empty($post['store_id'])) {$where['store_id'] = intval($post['store_id']);};
        if (!empty($post['building_id'])) {$where['building_id'] = intval($post['building_id']);};
        if (!empty($post['number'])) {$where['number'] = trim($post['number']);};

        $count = $count = ceil(Roomunionmodel::where($where)->whereIn('store_id', $store_ids)->count() / PAGINATE);
        if ($page > $count || $page < 1) {
            $this->api_res(0, ['list' => []]);
            return;
        } else {
            $price = Roomunionmodel::orderBy('number')->with('store_s')->with('building_s')->with('room_type')
                ->where($where)->whereIn('store_id', $store_ids)
                ->take(PAGINATE)->skip($offset)->get($filed)
                ->map(function ($s) {
                    $s->updated = date('Y-m-d', strtotime($s->updated_at->toDateTimeString()));
                    return $s;
                })->toArray();

            $this->api_res(0, ['list' => $price, 'count' => $count]);
        }
    }

    /**
     *  调价(物业房租)
     */
    public function rentPrice() {
        $post = $this->input->post(null, true);
        if (!$this->validation()) {
            $fieldarr = ['rent_price', 'property_price'];
            $this->api_res(1002, ['errmsg' => $this->form_first_error($fieldarr)]);
            return false;
        }
        if ($post['id']) {
            $id                    = intval($post['id']);
            $rent_price            = $post['rent_price'];
            $property_price        = $post['property_price'];
            $price                 = Roomunionmodel::findorFail($id);
            $price->rent_price     = $rent_price;
            $price->property_price = $property_price;
            if ($price->save()) {
                $this->api_res(0, []);
            } else {
                $this->api_res(1009);
            }
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * 水电价格
     */
    public function utilities() {
        $this->load->model('storemodel');
        $post = $this->input->post(null, true);
        if ($post['store_id']) {
            $store_id = intval($post['store_id']);
            $price    = Storemodel::where('id', $store_id)->get(['water_price', 'hot_water_price', 'electricity_price'])->toArray();
            $this->api_res(0, $price);
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * 调价（水电）
     */
    public function changeUtility() {
        $this->load->model('storemodel');
        $post = $this->input->post(null, true);
        if (isset($post['hot_water_price'])) {$h_price = trim($post['hot_water_price']);}
        if (isset($post['water_price'])) {$c_price = trim($post['water_price']);}
        if (isset($post['electricity_price'])) {$e_price = trim($post['electricity_price']);}

        if ($post['store_id']) {
            $store_id                 = intval($post['store_id']);
            $price                    = Storemodel::where('id', $store_id)->first();
            $price->hot_water_price   = $h_price;
            $price->water_price       = $c_price;
            $price->electricity_price = $e_price;
            if ($price->save()) {
                $this->api_res(0);
            } else {
                $this->api_res(1009);
            }
        } else {
            $this->api_res(1002);
        }
    }

    /**
     * @return mixed
     * 表单验证
     */
    private function validation() {
        $this->load->library('form_validation');
        $config = array(
            array(
                'field' => 'rent_price',
                'label' => '住宿费',
                'rules' => 'trim|required',
            ),
            array(
                'field' => 'property_price',
                'label' => '物业费',
                'rules' => 'trim|required',
            ),
        );

        $this->form_validation->set_rules($config)->set_error_delimiters('', '');
        return $this->form_validation->run();
    }

    /*********************************************** zjh ***********************************************/

    /**
     * 创建调价
     */
    public function create()
    {
        $field = ['room_id', 'type', 'new_price','remark'];
        if (!$this->validationText($this->validateCreate())) {
            $this->api_res(1002, ['error' => $this->form_first_error($field)]);
            return;
        }
        $input  = $this->input->post(null,true);
        //查找房间，判断权限
        $this->load->model('roomunionmodel');
        $this->load->model('storemodel');
        $this->load->model('pricecontrolmodel');
        $room   = Roomunionmodel::find($input['room_id']);
        $store_id   = $room->store_id;
        $store  = Storemodel::find($store_id);
        $e_store_ids    = explode(',',$this->employee->store_ids);
        if (!in_array($store_id,$e_store_ids)) {
            $this->api_res(1019);
            return;
        }

        $data   = [
            'company_id'=> COMPANY_ID,
            'store_id'  => $store_id,
            'room_id'   => $room->id,
            'type'      => $input['type'],
            'new_price' => $input['new_price'],
            'remark'    => $input['remark'],
            'employee_id'   => $this->employee->id,
            'created_at'    => Carbon::now()->toDateTimeString(),
            'updated_at'    => Carbon::now()->toDateTimeString(),
        ];


        switch ($input['type']) {
            case Pricecontrolmodel::TYPE_ROOM:
                $data['ori_price']  = empty($room->rent_price)?0:$room->rent_price;
                break;
            case Pricecontrolmodel::TYPE_MANAGEMENT:
                $data['ori_price']  = empty($room->property_price)?0:$room->property_price;
                break;
            case Pricecontrolmodel::TYPE_ELECTRICITY:
                $data['ori_price']  = empty($room->electricity_price)?0:$room->electricity_price;
                break;
            case Pricecontrolmodel::TYPE_WATER:
                $data['ori_price']  = empty($room->cold_water_price)?0:$room->cold_water_price;
                break;
            case Pricecontrolmodel::TYPE_HOTWATER:
                $data['ori_price']  = empty($room->hot_water_price)?0:$room->hot_water_price;
                break;
            default:
                break;
        }
        //判断该公司有没有调价审批模板
        $this->load->model('taskflowtemplatemodel');
        $taskflow_template = Taskflowtemplatemodel::where('type', Taskflowtemplatemodel::TYPE_PRICE)->first();
        try{
            DB::beginTransaction();
            if (!$taskflow_template) {
                //执行调价
                if($this->doChangePrice($room,$input['type'],$input['new_price'])){
                    $data['status']=Pricecontrolmodel::STATE_DONE;
                }
            } else {
                $this->load->model('taskflowmodel');
                //判断有无正在审核的调价记录
                $p  = Pricecontrolmodel::where([
                    'status'    => Pricecontrolmodel::STATE_AUDIT,
                    'type'      => $input['type'],
                    'room_id'   => $input['room_id']
                ])->exists();
                if($p){
                    $this->api_res(11203);
                    return;
                }
                //创建调价任务流
                $msg_data   = [
                    'store_name'    => $store->name,
                    'number'        => $room->number,
                    'create_name'   => $this->employee->name,
                    'type'          => $input['type'],
                    'money'         => $input['new_price'],
                ];
                $msg    = json_encode($msg_data);
                $taskflow_id    = $this->taskflowmodel->createTaskflow(COMPANY_ID,Taskflowmodel::TYPE_PRICE,$store_id,$input['room_id'],Taskflowmodel::CREATE_EMPLOYEE,$this->employee->id,null,null,$msg);
                if ($taskflow_id) {
                    $data['taskflow_id']    = $taskflow_id;
                    $data['status']         = Pricecontrolmodel::STATE_AUDIT;
                }
            }
            //创建调价记录
            $price_id   = Pricecontrolmodel::insertGetId($data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0,$price_id);
    }


    /**
     * 执行调价
     */
    private function doChangePrice($room,$type,$new_price)
    {
        switch ($type) {
            case Pricecontrolmodel::TYPE_ROOM;
                $room->rent_price   = $new_price;
                break;
            case Pricecontrolmodel::TYPE_MANAGEMENT;
                $room->property_price   = $new_price;
                break;
            case Pricecontrolmodel::TYPE_ELECTRICITY;
                $room->electricity_price = $new_price;
                break;
            case Pricecontrolmodel::TYPE_WATER;
                $room->cold_water_price = $new_price;
                break;
            case Pricecontrolmodel::TYPE_HOTWATER;
                $room->hot_water_price  = $new_price;
                break;
            default;
                break;
        }
        return $room->save();
    }


    /**
     * validate
     */
    private function validateCreate()
    {
        return array(
            array(
                'field' => 'room_id',
                'label' => '房间id',
                'rules' => 'trim|required|integer',
            ),
            array(
                'field' => 'type',
                'label' => '调价范围（房租或者物业费）',
                'rules' => 'trim|required|in_list[ROOM,MANAGEMENT]',
                'errors'=> [
                    'required'  => '请选择%s',
                ]
            ),
            array(
                'field' => 'new_price',
                'label' => '新的金额',
                'rules' => 'trim|required|numeric',
                'errors'=> [
                    'required'  => '请填写%s',
                    'numeric'   => '请填写正确的金额'
                ]
            ),
            array(
                'field' => 'remark',
                'label' => '调价原因',
                'rules' => 'trim|required',
                'errors'=> [
                    'required'  => '请填写%s',
                ]
            ),
        );
    }

    /**
     * 批量水电调价
     */
    public function batchCreate()
    {
        $input  = $this->input->post(null,true);
        $field  = ['store_id','community_id','electricity_price','cold_water_price','hot_water_price'];
        if (!$this->validationText($this->validateBatchCreate())) {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        if (empty($input['store_id'])) {
            $this->api_res('1002');
            return;
        }
        $where  = [];
        if (!empty($input['community_id'])) {
            $community_id   = $input['community_id'];
            $where['community_id']  = $community_id;
        } else {
            $community_id   = null;
        }
        $remark = empty($input['reamrk'])?'无':$input['remark'];
        $electricity_price  = $input['electricity_price'];
        $cold_water_price   = $input['cold_water_price'];
        $hot_water_price    = $input['hot_water_price'];
        $update_arr = [
            'electricity_price' => $electricity_price,
            'cold_water_price'  => $cold_water_price,
            'hot_water_price'   => $hot_water_price
        ];
        $this->load->model('pricecontrolmodel');
        $rooms  = Roomunionmodel::where('store_id',$input['store_id'])->where($where)->get();
        $records    = [];
        foreach ($rooms as $room) {
            $records[]  = [
                'company_id'    => $room->company_id,
                'store_id'      => $input['store_id'],
                'community_id'  => $community_id,
                'room_id'       => $room->id,
                'type'          => Pricecontrolmodel::TYPE_ELECTRICITY,
                'status'        => Pricecontrolmodel::STATE_DONE,
                'employee_id'   => $this->employee->id,
                'ori_price'     => $room->electricity_price,
                'new_price'     => $electricity_price,
                'remark'        => $remark,
                ];
            $records[]  = [
                'company_id'    => $room->company_id,
                'store_id'      => $input['store_id'],
                'community_id'  => $community_id,
                'room_id'       => $room->id,
                'type'          => Pricecontrolmodel::TYPE_WATER,
                'status'        => Pricecontrolmodel::STATE_DONE,
                'employee_id'   => $this->employee->id,
                'ori_price'     => $room->cold_water_price,
                'new_price'     => $cold_water_price,
                'remark'        => $remark,
            ];
            $records[]  = [
                'company_id'    => $room->company_id,
                'store_id'      => $input['store_id'],
                'community_id'  => $community_id,
                'room_id'       => $room->id,
                'type'          => Pricecontrolmodel::TYPE_HOTWATER,
                'status'        => Pricecontrolmodel::STATE_DONE,
                'employee_id'   => $this->employee->id,
                'ori_price'     => $room->hot_water_price,
                'new_price'     => $hot_water_price,
                'remark'        => $remark,
            ];
        }
        $this->load->model('pricecontrolmodel');
        try {
            DB::beginTransaction();
            Roomunionmodel::where('store_id',$input['store_id'])->where($where)->update($update_arr);
            Pricecontrolmodel::insert($records);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        $this->api_res(0);
    }

    /**
     * 批量修改参数验证
     */
    private function validateBatchCreate()
    {
        return [
            [
                'field' => 'store_id',
                'label' => '门店',
                'rules' => 'trim|required',
            ],
            [
                'field' => 'community_id',
                'label' => '小区',
                'rules' => 'trim',
            ],
            [
                'field' => 'electricity_price',
                'label' => '电费单价',
                'rules' => 'trim|required',
            ],
            [
                'field' => 'cold_water_price',
                'label' => '冷水单价',
                'rules' => 'trim|required',
            ],
            [
                'field' => 'hot_water_price',
                'label' => '热水价格',
                'rules' => 'trim|required',
            ],
        ];
    }

}
