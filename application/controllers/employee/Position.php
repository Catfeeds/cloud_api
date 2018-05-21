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

    public function editPosition()
    {
        $post = $this->input->post(null,true);

        if (isset($post['id']) && !empty($post['id'])) {
            $position = Positionmodel::find($post['id']);
            if (!$position) {
                $this->api_res(1009);
                return;
            }
            if (isset($post['name']) && !empty($post['name'])) {
                if ($post['name'] == $position->name) {
                    $pc_privilege = isset($post['pc_privilege']) ? $post['pc_privilege'] : null;
                    $mini_privilege = isset($post['mini_privilege']) ? $post['mini_privilege'] : null;
                    $position->pc_privilege = $pc_privilege;
                    $position->mini_privilege = $mini_privilege;
                    $result = $position->save();
                    if (!$result) {
                        $this->api_res(1009);
                        return;
                    }
                    $this->api_res(0);
                } else {
                    $this->api_res(10101);
                    return;
                }
            } else {
                $category  = [$position->name, $position->pc_privilege, $position->mini_privilege];
                $this->api_res(0, $category);
            }
        } else {
            $this->api_res(10101);
            return;
        }

    }

    public function addPosition()
    {
        $post = $this->input->post(null,true);
        $name = isset($post['name']) ? $post['name'] : null;
        $pc_privilege = isset($post['pc_privilege']) ? $post['pc_privilege'] : null;
        $mini_privilege = isset($post['mini_privilege']) ? $post['mini_privilege'] : null;

        try{
            DB::beginTransaction();
            $result = positionmodel::insert(
                [   'name' => $name,
                    'pc_privilege' => $pc_privilege,
                    'mini_privilege' => $mini_privilege,
                    'created_at' => date('Y-m-d H:i:s',time()),
                    'updated_at' => date('Y-m-d H:i:s',time())
                ]);
            if(!$result) {
                DB::rollBack();
                $this->api_res(1009);
                return;
            }
            DB::commit();
            $this->api_res(0);
        }catch (Exception $e){
            DB::rollBack();
            throw $e;
        }

    }


}