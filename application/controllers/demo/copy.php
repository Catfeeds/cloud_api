<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Illuminate\Database\Capsule\Manager as DB;
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/6 0006
 * Time:        16:08
 * Describe:
 */
class copy extends MY_Controller
{

    public function run(){
//        $this->transferImages();
        //$this->copy_contract_template();
//       $this->templateToUnionRoom();
//        $this->customerUxid();
//        $this->residentUxid();
//        $this->contractUxid();
//        $this->updateResidentStoreId();
//        $this->orderToNew();
        //$this->orderToBill();
        //$this->billToOrder();


    }



    /**
     * 把图片 images copy到 门店或者房型下
     */
    public function transferImages(){
        
        $this->load->model('imagesmodel');
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');

//        $bimages = Imagesmodel::get(['id','apartment_id','room_type_id','url'])->where('apartment_id',0)->where('room_type_id',0)->groupBy('apartment_id');
//        $images = Imagesmodel::get(['id','apartment_id','room_type_id','url'])->where('apartment_id','>',0)->where('room_type_id',0)->groupBy('apartment_id');
        $images = Imagesmodel::get(['id','apartment_id','room_type_id','url'])->where('apartment_id',0)->where('room_type_id','>',0)->groupBy('room_type_id');




        $array  = [];


        foreach ($images as $key=>$value){
            foreach ($value as $item){
                $array[$key][]=$item->url;
            }

        }


//        foreach ($array as $key=>$value){
//            $store=Storemodel::find($key);
//            $store->images  = $value;
//            //var_dump(($value));exit;
//            $store->save();
//        }



        foreach ($array as $key=>$value){
            $roomtype=Roomtypemodel::find($key);
            if(!$roomtype){
                log_message('error',"no room_type $key");
                continue;
            }
            $roomtype->images  = json_encode($value);
//            var_dump($value);exit;
            $roomtype->save();
        }


        echo 'ok';die();
        //var_dump($array);die();

        var_dump($meger_images->toArray());

        //var_dump($images->toArray());

    }

    /*
     * 把房型的长短租模板copy到模板表 (ok)
     */
    public function copy_contract_template(){
        $this->load->model('contracttemplatemodel');
        $this->load->model('roomunionmodel');
        $this->load->model('roomtypemodel');
        $roomtypes  = Roomtypemodel::all();
        DB::beginTransaction();
        foreach ($roomtypes as $roomtype){
            $roomtype_id    = $roomtype->id;
            $fdd_tpl_id = $roomtype->fdd_tpl_id;
            $contract_tpl_path  = $roomtype->contract_tpl_path;


//            $data   = [
//                'company_id'    => 1,
//                'store_id'      => $roomtype->store_id,
//                'name'          => $roomtype->name,
//                'room_type_id'          => $roomtype->id,
//            ];

            if(!empty($fdd_tpl_id)){
                $fdd_tpl_id = json_decode($fdd_tpl_id,true);
                foreach ($fdd_tpl_id as $key=>$item){
                    $template   = new Contracttemplatemodel();
//                    $template->fill($data);
                    $template->company_id   =1;
                    $template->store_id   =$roomtype->store_id;
                    $template->name   =$roomtype->name;
                    $template->room_type_id   =$roomtype->id;
                    $template->rent_type    = $key;
                    $template->fdd_tpl_id    = $item;
                    $template->save();
                }

            }
            if(!empty($contract_tpl_path)){
                $contract_tpl_path  = json_decode($contract_tpl_path,true);

               // '{"SHORT":{"path":"\/var\/www\/JinDiStrawberry\/apartmentAdmin\/public\/contract_template\/1\/2_SHORT.pdf","url":"http:\/\/admin.funxdata.com\/contract_template\/1\/2_SHORT.pdf"},"LONG":{"path":"\/var\/www\/JinDiStrawberry\/apartmentAdmin\/public\/contract_template\/1\/2_LONG.pdf","url":"http:\/\/admin.funxdata.com\/contract_template\/1\/2_LONG.pdf"}}';

                foreach ($contract_tpl_path as $key=>$item){
                    $template   = Contracttemplatemodel::where('rent_type',$key)->where('room_type_id',$roomtype->id)->first();
                    if(empty($template)){
                        $template   = new Contracttemplatemodel();
                        $template->company_id   =1;
                        $template->store_id   =$roomtype->store_id;
                        $template->name   =$roomtype->name;
                        $template->room_type_id   =$roomtype->id;
                        $template->rent_type    = $key;
                    }

                    $template->contract_tpl_path = $item['path'];
                    $template->url = $item['url'];
                    $template->save();

                }

            }
        }
        DB::commit();
        $this->api_res(0);
        //var_dump($roomtypes);

    }

    /**
     * 把模板 对应到房间表里
     */
    public function templateToUnionRoom(){

        $this->load->model('contracttemplatemodel');
        $this->load->model('roomunionmodel');
        $templates  = Contracttemplatemodel::all();
        foreach ($templates as $template){
            $room_type_id   = $template->room_type_id;

            $rent_type  = $template->rent_type;

            $template_type  = '';
            switch ($rent_type){
                case 'LONG':
                    $template_type   = 'contract_template_long_id';
                    break;
                case 'SHORT':
                    $template_type   = 'contract_template_short_id';
                    break;
                default:
                    $template_type   = 'contract_template_reserve_id';

            }

            $roomunions = Roomunionmodel::where('room_type_id',$room_type_id);

            $roomunions->update([$template_type=>$template->id]);

            $this->api_res(0);
        }
    }

    /**
     * 给customer编uxid
     */
    public function customerUxid(){

        $this->load->model('customermodel');

        $customers  = Customermodel::all();

        foreach ($customers as $customer){
            $customer1   = Customermodel::find($customer->id);
            $customer1->company_id   =1;
            $customer1->uxid = $customer->id;
            $customer1->save();

        }
        $this->api_res(0);

    }

    /**
     * 给resident 的uxid 和company_id 赋值
     */
    public function residentUxid(){

        $this->load->model('residentmodel');

        $resident   = Residentmodel::all()->map(function($query){
            $resident   = Residentmodel::find($query->id);
            $resident->uxid = $resident->customer_id;
            $resident->company_id = 1;

            $resident->save();
        });

        $this->api_res(0);

    }

    /**
     * 合同表里添加customer_id 和uxid
     *
     */
    public function contractUxid(){
        $this->load->model('contractmodel');
        $this->load->model('residentmodel');
        $contracts = Contractmodel::all();

        foreach ($contracts as $contract){

            $resident   = Residentmodel::find($contract->resident_id);

            if(empty($resident)){
                continue;
            }
            $contract->uxid = $resident->uxid;
            $contract->customer_id = $resident->customer_id;
            $contract->save();

//            var_dump($contract->toArray());exit;
        }



        $this->api_res(0);

    }

    /**
     * 更新resident 表里的 store_id
     */

    public function updateResidentStoreId(){

        $this->load->model('roomunionmodel');
        $this->load->model('residentmodel');

        $residents  = Residentmodel::all();
        foreach ($residents as $resident){
            if($resident->roomunion){
                $resident->store_id = $resident->roomunion->store_id;
                $resident->save();
            }

            //var_dump($resident->roomunion->store_id);exit;

        }
        echo 'ok';

    }

    /**
     * 订单转入新的订单表
     */
    public function orderToNew()
    {
        $this->load->model('ordermodel');
        $this->load->model('testordermodel');

//        $numbers    = Ordermodel::groupBy('number')->where('sequence_number','>',0)->get(['number'])->map(function($a){
//            return $a->number;
//        });

        //一次1000条number 大概6000条
        $numbers    = Ordermodel::offset(5000)->limit(1000)->groupBy('created_at')->get(['created_at'])->map(function($q){
            return $q->created_at;
        });

        try{
            DB::beginTransaction();

            foreach ($numbers as $number){


                $pre_number = date('YmdHis',strtotime($number));
                $orders = Ordermodel::where('created_at',$number)->get()->toArray();
                $count  = count($orders);

                for($i=0;$i<$count;$i++){
                    $a=new Testordermodel();
                    $to_number = sprintf('%010s',($i+1));
                    $number=$pre_number.$to_number;
                    $a->id=$orders[$i]['id'];
                    $a->number  = $number;
                    $a->old_number  = $orders[$i]['number'];
                    $a->store_id  = $orders[$i]['store_id'];
                    $a->room_type_id  = $orders[$i]['room_type_id'];
                    $a->room_id  = $orders[$i]['room_id'];
                    $a->employee_id  = $orders[$i]['employee_id'];
                    $a->resident_id  = $orders[$i]['resident_id'];
                    $a->customer_id  = $orders[$i]['customer_id'];
                    $a->uxid  = $orders[$i]['customer_id'];
                    $a->type  = $orders[$i]['type'];
                    $a->year  = $orders[$i]['year'];
                    $a->month  = $orders[$i]['month'];
                    $a->money  = $orders[$i]['money'];
                    $a->paid  = $orders[$i]['paid'];
                    $a->status  = $orders[$i]['status'];
                    $a->deal  = $orders[$i]['deal'];
                    $a->pay_date  = $orders[$i]['pay_date'];
                    $a->remark  = $orders[$i]['remark'];
                    $a->created_at  = $orders[$i]['created_at'];
                    $a->updated_at  = $orders[$i]['updated_at'];
                    $a->deleted_at  = $orders[$i]['deleted_at'];
                    $a->discount_money  = $orders[$i]['discount_money'];
                    $a->pay_status  = $orders[$i]['pay_status'];
                    $a->data  = $orders[$i]['data'];
                    $a->save();

                }
            }

            DB::commit();
        }catch (Exception $e){
            DB::rollBack();
            throw  $e;
        }


        $this->api_res(0);

    }

    /**
     * 订单转入新的流水表
     */
    public function orderToBill(){

//        $this->load->model('ordermodel');
//        $this->load->model('testbillmodel');
//
//       /* $bills  = Ordermodel::where('sequence_number','>',0)->groupBy(['updated_at'])
//            ->get(['pay_date','number','sequence_number'])->groupBy('number');*/
//
////        $bills  = Ordermodel::where('sequence_number','>',0)->groupBy(['pay_date','number'])
////            ->get(['pay_date','number']);
//
////
//        $bills  = Ordermodel::whereIn('status',['CONFIRM','COMPLATE'])->groupBy(['new_at'=>function($query){
//
//            $query->new_at  = substr($query->updated_at,0,16);
//        },'resident_id','pay_type'])->get()/*->map(function($query){
//            //var_dump($query->toArray());exit;
//            $a=$query->toArray();
//            $at = $a['updated_at'];
//
//            $new_at = substr($at,0,16);
//
//            //echo $new_at;exit;
//            $query->new_at  =$new_at;
//            return $query;
//        })*/;
//            //->groupBy('updated_at','resident_id','pay_type');
////        $bills  = $bills->groupBy([function($bill){
////            return substr($bill['updated_at'],0,16);
////        }]);
//
//        var_dump(count($bills));
//
//
//
//
//        //var_dump($bills->toArray());exit;
//
//        //var_dump($bills->count());exit;
//
//        exit;
//        try{
//            DB::beginTransaction();;
//
//
//        }catch (Exception $e){
//            DB::rollBack();
//            throw $e;
//        }

        $this->load->model('testbillmodel');

        $bills  = Testbillmodel::all()->groupBy(function($item){
            return substr($item['sequence_number'],0,10);
        });

        //var_dump($bills->toArray());exit;

        $arr    = $bills->toArray();

        foreach ($arr as $key=>$value){
            $date   = date('Ymd',strtotime($key));
            $count  = count($value);
            for ($i=0;$i<$count;$i++){
                $a= Testbillmodel::find($value[$i]['id']);
                $new_number = $date.sprintf('%06s',$i+1);
                $a->sequence_number = $new_number;
                $time_at    =$a->updated_at;
                $a->updated_at  = $time_at;
                $a->save();
            }

        }
        $this->api_res(0);


    }

    public function billToOrder(){

        $this->load->model('testbillmodel');
        $this->load->model('testordermodel');
        $this->load->model('ordermodel');

        $bills  = Testbillmodel::all()->toArray();

        $orders = Testordermodel::all()->map(function($query){
            $query->new_at  = substr($query->updated_at,0,16);
            var_dump($query);exit;

            return $query;
        });

        foreach ($bills as $bill){
            //$old_at = $bill['updated_at_copy'];
            //echo $old_at;exit;
            //var_dump($bill);exit;
            $bill_at    = substr($bill['updated_at_copy'],0,16);
            $resident_id    = $bill['resident_id'];
            //var_dump($bill_at);exit;


        }

        /*$orders = Ordermodel::all()->map(function($query){
            $query->new_at  = substr($query->updated_at,0,16);
            //var_dump($query->toArray());exit;
        });*/

    }



}