<?php
namespace App\Utils\Pay\Providers\wiipay;

use App\Utils\Pay\Providers\Provider;

class wiipayProvider extends Provider
{

    function provider_name()
    {
        return 'wiipay';
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
        return $this->getConfig('host')."order/h5wiipay";
    }

    function notify_url(){
        return $this->getConfig('host')."notify/wiipay";
    }

    function return_url(){
        return $this->getConfig('host')."return/wiipay";
    }

    function pay_force()
    {
        $params = $this->_build_params();
        $params['sign'] = $this->getSign($params);
        //$url = $this->getApiUrl()."?". http_build_query($params);

        return view()->file(dirname(__FILE__) . DIRECTORY_SEPARATOR.'pay_form.php',[
            'api_url' => $this->getApiUrl(),
            'params' => $params
        ]);
    }

    function pay_url()
    {
        $params = $this->_build_params();
        $params['sign'] = $this->getSign($params);
        return $this->getApiUrl()."?". http_build_query($params);
    }

    function pay_check()
    {
        return false;
    }

    private function _build_params(){

        $price = (int)$this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);

        return  [
            'app_id'                => $this->get_app_id(),
            'body'                  => $this->get_param('_good_name'),
            'callback_url'         => $this->return_url(),
            'out_trade_no'         => $this->get_param('_order_id'),
            'total_fee'             => sprintf("%.2f",$price/100),
            'channel_id'            => $ext['sub_type'] ?? '',
            "version"               => '2.0'
        ];
    }

    function getSign($params) {
        unset($params['sign']);
        ksort($params);
        $items = array();
        foreach($params as $key=>$value){
            $items[] = $key."=".$value;
        }
        return strtoupper(md5(join("&",$items).$this->get_app_secret_key()));
    }

    function validate_notify(){
        return $this->checkSign() && $this->get_param('status') == 'success';
    }

    function get_notify_order_id(){
        return $this->get_param('cpparam');
    }

    function get_notify_trade_id(){
        return $this->get_param('orderNo');
    }

    function get_notify_price(){
        return $this->get_param('price');
    }

    function checkSign() : bool
    {
        $params = $this->get_param_all();
        return $this->get_param('sign') == $this->getSign($params);
    }
}