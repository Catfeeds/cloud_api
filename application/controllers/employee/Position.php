<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        14:46
 * Describe:    员工职位
 */
class Position extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('positionmodel');

    }

    /**
     * 显示编辑职位的信息
     */
    public function getPosition() {
        $post  = $this->input->post(null, true);
        $filed = ['name', 'pc_privilege_ids'];
        if (isset($post['id']) && !empty($post['id'])) {
            $position = Positionmodel::where('id', $post['id'])->first($filed);
            if (!$position) {
                $this->api_res(1009);
                return;
            }
            $this->api_res(0, $position);
        } else {
            $this->api_res(1002);
            return;
        }

    }

    /**
     * 提交已编辑的职位信息
     */
    public function submitPosition() {
        $post = $this->input->post(null, true);
        if (isset($post['id']) && !empty($post['id'])) {
            $position = Positionmodel::find($post['id']);
            if (!$position) {
                $this->api_res(1009);
                return;
            }
        } else {
            $this->api_res(1002);
            return;
        }
        $config = $this->validation();
        if (!$this->validationText($config)) {
            $this->api_res(1002, ['error' => $this->form_first_error(['name'])]);
            return;
        }
        $id          = $post['id'];
        $name        = $post['name'];
        $isNameEqual = Positionmodel::where('company_id', $this->company_id)->where('name', $name)->first();
        if ($isNameEqual && ($isNameEqual->id != $id)) {
            $this->api_res(1014);
            return false;
        }
        $pc_privilege_ids = isset($post['pc_privilege_ids']) ? $post['pc_privilege_ids'] : null;
        //$pc_privilege = isset($post['pc_privilege']) ? $post['pc_privilege'] : null;
        //$mini_privilege = isset($post['mini_privilege']) ? $post['mini_privilege'] : null;
        $position->name             = $name;
        $position->pc_privilege_ids = $pc_privilege_ids;
        //$position->pc_privilege = $pc_privilege;
        //$position->mini_privilege = $mini_privilege;
        $result = $position->save();
        if (!$result) {
            $this->api_res(1009);
            return;
        }
        $this->api_res(0);
    }

    /**
     * 添加职位
     */
    public function addPosition() {
        $post   = $this->input->post(null, true);
        $config = $this->validation();
        if (!$this->validationText($config)) {
            $this->api_res(1002, ['error' => $this->form_first_error(['name'])]);
            return;
        }

        $name        = $post['name'];
        $isNameEqual = Positionmodel::where('company_id', $this->company_id)->where('name', $name)->first();
        if ($isNameEqual) {
            $this->api_res(1014);
            return false;
        }
        $pc_privilege_ids = isset($post['pc_privilege_ids']) ? $post['pc_privilege_ids'] : null;
        //$pc_privilege = isset($post['pc_privilege']) ? $post['pc_privilege'] : null;
        //$mini_privilege = isset($post['mini_privilege']) ? $post['mini_privilege'] : null;

        try {
            DB::beginTransaction();
            $result = Positionmodel::insert(
                ['name'            => $name,
                    'company_id'       => $this->company_id,
                    'pc_privilege_ids' => $pc_privilege_ids,
                    //'pc_privilege' => $pc_privilege,
                    //'mini_privilege' => $mini_privilege,
                    'created_at'       => date('Y-m-d H:i:s', time()),
                    'updated_at'       => date('Y-m-d H:i:s', time()),
                ]);
            if (!$result) {
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
            DB::commit();
            $this->api_res(0);
        } catch (Exception $e) {
            DB::rollBack();
            $this->api_res(1009);
            throw $e;
        }
    }

    /**
     * 职位管理
     */
    public function listPosition() {
        $post   = $this->input->post(null, true);
        $page   = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $filed  = ['id', 'name', 'pc_privilege_ids', 'created_at'];
        $count  = ceil((Positionmodel::where('company_id', $this->company_id)->count()) / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }

        $this->load->model('employeemodel');
        $positions = Positionmodel::with(['employee' => function ($query) {
            $query->where('status', 'ENABLE');
        }])->where('company_id', $this->company_id)
            ->offset($offset)->limit(PAGINATE)->orderBy('created_at', 'asc')
            ->get($filed)->map(function ($a) {
            $a->count_z = $a->employee->count();
            return $a;
        });
        $this->load->model('privilegemodel');
        $position = $positions->map(function ($p) {
            unset($p->employee);
            $pc_privilege_ids = explode(',', $p->pc_privilege_ids);
            $temp_string = '';
            $parent_ids       = Privilegemodel::whereIn('id', $pc_privilege_ids)->groupBy(['parent_id'])->get(['parent_id'])->toArray();
            if($parent_ids){
                $names = Privilegemodel::whereIn('id', $parent_ids)->get(['name'])->toArray();
                foreach ($names as $name) {
                    $temp_string = $temp_string . $name['name'] . " / ";
                }
                $temp_string     = rtrim($temp_string, ' / ');
            }
            $p->pc_privilege = $temp_string;

            return $p;
        });
        $this->api_res(0, ['count' => $count, 'list' => $position]);
    }

    /**
     * 按名称模糊查找
     */
    public function searchPosition() {
        $filed  = ['id', 'name', 'pc_privilege_ids', 'created_at'];
        $post   = $this->input->post(null, true);
        $name   = isset($post['name']) ? $post['name'] : null;
        $page   = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $count  = ceil((Positionmodel::where('company_id', $this->company_id)
                ->where('name', 'like', "%$name%")->count()) / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $this->load->model('employeemodel');
        $positions = Positionmodel::with(['employee' => function ($query) {
            $query->where('status', 'ENABLE');
        }])->where('company_id', $this->company_id)
            ->where('name', 'like', "%$name%")
            ->offset($offset)->limit(PAGINATE)
            ->orderBy('id', 'desc')
            ->get($filed)->map(function ($a) {
            $a->count_z = $a->employee->count();
            return $a;
        });
        $this->load->model('privilegemodel');
        foreach ($positions as $position) {
            $pc_privilege_ids = explode(',', $position->pc_privilege_ids);
            $parent_ids       = Privilegemodel::whereIn('id', $pc_privilege_ids)->groupBy(['parent_id'])->get(['parent_id'])->toArray();
            if (!$parent_ids) {
                $this->api_res(1009);
            }
            $names = Privilegemodel::whereIn('id', $parent_ids)->get(['name'])->toArray();
            if (!$names) {
                $this->api_res(1009);
            }
            $temp_string = '';
            foreach ($names as $name) {
                $temp_string = $temp_string . $name['name'] . " / ";
            }
            $temp_string            = rtrim($temp_string, ' / ');
            $position->pc_privilege = $temp_string;
        }
        $this->api_res(0, ['count' => $count, 'list' => $positions]);
    }

    /**
     * 显示所有权限
     *
    public function showPrivilege()
    {
    $parent_ids = PRIVILEGE_IDS;
    $this->load->model('privilegemodel');
    $privilege= privilegemodel::whereIn('parent_id', $parent_ids)->get(['id', 'name']);
    if (!$parent_ids) {
    $this->api_res(1009);
    return;
    }
    $this->api_res(0, ['pc_privilege' => $privilege]);
    }*/

    /**
     * 显示所有详细权限
     */
    public function showPrivilegeDetail() {
        $this->load->model('privilegemodel');
        $privileges_one = privilegemodel::where('parent_id', 0)->get(['id', 'parent_id', 'name'])->toArray();
        if (!$privileges_one) {
            $this->api_res(1007);
            return;
        }
        foreach ($privileges_one as $key => $privilege_two) {
            $temps = privilegemodel::where('parent_id', $privilege_two['id'])->get(['id', 'parent_id', 'name'])->toArray();
            if (!$temps) {
                continue;
//                $this->api_res(1009);
                //                return;
            }
            foreach ($temps as $k2 => $temp) {
                if ($temp['id'] == 37) {
                    break;
                }

                $res = privilegemodel::where('parent_id', $temp['id'])->get(['id', 'parent_id', 'name'])->toArray();
                if (!$res) {
                    $this->api_res(1009);
                    return;
                }
                $temps[$k2]['list'] = $res;
            }
            $privileges_one[$key]['list'] = $temps;
        }
        $this->api_res(0, $privileges_one);
    }

    /**
     * 显示公司职位
     */
    public function showPositions() {
        $position = Positionmodel::where('company_id', $this->company_id)->get(['id', 'name']);
        if (!$position) {
            $this->api_res(1009);
            return;
        }
        $this->api_res(0, $position);
    }

    /**
     * 删除职位
     */
    public function deletePosition() {
        $id    = $this->input->post('id', true);
        $where = ['company_id' => $this->company_id, 'id' => $id];
        if (Positionmodel::where($where)->delete()) {
            $this->api_res(0);
            return;
        } else {
            $this->api_res(1009);
        }
    }

    /**
     * 验证
     */
    public function validation() {
        $config = array(
            array(
                'field' => 'name',
                'label' => '职位名称',
                'rules' => 'trim|required|max_length[255]',
            ),
        );
        return $config;
    }

    /**
     * 展示门店下职位名称和id
     */
    public function showPosition()
    {
        //当前操作的门店
        $positions  = Positionmodel::where('company_id',$this->company_id)->select(['id','name'])->get();
        $this->api_res(0,$positions);
    }

}
