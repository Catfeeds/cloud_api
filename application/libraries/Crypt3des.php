<?php

/**
 * PHP版3DES加解密类
 * 源代码由法大大提供
 */
class Crypt3des
{
    /**
     * 使用pkcs7进行填充
     */
    static function PaddingPKCS7($input)
    {
        $srcdata      = $input;
       $block_size   = @mcrypt_get_block_size ('tripledes', 'ecb');
        $padding_char = $block_size - (strlen($input) % $block_size);
        $srcdata      .= str_repeat(chr($padding_char), $padding_char);

        return $srcdata;
    }

    /**
     * 3des加密
     * @param  $string //待加密的字符串
     * @param  $key //加密用的密钥
     * @return //加密后的字符串
     */
    static function encrypt($string, $key)
    {
        $string         = self::PaddingPKCS7($string);

        // 加密方法
        $cipher_alg     = MCRYPT_TRIPLEDES;

        // 初始化向量来增加安全性
        $iv             = @mcrypt_create_iv(mcrypt_get_iv_size($cipher_alg, MCRYPT_MODE_ECB), MCRYPT_RAND);

        //开始加密
        $encryptedStr   = @mcrypt_encrypt($cipher_alg, $key, $string, MCRYPT_MODE_ECB, $iv);

        // 转化成16进制
        $des3           = bin2hex($encryptedStr);

        return $des3;
    }
}