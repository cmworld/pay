<?php
namespace App\Utils\Pay\Providers\quick;

use App\Utils\Pay\Providers\Provider;

class quickProvider extends Provider
{
    function provider_name()
    {
        return 'quick';
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
        return "https://pay.9127pay.com/api/create.jsp";
    }

    function notify_url(){
        return $this->getConfig('host')."notify/quick";
    }

    function return_url(){
        return "";
    }


    function pay_force()
    {
        $params = $this->_build_params();
        $json = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $api_url = $this->getApiUrl().'?'.http_build_query([
            'time' => APP_TIMENOW,
            "sign" => $this->getSign($params)
        ]);

        return $this->getHttpClient()->post($api_url ,$json,[
                'X-AjaxPro-Method:ShowList',
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($json)
        ]);
    }

    function pay_url()
    {
        return "";
    }

    function pay_check()
    {
        return false;
    }

    private function _build_params(){

        $price = $this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);
        return  [
            'merchant_id'   => $this->get_app_id(),
            'order_id'       => $this->get_param('_order_id'),
            'payment'        => $ext['sub_type'] ?? '',
            'bill_price'    => number_format($price/100, 2),
            'notify_url'    => $this->notify_url(),
            'info'           => [
                "device_ip"   => $ext["ip"],    // 用户的手机 ip 地址
                "device_id"   => $ext["device_id"] ?? "",     // 用户的手机设备码
                "device_type" => $ext["device_type"] ?? "",         // 设备类型， ios/android
                "user_value"  => 0
            ]
        ];

    }

    function getSign($data) {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return strtoupper(md5($json . APP_TIMENOW . $this->get_app_secret_key()));
    }

    function parse_params($datas){

        $json = $datas['json'] ?? "[]";
        $json_arr = json_decode($json,true);

        if(json_last_error() == JSON_ERROR_NONE){
            foreach ($json_arr as $k => $v){
                $this->set_param($k,$v);
            }
        }

        return $datas;
    }

    function get_notify_order_id(){
        return $this->get_param('business_order');
    }

    function get_notify_trade_id(){
        return $this->get_param('upstream_trade_no');
    }

    function get_notify_price(){
        return $this->get_param('payed_money');
    }

    function validate_notify(){

        if($this->checkSign()){

            if($this->get_param('business') != $this->get_app_id()){
                return  false;
            }

            if($this->get_param('pay_status') != 10000){
                return  false;
            }

            return true;
        }
        return false;
    }

    function checkSign() : bool
    {
        $sign = $this->get_param('sign');
        $json = $this->get_param('json');
        $time = $this->get_param('time');

        return strtoupper($sign) == strtoupper(md5($json . $time . $this->get_app_secret_key()));
    }
}