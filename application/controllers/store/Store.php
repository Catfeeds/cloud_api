<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/26 0026
 * Time:        16:47
 * Describe:    门店管理
 */
class Store extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        /*if(!$this->position){
            throw new Exception();
        }*/
        $this->load->model('storemodel');
    }

    /**
     * 房间列表
     * post page 页码
     * return count 总页数，list 门店列表
     */
    public function listStore()
    {
        $post   = $this->input->post(null,true);
        $page   = isset($post['page'])?$post['page']:1;
        $offset = PAGINATE*($page-1);
        $field  = ['id','name','city','rent_type','address','contact_user','contact_phone','status'];
        $count  = ceil(Storemodel::count()/PAGINATE);
        if($page>$count){
            throw new Exception();
        }
        $stores = Storemodel::offset($offset)->limit(PAGINATE)->orderBy('id','desc')->get($field)->toArray();
        $this->api_res(0,['count'=>$count,'list'=>$stores]);
    }

    /**
     * 删除门店
     */
    public function deleteStore(){
        $store_id   = $this->input->post('store_id',true);
        if(Storemodel::find($store_id)->delete()){
            $this->api_res(0);
        }
    }

    /**
     * 查找门店
     */
    public function searchStore(){

    }

    /**
     * 添加分布式门店
     */
    public function addStoreDot()
    {
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'counsel_phone','counsel_time','images','describe'
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post    = $this->input->post(null,true);
        $insert  = new Storemodel();
        $insert->fill($post);
        $insert->save();
        $this->api_res(0,['store_id'=>$insert->id]);
    }

    /**
     * 添加集中式门店
     */
    public function addStoreUnion()
    {
        $field  = [
            'rent_type','status','name','theme','province','city','district','address', 'contact_user',
            'contact_phone','counsel_phone','counsel_time','images','describe','history','shop','relax','bus'
        ];
        if(!$this->validationText($this->validationAddConfig()))
        {
            $this->api_res(1002,['error'=>$this->form_first_error($field)]);
            return;
        }
        $post   = $this->input->post(null,true);
        $insert  = new Storemodel();
        $insert->fill($post);
        $insert->save();
        $this->api_res(0);
    }


    /**
     * 添加门店的验证规则
     */
    public function validationAddConfig()
    {

        $config = [
            array(
                'field' => 'name',
                'label' => '门店名',
                'rules' => 'required|trim|max_length[20]',
                'errors'=> array(
                    'required' => '门店名不能为空.',
                )
            ),
            array(
                'field' => 'rent_type',
                'label' => '门店类型',
                'rules' => 'required|trim|in_list[DOT,UNION]',
            ),
            array(
                'field' => 'status',
                'label' => '门店状态',
                'rules' => 'required|trim|in_list[NORMAL,CLOSE,WAIT]',
            ),
            array(
                'field' => 'theme',
                'label' => '门店主题',
                'rules' => 'trim',
            ),
            array(
                'field' => 'province',
                'label' => '省份',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'city',
                'label' => '城市',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'district',
                'label' => '区',
                'rules' => 'trim',
            ),
            array(
                'field' => 'address',
                'label' => '地址',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'contact_user',
                'label' => '联系人',
                'rules' => 'required|trim|min_length[2]|max_length[6]',
            ),
            array(
                'field' => 'counsel_phone',
                'label' => '咨询电话',
                'rules' => 'required|trim|max_length[14]',
            ),
            array(
                'field' => 'counsel_time',
                'label' => '咨询时间',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'images',
                'label' => '门店图片',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'describe',
                'label' => '门店描述',
                'rules' => 'required|trim',
            ),
            array(
                'field' => 'history',
                'label' => '配套医院',
                'rules' => 'trim',
            ),
            array(
                'field' => 'shop',
                'label' => '配套商场',
                'rules' => 'trim',
            ),
            array(
                'field' => 'relax',
                'label' => '配套休闲',
                'rules' => 'trim',
            ),
            array(
                'field' => 'bus',
                'label' => '配套交通',
                'rules' => 'trim',
            ),

        ];
        return $config;
    }


}
