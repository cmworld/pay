<?php
namespace App\Utils\Pay\Providers\alipay;

class alipayRequest
{

    private $_method;
    private $_params = [];

    function __construct($method,$params=[])
    {
        $this->_method = $method;
        $this->_params = $params;
    }

    function get_method(){
        return $this->_method;
    }

    function get_params(){
        return $this->_params;
    }

    function get_biz_params(){
        return [
            "biz_content" => json_encode($this->_params,JSON_UNESCAPED_UNICODE)
        ];
    }
}