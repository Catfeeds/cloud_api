<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/5/25 0025
 * Time:        17:03
 * Describe:    微信公众号推送模板
 */
class Pushtemplate
{
    //例子:模板1
    public function template1() {
        $template = [
            'title'         => '停水通知',
            'type'          => 2,
            'template_id'   => 'OhCKlytLt8bUCiP9xhNFNtq1NmV_KbLBBuyS7EJGnSk',
            'in_use'        => 1,
            'content'       => [
                [
                    'field'     => 'name',
                    'label'     => '标题',
                    'rules'     => 'trim|required',
                ],
                [
                    'field'     => 'name',
                    'label'     => '首段提醒',
                    'rules'     => 'trim|required',
                ],
                [
                    'field'     => 'name',
                    'label'     => '停水时间',
                    'rules'     => 'trim|required',
                ],
                [
                    'key'       => 'remark',
                    'name'      => '备注说明',
                    'required'  => true,
                ],

            ]
        ];
        return $template;
    }

}