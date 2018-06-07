<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/6 0006
 * Time:        16:08
 * Describe:
 */
class copy extends MY_Controller
{

    public function run(){
       // $this->copy_contract_template();
       // $this->templateToUnionRoom();
    }

    /**
     * 把图片 images copy到 门店或者房型下
     */
    public function transferImages(){
        
        $this->load->model('imagesmodel');
        $this->load->model('storemodel');
        $this->load->model('roomtypemodel');

        //$images = Imagesmodel::get(['id','apartment_id','room_type_id','url'])->where('apartment_id',0)->where('room_type_id',0)->groupBy('apartment_id');
        //$images = Imagesmodel::get(['id','apartment_id','room_type_id','url'])->where('apartment_id','>',0)->where('room_type_id',0)->groupBy('apartment_id');
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
//            $store->save();
//        }



        foreach ($array as $key=>$value){
            $roomtype=Roomtypemodel::find($key);
            if(!$roomtype){
                log_message('error',"no room_type $key");
                continue;
            }
            $roomtype->images  = $value;
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

                    $template->fdd_tpl_path = $item['path'];
                    $template->url = $item['url'];
                    $template->save();

                }

            }
        }

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



}