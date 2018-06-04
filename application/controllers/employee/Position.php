<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/14 0014
 * Time:        14:46
 * Describe:    员工职位
 */
class Position extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('positionmodel');

    }

    /**
     * 显示编辑职位的信息
     */
    public function getPosition()
    {
        $post = $this->input->post(null,true);
        if (isset($post['id']) && !empty($post['id'])) {
            $position = Positionmodel::find($post['id']);
            if (!$position) {
                $this->api_res(1009);
                return;
            }
            $category = ['name' => $position->name,
                        'pc_privilege_ids' => $position->pc_privilege_ids,
                        'pc_privilege' => $position->pc_privilege
                        //'mini_privilege' => $position->mini_privilege
                        ];
            $this->api_res(0, $category);
        } else {
            $this->api_res(1002);
            return;
        }

    }

    /**
     * 提交已编辑的职位信息
     */
    public function submitPosition()
    {
        $post = $this->input->post(null,true);
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
        if(!$this->validationText($config))
        {
            $this->api_res(1002,['error'=>$this->form_first_error(['name'])]);
            return ;
        }
        $id = $post['id'];
        $name = $post['name'];
        $isNameEqual = Positionmodel::where('name', $name)->first();
        if ($isNameEqual && ($isNameEqual->id != $id)) {
            $this->api_res(1009, ['error' => '职位已存在']);
            return false;
        }
        $pc_privilege_ids = isset($post['pc_privilege_ids']) ? $post['pc_privilege_ids'] : null;
        $pc_privilege = isset($post['pc_privilege']) ? $post['pc_privilege'] : null;
        //$mini_privilege = isset($post['mini_privilege']) ? $post['mini_privilege'] : null;
        $position->name = $name;
        $position->pc_privilege_ids = $pc_privilege_ids;
        $position->pc_privilege = $pc_privilege;
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
    public function addPosition()
    {
        $post = $this->input->post(null, true);
        $config = $this->validation();
        if(!$this->validationText($config))
        {
            $this->api_res(1002,['error'=>$this->form_first_error(['name'])]);
            return;
        }

        $name = $post['name'];
        $isNameEqual = Positionmodel::where('name', $name)->first();
        if ($isNameEqual) {
            $this->api_res(1009, ['error' => '职位已存在']);
            return false;
        }
        $pc_privilege_ids = isset($post['pc_privilege_ids']) ? $post['pc_privilege_ids'] : null;
        $pc_privilege = isset($post['pc_privilege']) ? $post['pc_privilege'] : null;
        //$mini_privilege = isset($post['mini_privilege']) ? $post['mini_privilege'] : null;

        try {
            DB::beginTransaction();
            $result = Positionmodel::insert(
                [   'name' => $name,
                    'company_id' => COMPANY_ID,
                    'pc_privilege_ids' => $pc_privilege_ids,
                    'pc_privilege' => $pc_privilege,
                    //'mini_privilege' => $mini_privilege,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time())
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
    public function listPosition()
    {
        $post = $this->input->post(null, true);
        $page = intval(isset($post['page']) ? $post['page'] : 1);
        $offset = PAGINATE * ($page - 1);
        $filed = ['id', 'name', 'pc_privilege_ids', 'pc_privilege', 'created_at'];
        $count = ceil((Positionmodel::all()->count()) / PAGINATE);
        if ($page > $count) {
            $this->api_res(0, ['count' => $count, 'list' => []]);
            return;
        }
        $this->load->model('employeemodel');
        $category = Positionmodel::with('employee')
            ->offset($offset)->limit(PAGINATE)->orderBy('created_at', 'asc')
            ->get($filed)->map(function($a){
                $a->count_z = $a->employee->count();
                return  $a;
            });

        $this->api_res(0, ['count' => $count, 'list' => $category]);
    }

    /**
     * 按名称模糊查找
     */
    public function searchPosition()
    {
        $filed = ['id', 'name', 'pc_privilege_ids', 'pc_privilege', 'created_at'];
        $post   = $this->input->post(null,true);
        $config = $this->validation();
        if(!$this->validationText($config))
        {
            $this->api_res(1002,['error'=>$this->form_first_error(['name'])]);
            return ;
        }
        $name   = isset($post['name'])?$post['name']:null;
        $page   = intval(isset($post['page'])?$post['page']:1);
        $offset = PAGINATE * ($page-1);
        $count  = ceil((Positionmodel::where('name','like',"%$name%")->count())/PAGINATE);
        if($page > $count){
            $this->api_res(0,['count'=>$count,'list'=>[]]);
            return;
        }
        $this->load->model('employeemodel');
        $category = Positionmodel::with('employee')
            ->where('name','like',"%$name%")
            ->offset($offset)->limit(PAGINATE)
            ->orderBy('id', 'desc')
            ->get($filed)->map(function($a){
                $a->count_z = $a->employee->count();
                return $a;
            });
        $this->api_res(0,['count'=>$count,'list'=>$category]);
    }

    /**
     * 显示所有权限
     */
    public function showPrivilege()
    {
        $this->load->model('privilegemodel');
        $parent_ids= privilegemodel::where('parent_id', 0)->get(['id'])->map(function ($p) {
            return $p->id;
        });
        if (!$parent_ids) {
            $this->api_res(1009);
            return;
        }
        $privilege= privilegemodel::whereIn('parent_id', $parent_ids)->get(['id', 'name']);
        if (!$parent_ids) {
            $this->api_res(1009);
            return;
        }
        $this->api_res(0, ['pc_privilege' => $privilege]);
    }

    /**
     * 显示所有职位
     */
    public function showPositions()
    {
        $position = Positionmodel::get(['id', 'name']);
        if (!$position) {
            $this->api_res(1009);
            return;
        }
        $this->api_res(0, $position);
    }

    /**
     * 删除职位
     */
    public function deletePosition()
    {
        $id   = $this->input->post('id',true);
        $where  = ['id' => $id];
        if(Positionmodel::where($where)->delete()){
            $this->api_res(0);
            return;
        }else{
            $this->api_res(1009);
        }
    }

    /**
     * 验证
     */
    public function validation()
    {
        $config = array(
            array(
                'field' => 'name',
                'label' => '职位名称',
                'rules' => 'trim|required|max_length[255]',
            ),
        );
        return $config;
    }

}