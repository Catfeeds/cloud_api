<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Author:      weijinlong
 * Date:        2018/4/19
 * Time:        09:11
 * Describe:    [未完成]通过在框架早期植入各种基础校验来提升性能
 */

class PreHook {

    public function proc() {
        //跨域访问
        header("Access-Control-Allow-Origin: * ");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Token");
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

        //允许所有的options请求
        $my_request = empty($_SERVER['REQUEST_METHOD']) ? '' : array($_SERVER['REQUEST_METHOD']);
        if ($my_request == 'options') {
            header('HTTP/1.1 200 OK');
            exit;
        }

        //添加应用防火墙waf，提升安全性
        $referer      = empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);
        $query_string = empty($_SERVER["QUERY_STRING"]) ? array() : array($_SERVER["QUERY_STRING"]);

        $this->check_data($query_string, $this->url_arr);
         $this->check_data($_GET,$this->args_arr);
        $this->check_data($_POST, $this->args_arr);
        $this->check_data($_COOKIE, $this->args_arr);
        $this->check_data($referer, $this->args_arr);

    }

    ///////////////////////以下内容为waf应用防火墙代码///////////////////////

    private $url_arr = array(
        'xss' => "\\=\\+\\/v(?:8|9|\\+|\\/)|\\%0acontent\\-(?:id|location|type|transfer\\-encoding)",
    );

    private $args_arr = array(
        'xss'   => "[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",

        'sql'   => "[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",

        'other' => "\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]",
    );

    private function check_data($arr, $v) {
        foreach ($arr as $key => $value) {
            if (!is_array($key)) {
                $this->check($key, $v);
            } else {
                $this->check_data($key, $v);
            }

            if (!is_array($value)) {
                $this->check($value, $v);
            } else {
                $this->check_data($value, $v);
            }
        }
    }
    private function check($str, $v) {
        foreach ($v as $key => $value) {
            if (preg_match("/" . $value . "/is", $str) == 1 || preg_match("/" . $value . "/is", urlencode($str)) == 1) {
                header('HTTP/1.1 403 Forbidden');
                echo 'access denied';
                exit();
            }
        }
    }

}
