<?php
namespace App\Utils\Pay\Providers\ypt;

use App\Utils\Pay\Providers\Provider;

class yptProvider extends Provider
{

    private $_action_map = [
        'alipay_saoma' => "Api/Alipay/precreatetrade",
        'wxpay_saoma' => "Api/Wxpay/native",
        'wxpay_h5' => "Api/Wxpay/wxwap",
    ];

    function provider_name()
    {
        return 'ypt';
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
        return $this->get_api_host();
    }

    function notify_url(){
        return $this->getConfig('host')."notify/ypt";
    }

    function return_url(){
        return "";
    }

    private function _get_action(){
        $ext = $this->get_param('_good_ext',[]);
        $sub_type = $ext['sub_type'] ?? '';

        return $_action_map[strtolower($sub_type)] ?? false;
    }

    function pay_force()
    {
        $params = $this->_build_params();
        $params["sign"] = $this->getSign($params);

        if(!($action = $this->_get_action())){
            throw new \Exception("Provider ".$this->provider_name()."->pay_force(): no action set ".json_encode($params));
        }

        $ret = $this->getHttpClient()->post($this->getApiUrl()."/".$action,http_build_query($params));

        $res = json_decode($ret,true);
        if(isset($res['success'])&&$res['success']=='true'){

            $postUrl = $newData['data']['qrCode'] ?? $newData['data']['payInfo'] ?? "";

            if(!$postUrl){
                throw new \Exception("Provider ".$this->provider_name()."->pay_force(): no url get ".json_encode($newData));
            }

            return redirect()->to($postUrl);
        }

        throw new \Exception("Provider ".$this->provider_name()."->pay_force(): no result get ".$ret);
    }

    function pay_url()
    {
        $params = $this->_build_params();
        $params["sign"] = $this->getSign($params);

        if(!($action = $this->_get_action())){
            throw new \Exception("Provider ".$this->provider_name()."->pay_force(): no action set ".json_encode($params));
        }

        $ret = $this->getHttpClient()->post($this->getApiUrl()."/".$action,http_build_query($params));

        $res = json_decode($ret,true);
        if(isset($res['success'])&&$res['success']=='true'){

            $postUrl = $newData['data']['qrCode'] ?? $newData['data']['payInfo'] ?? "";

            if(!$postUrl){
                throw new \Exception("Provider ".$this->provider_name()."->pay_force(): no url get ".json_encode($newData));
            }

            return $postUrl;
        }

        throw new \Exception("Provider ".$this->provider_name()."->pay_force(): no result get ".$ret);
    }

    function pay_check()
    {
        $params = [
            'orderSn' => $this->get_param('_order_id'),
            'remark' => $this->get_param('_order_trade_id'),
            'mchId' => $this->get_app_id(),
            'timestamp' => time(),
        ];

        $params["sign"] = $this->getSign($params);

        $ret = $this->getHttpClient()->post($this->getApiUrl()."/Api/Trade/querytrade",http_build_query($params));

        $res = json_decode($ret,true);
        if(isset($res['success'])&&$res['success']=='true'){

            $this->set_param('check_order_id',$res['data']['orderSn']);
            $this->set_param('check_order_price',$res['data']['totalAmount']);
            $this->set_param('check_trade_no',$res['data']['remark']);

            return true;
        }
        return false;
    }


    private function _build_params(){

        $price = (int)$this->get_param('_good_price',0);

        $ext = $this->get_param('_good_ext',[]);
        return  [
            'mchId'                 => $this->get_app_id(),
            'subject'                => $this->get_param('_good_name',""),
            'orderSn'               => $this->get_param('_order_id'),
            'notify'                => $this->notify_url(),
            'spbillCreateIp'       => $ext['ip'],
            'totalAmount'          => number_format($price/100, 2),
            'timestamp'            => time(),
            'channel'               => "00"
        ];
    }

    function getSign($params) {

        unset($params['sign']);
        ksort($data);
        $items = array();
        foreach($data as $key=>$value){
            $items[] = $key."=".$value;
        }
        return strtoupper(md5(join("&",$items)."&key=".$this->get_app_secret_key()));
    }

    function get_notify_order_id(){
        return $this->get_param('orderSn');
    }

    function get_notify_trade_id(){
        return $this->get_param('remark');
    }

    function get_notify_price(){
        return $this->get_param('totalAmount');
    }

    function checkSign() : bool
    {
        $params = $this->get_param_all();
        $sign = $this->get_param('sign');
        return $sign == $this->getSign($params);
    }
}