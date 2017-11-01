<?php
namespace App\Utils\Pay\Providers\ykypay;

use App\Utils\Pay\Providers\Provider;

class ykypayProvider extends Provider
{
    function provider_name()
    {
        return 'ykypay';
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
        return "http://gateway.ma9888.com/index.php";
    }

    function notify_url(){
        return $this->getConfig('host')."notify/ykypay";
    }

    function return_url(){
        return $this->getConfig('host')."return/ykypay";
    }

    function pay_force()
    {
        $ext = $this->get_param('_good_ext',[]);

        $params = $this->_build_params();
        $sign = $this->getSign($params);

        $params['attach'] = $this->get_param('_good_name');
        $params['hrefbackurl'] = $this->return_url();
        $params['payerIp'] = $ext['ip'] ?? '';
        $params['sign'] = $sign;
        $url = $this->getApiUrl()."?a=". base64_encode(http_build_query($params));

        return redirect()->to($url);
    }

    function pay_url()
    {
        $ext = $this->get_param('_good_ext',[]);

        $params = $this->_build_params();
        $sign = $this->getSign($params);

        $params['attach'] = $this->get_param('_good_name');
        $params['hrefbackurl'] = $this->return_url();
        $params['payerIp'] = $ext['ip'] ?? '';
        $params['sign'] = $sign;
        return $this->getApiUrl()."?a=". base64_encode(http_build_query($params));
    }

    function pay_check()
    {
        $params = [
            'orderid' => $this->get_param('_order_id'),
            'parter' => $this->get_app_id()
        ];
        $params['sign'] = $this->getSign($params);

        $ret = $this->getHttpClient()->get("http://www.1983game.com/search.ashx",$params);
        $res = json_decode($ret,true);
        if(isset($res['opstate']) && $res['opstate']=='0'){

            $this->set_param('check_order_id',$res['orderid']);
            $this->set_param('check_order_price',$res['ovalue']);
            $this->set_param('check_trade_no',$res['sysorderid']);

            return true;
        }else{
            return false;
        }
    }


    private function _build_params(){

        $price = (int)$this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);

        return  [
            'parter'                => $this->get_app_id(),
            'type'                  => $ext['sub_type'] ?? '',
            'value'                 => sprintf("%.2f",$price/100),
            'orderid'               => $this->get_param('_order_id'),
            "callbackurl"           => $this->notify_url()
        ];
    }

    function getSign($params) {
        return md5(http_build_query($params).$this->get_app_secret_key());
    }

    function get_notify_order_id(){
        return $this->get_param('orderid');
    }

    function get_notify_trade_id(){
        return $this->get_param('sysorderid');
    }

    function get_notify_price(){
        return $this->get_param('ovalue');
    }

    function checkSign() : bool
    {
        $params = [
            'orderid' => $this->get_param('orderid'),
            'opstate' => $this->get_param('opstate'),
            'ovalue' => $this->get_param('ovalue'),
        ];
        
        return $this->get_param('sign') == $this->getSign($params);
    }
}