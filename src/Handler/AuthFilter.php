<?php
namespace Trangfoo\HproseLumen\Handler;

/**
 * 验证过滤器
 *
 * Class AuthFilter
 * @package Trangfoo\HproseLumen\Handler
 */
class AuthFilter{

    /**
     * 生成签名
     * @return array
     */
    private static function createSign(){
        $_st = time();
        $_rpc_id = mt_rand(10000,100000);
        $_sign = md5($_st.$_rpc_id.config('hprose.auth.secret'));
        return [
            '_st'       => $_st,
            '_rpc_id'   => $_rpc_id,
            '_sign'     => $_sign
        ];
    }

    /**
     *
     * @param $data
     */
    private static function checkSign($data){
        $check = false;
        if(isset($data['_st']) && isset($data['_rpc_id']) && isset($data['_sign'])){
            $_st = $data['_st'];
            $_rpc_id = $data['_rpc_id'];
            $_sign = $data['_sign'];
            if(time() - $_st <= config('hprose.auth.timeout')){
                $_sign_tmp = md5($_st.$_rpc_id.config('hprose.auth.secret'));
                if($_sign == $_sign_tmp){
                    $check = true;
                }
            }
        }
        return $check;
    }

    /**
     * 客户端请求时植入验证信息
     *
     * @param $name
     * @param array $args
     * @param \stdClass $context
     * @param \Closure $next
     * @return mixed
     */
    public static function inputInvokeHandler($name, array &$args, \stdClass $context, \Closure $next) {
        //植入验证参数
        $args[] = self::createSign();
        $response = $next($name, $args, $context);
        return $response;
    }


    /**
     * 服务端验证后返回信息
     *
     * @param $name
     * @param array $args
     * @param \stdClass $context
     * @param \Closure $next
     * @return array|mixed
     */
    public static function outputInvokeHandler($name, array &$args, \stdClass $context, \Closure $next) {
        if(self::checkSign(end($args))){
            $response = $next($name, $args, $context);
        }else{
            $response = [
                'code'  => config('hprose.auth.reject_code'),
                'data'  => [],
                'msg'   => config('hprose.auth.reject_msg')
            ];
        }
        return $response;
    }


}
