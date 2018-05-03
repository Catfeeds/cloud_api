<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/27 0027
 * Time:        10:31
 * Describe:    公共接口
 */


class Common extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 上传图片接口
     * 传入字段名为image
     */
    public function imageUpload(){
        $config = [
            'allowed_types' => 'gif|jpg|png|jpeg',
            'max_size'  => 4*1024,
        ];
        $this->load->library('alioss',$config);
        if(!$this->alioss->do_upload('image'))
        {
            $this->api_res(1004,array('error' => $this->alioss->display_errors('','')));
        }else
        {
            $oss_path   = $this->alioss->data()['oss_path'];
            $this->api_res(0,['image_url'=>config_item('cdn_path').$oss_path]);
        }
    }

    /*
     * 上传文件
     */
    public function fileUpload(){
        $config = [
            'allowed_types' => 'pdf|doc',
            'max_size'  => 4*1024,
        ];
        $this->load->library('alioss',$config);
        if(!$this->alioss->do_upload('file'))
        {
            $this->api_res(1004,array('error' => $this->alioss->display_errors('','')));
        }else
        {
            $oss_path   = $this->alioss->data()['oss_path'];
            $this->api_res(0,['file_url'=>config_item('cdn_path').$oss_path]);
        }
    }

}
