<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/9
 * Time: 16:35
 */
class Employeestoremodel extends Basemodel {
    protected $table = 'boss_res_employee_store';

    /*
     *获取权限
     */
    public function getStoreIds(){
        $position = Employeemodel::wherehas('position',function($query){
            $query->where('company_id', '0');
        })->where('id', get_instance()->current_id)->select()->first();
        if($position){
            $this->store_ids = Storemodel::where('company_id', get_instance()->company_id)->get(['id'])
                               ->map(function($query){
                               $query = $query->toArray();
                               return $query['id'];
                              });
            return $this;
        }
        $employee_store = Employeestoremodel::where('employee_id', get_instance()->current_id)->get(['store_id'])
            ->map(function($query){
                $query = $query->toArray();
                return $query['store_id'];
            });
        $this->store_ids = $employee_store;
        return $this;
    }
   /*
    * 添加权限
   */
    public function insertEmployeeStore($store_ids, $employee_id, $position_id){
        $store_arr = explode(',', $store_ids);
        Employeestoremodel::whereNotin('store_id', $store_arr)->where('employee_id', $employee_id)->delete();
        foreach ($store_arr as $value){
            $employee_store_select = Employeestoremodel::where('store_id', $value)->where('employee_id', $employee_id)->select(['position_id'])->first();
            if($employee_store_select){
                if($employee_store_select->position_id == $position_id) {
                    continue;
                }
                Employeestoremodel::where('store_id', $value)->where('employee_id' , $employee_id)
                    ->update(['position_id' => $position_id]);
                continue;
            }
            $data[] = [
                'store_id'    => $value,
                'employee_id' => $employee_id,
                'position_id' => $position_id,
            ];
        }
        if(!empty($data)){
            Employeestoremodel::insert($data);
        }
    }

}
