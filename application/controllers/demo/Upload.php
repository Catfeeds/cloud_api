<?php

class Upload extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url'));
    }

    public function index()
    {
        $this->load->view('upload_form', array('error' => ' ' ));
    }
 
    /*
        上传例子
        标准文件上传 
        地址：demo/upload/do_upload
        上传后从$this->alioss->data()中获取 oss_path 字段作为完整地址
        地址示例： /2018-04-17/5ad5adcab137b.jpg
        将此地址与cdn_path相拼接得到完整URL
    */
    public function do_upload()
    {
        //$config['upload_path']      = '';
        $config['allowed_types']    = 'gif|jpg|png';
        $config['max_size']     = 5000;
        // $config['max_width']        = 1024;
        // $config['max_height']       = 768;

        $this->load->library('alioss', $config);

        if ( ! $this->alioss->do_upload('userfile'))
        {
            $error = array('error' => $this->alioss->display_errors());
            $this->load->view('upload_form', $error);
        }
        else
        {
            $data = array('upload_data' => $this->alioss->data());

            $this->load->view('upload_success', $data);
        }
    }
}
?>