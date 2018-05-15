<?php
use \Firebase\JWT\JWT;

/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/4/2
 * Time:        21:13
 * Describe:    jwt相关的扩展方法,这个文件在autoload自动加载
 */

class M_jwt
{
    public function __construct()
    {
        //...
    }

    //生成jwt token 字符串
    public function generateJwtToken($bxid=0,$company_id=0){
       
            $key    = config_item('jwt_key');
            $alg    = config_item('jwt_alg');

            //token中各字段含义参见JWT payload的说明
            $token = array(
                "iss" => config_item('jwt_iss'),
                "exp" => config_item('jwt_exp'),
                "nbf" => config_item('jwt_nbf'),
                "bxid" => $bxid,  //自添加字段，
                "company_id"    =>$company_id
            );

            return JWT::encode($token, $key);
          
       
    }

    /**
     * @param string $token
     * @return bool|object
     */
    public function decodeJwtToken($token=''){

            $key    = config_item('jwt_key');
            $alg    = config_item('jwt_alg');

            return $decoded= JWT::decode($token, $key, array($alg));
         

    }
}