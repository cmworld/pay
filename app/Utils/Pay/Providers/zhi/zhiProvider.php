<?php
namespace App\Utils\Pay\Providers\zhi;

use App\Utils\Pay\Providers\Provider;

class zhiProvider extends Provider
{
    public $key_config;

    function __construct()
    {
        parent::__construct();
        $this->key_config = include __DIR__ . "/merchant.php";
    }

    function provider_name()
    {
        return 'zhi';
    }

    function set_app_id($app_id)
    {
        $this->_app_id = $app_id;
    }

    function set_app_key($app_key)
    {
        $this->_app_key = $app_key;
    }

    function getApiUrl()
    {
        return "https://api.dinpay.com/gateway/api/scanpay";
    }

    function notify_url(){
        return $this->getConfig('host')."notify/zhi";
    }

    function return_url(){
        return "";
    }

    function pay_force()
    {
        $params = $this->_build_params();
        $params["sign"] = $this->getSign($params);
        $res = $this->getHttpClient()->post($this->getApiUrl(),$params);

        $xml = simplexml_load_string($res);

        if($xml !== FALSE && isset($xml->response->qrcode)) {
            return redirect()->to($xml->response->qrcode);
        }

        throw new \Exception("Provider ".$this->provider_name()."->pay_force(): no result get ".json_encode($params));
    }

    function pay_url()
    {
        $params = $this->_build_params();
        $params["sign"] = $this->getSign($params);
        $res = $this->getHttpClient()->post($this->getApiUrl(),$params);

        $xml = simplexml_load_string($res);

        if($xml !== FALSE && isset($xml->response->qrcode)) {
            return $xml->response->qrcode;
        }

        throw new \Exception("Provider ".$this->provider_name()."->pay_url(): no result get ".json_encode($params));
    }

    function pay_check()
    {
        $params = [
            'merchant_code' => '1111110166',
            'interface_version'  => "V3.1",
            'sign_type'  => 'RSA-S',
            'service_type'     => 'single_trade_query',
            'order_no'      => $this->get_param("_order_id"),
            'trade_no' => ''
        ];

        $params["sign"] = $this->getSign($params);
        $res = $this->getHttpClient()->post($this->getApiUrl(),$params);

        $xml = simplexml_load_string($res);

        if($xml !== FALSE && isset($xml->is_success) && $xml->is_success == 'T') {

            if($xml->trade->trade_status != 'SUCCESS'){
                return false;
            }

            $this->set_param('check_order_id',$xml->trade->order_no);
            $this->set_param('check_order_price',$xml->trade->order_amount);
            $this->set_param('check_trade_no',$xml->trade->trade_no);

            return true;
        }

        return false;
    }


    private function _build_params(){

        $price = (int)$this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);
        return  [
            'merchant_code'         => $this->get_app_id(),
            'notify_url'            => $this->notify_url(),
            'service_type'          => isset($ext['sub_type']) ? $ext['sub_type'] : '',
            'interface_version'    => 'V3.1',
            'sign_type'              => 'RSA-S',
            'order_no'               => $this->get_param('_order_id'),
            'order_amount'          => sprintf("%.2f", $price/100),
            'product_name'          => $this->get_param('_good_name',""),
            'product_desc'          => $this->get_param('_good_desc',""),
            'order_time'            => date('Y-m-d H:i:s'),
            'extra_return_param'   => '',
            'product_num'           => 1,
            'product_code'          => '',
            'extend_param'          => '',
        ];
    }

    function getSign($params) {

        $strA = [];
        ksort($params);
        foreach($params as $k1=>$v1){
            $strA[] = $k1.'='.$v1;
        }

        $signStr = implode('&', $strA);

        /*
        $signStr = 'client_ip='.$params['client_ip'].'&';
        !empty($params['extend_param']) && $signStr .= 'extend_param='.$params['extend_param'].'&';
        !empty($params['extra_return_param']) && $signStr .= 'extra_return_param='.$params['extra_return_param'].'&';
        $signStr .= 'interface_version='.$params['interface_version'].'&';
        $signStr .= 'merchant_code='.$params['merchant_code'].'&';
        $signStr .= 'notify_url='.$params['notify_url'].'&';
        $signStr .= 'order_amount='.$params['order_amount'].'&';
        $signStr .= 'order_no='.$params['order_no'].'&';
        $signStr .= 'order_time='.$params['order_time'].'&';
        !empty($params['product_code']) && $signStr .= 'product_code='.$params['product_code'].'&';
        !empty($params['product_desc']) && $signStr .= 'product_desc='.$params['product_desc'].'&';
        $signStr .= 'product_name='.$params['product_name'].'&';
        !empty($params['product_num']) && $signStr .= 'product_num='.$params['product_num'].'&';
        $signStr .= 'service_type='.$params['service_type'];
        */

        $merchant_private_key= openssl_get_privatekey($this->key_config['merchant_private_key']);
        openssl_sign($signStr,$sign_info,$merchant_private_key, OPENSSL_ALGO_MD5);

        return base64_encode($sign_info);
    }

    function get_notify_order_id(){
        return $this->get_param('order_no');
    }

    function get_notify_trade_id(){
        return $this->get_param('trade_no');
    }

    function get_notify_price(){
        return $this->get_param('order_amount');
    }

    function checkSign() : bool
    {
        $sign = $this->get_param('sign');
        $old_sign = base64_decode($sign);
        $sign_str = '';
        !empty($this->get_param('bank_seq_no')) && $sign_str .= 'bank_seq_no=' . $this->get_param('bank_seq_no') . '&';
        !empty($this->get_param('extra_return_param')) && $sign_str .= 'extra_return_param=' . $this->get_param('extra_return_param') . '&';
        $sign_str .= 'interface_version=' . $this->get_param('interface_version') . '&';
        $sign_str .= 'merchant_code=' . $this->get_param('merchant_code') . '&';
        $sign_str .= 'notify_id=' . $this->get_param('notify_id') . '&';
        $sign_str .= 'notify_type=' . $this->get_param('notify_type') . '&';
        $sign_str .= 'order_amount=' . $this->get_param('order_amount') . '&';
        $sign_str .= 'order_no=' . $this->get_param('order_no') . '&';
        $sign_str .= 'order_time=' . $this->get_param('order_time') . '&';
        $sign_str .= 'trade_no=' . $this->get_param('trade_no') . '&';
        $sign_str .= 'trade_status=' . $this->get_param('trade_status') . '&';
        $sign_str .= 'trade_time=' . $this->get_param('trade_time');

        $dinpay_public_key = openssl_get_publickey($this->key_config['dinpay_public_key']);
        $flag = openssl_verify($sign_str, $old_sign, $dinpay_public_key, OPENSSL_ALGO_MD5);

        return ($flag == 1);
    }
}