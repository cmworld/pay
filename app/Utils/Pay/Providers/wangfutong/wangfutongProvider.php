<?php
namespace App\Utils\Pay\Providers\wangfutong;

use App\Utils\Pay\Providers\Provider;

class wangfutongProvider extends Provider
{
    function provider_name()
    {
        return 'wangfutong';
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
        return "http://pay.wangfutongpay.com/PayBank.aspx";
    }

    function notify_url(){
        return $this->getConfig('host')."notify/wangfutong";
    }

    function return_url(){
        return $this->getConfig('host')."return/wangfutong";
    }

    private function _get_pay_url(){
        $params = $this->_build_params();
        $params["sign"] = $this->getSign($params);
        $params["hrefbackurl"] = $this->return_url();
        $params["attach"] = $this->get_param('_good_name');
        return $this->getApiUrl().'?'.http_build_query($params);
    }

    private function _get_qr_url(){
        $url = $this->_get_pay_url();
        $res = $this->getHttpClient()->get($url);

        $is = preg_match("/MakeQRCode.aspx\?data\=(https\:\/\/qr\.alipay\.com\/[a-zA-Z0-9]+)/", $res,$match);

        if($is && count($match)==2){
            return $match[1];
        }

        throw new \Exception("Provider ".$this->provider_name()."->_get_qr_url(): cannot get url ".json_encode($this->_build_params()));
    }

    function pay_force()
    {
        $ext = $this->get_param('_good_ext',[]);
        $sub_type = $ext['sub_type'] ?? '';

        if(strtoupper($sub_type) == "ALIPAYSCAN") {
            $url = $this->_get_qr_url();
        }else{
            $url = $this->_get_pay_url();
        }

        return redirect()->to($url);
    }

    function pay_url()
    {
        $ext = $this->get_param('_good_ext',[]);
        $sub_type = $ext['sub_type'] ?? '';

        if(strtoupper($sub_type) == "ALIPAYSCAN") {
            return $this->_get_qr_url();
        }else{
            return $this->_get_pay_url();
        }
    }

    function pay_check()
    {
        $params = [
            'version'         => '1.0',
            'partner'         => $this->get_app_id(),
            'ordernumber'     => $this->get_param('_order_id'),
            'sysnumber'        => '',
            'key'               => $this->get_app_secret_key()
        ];
        $res = $this->getHttpClient()->post("http://pay.wangfutongpay.com/trade/query",$params);
        $rst = json_decode($res,true);
        if(isset($rst['status'])||$rst['status']!=1){
            return false;
        }

        if(!isset($rst['tradestate'])||$rst['tradestate']!=1){
            return false;
        }

        if($rst['partner']!=$this->get_app_id()){
            return false;
        }

        return true;
    }

    private function _build_params(){

        $price = (int)$this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);
        return  [
            'partner'         => $this->get_app_id(),
            'banktype'        => $ext['sub_type'] ?? '',
            'paymoney'        => sprintf("%.2f", $price/100),
            'ordernumber'    => $this->get_param('_order_id'),
            'callbackurl'    => $this->notify_url()
        ];
    }

    function getSign($data) {
        return md5(http_build_query($data).$this->get_app_pubic_key());
    }

    function get_notify_order_id(){
        return $this->get_param('ordernumber');
    }

    function get_notify_trade_id(){
        return $this->get_param('sysnumber');
    }

    function get_notify_price(){
        return $this->get_param('paymoney');
    }

    function checkSign() : bool
    {
        $params = [
            'partner' => $this->get_app_id(),
            'ordernumber' => $this->get_param('ordernumber'),
            'orderstatus' => $this->get_param('orderstatus'),
            'paymoney' => $this->get_param('paymoney')
        ];

        return $this->get_param('sign') == md5(http_build_query($params).$this->get_app_key());
    }
}