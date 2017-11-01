<?php
namespace App\Utils\Pay\Providers\alipay;

use App\Utils\Pay\Providers\Provider;

class alipayProvider extends Provider
{

    private $_api_version = "1.0";
    private $_sign_type = "RSA";
    private $_alipay_sdk_version = "alipay-sdk-php-20161101";
    private $_merchant_private_key = "MIICXQIBAAKBgQC1zsEIjGb6nLIIIvbYBxHmXLtlw8zbqEVNSPkDMXt5AZMdYTtz6CIDygLM6k5FyDBLmAy4yUtn1l0d2wUqgiBSLYu56YjLDBw8kghqjykx8VJbTmfIUlxBtieO71F8vj2bUIBCOGA5PeBXNv06GU4tWsb1x/si450ARHIPoRlHgwIDAQABAoGACKe/KNkGTggHsbt4ZPBxObZQdZfMuOhZ5EQFFtHUPv6EMnHekrYKaIPFflvpPgk5w1+Ju4JZxKe/5xv2Mv/e6fYHlnvEW9z3D+Xgz7pZzjYD674ocd2NUnbEY+zOj+2Cw9uGFv6ioGMrcKDiB2TUejxAoZLdRgtSHW6h/7A5noECQQDonv3WCtqk4vX2pfMB3fzoZoivQgxhqzoh+myGze4SjLBXMVqhDPeYpgQL9gbq6TuS/0h8tLP0u4mh85fRoj11AkEAyBRnx8WEpK+v4jf/K1ziV4P3Ncc1ilux6PJ+NRwcxwoTz8tqfEeFb76brMeBYz7zCIeG73jnAedPMFNIXcx6FwJBALCB85m2IrF6hafhw8Jm7sBpDM3vD/YMNtARdMfU+hCZMDT4/gu2CymIzwlEZXtZ/hpMGnSFqQbKRmTcsRYgRQECQQCwUytSpwNKj3oVhvvdnzHppmcKgdDxafXUMUCAVZIW5w6mpcHmXLF/1R8kmX2xlRxhe+6yxH3w84SaNgskfrtpAkBqZWjJUn+GKMm/w36zszZhZpOBZU77mL1TM4JHDqWoZY/2VNO73KB1iOWa17g/kMaekIONNY6zof1wJ+SQ2V2+";

    function provider_name()
    {
        return 'alipay';
    }

    function getApiUrl()
    {
        $api_host =  $this->get_api_host();
        if($api_host){
            return $api_host . "/gateway.do";
        }else {
            return "https://openapi.alipaydev.com/gateway.do";
        }
    }

    function notify_url(){
        return $this->getConfig('host')."notify/alipay";
    }

    function return_url(){
        return $this->getConfig('host')."return/alipay";
    }

    private function _is_saoma(){
        $ext = $this->get_param('_good_ext',[]);
        $sub_type = $ext['sub_type'] ?? '';

        return strtoupper($sub_type) == "SAOMA";
    }

    private function _urlencode_with_str($str,$arr){
        foreach ($arr as $k => $v){
            $str .= "$k=" . urlencode($v) . "&";
        }
        return substr($str, 0, -1);
    }

    private function _qr_pay($need_return){

        $price = $this->get_param('_good_price',0);
        $price = sprintf("%.2f", $price/100);

        $params = [
            'subject' => $this->get_param("_good_name"),
            'out_trade_no' => $this->get_param("_order_id"),
            'total_amount' => $price
        ];

        $req  = new alipayRequest("alipay.trade.precreate",$params);
        $api_params = $req->get_biz_params();

        $system_params = $this->_build_system_params($req);
        $params = array_merge($api_params, $system_params);
        $sys_params["sign"] = $this->getSign($params);

        $api_url = $this->_urlencode_with_str($this->getApiUrl() . "?",$sys_params);

        $res = $this->_api_request($api_url,$api_params);
        if($res === false){
            throw new \Exception("alipay provider ->_qr_pay() failed ");
        }

        return $need_return ? $res->qr_code : redirect()->to($res->qr_code);
    }

    private function _wap_pay($need_return){

        $price = $this->get_param('_good_price',0);
        $price = sprintf("%.2f", $price/100);

        $params = [
            'productCode' => "QUICK_WAP_PAY",
            'body' => $this->get_param("_good_desc"),
            'subject' => $this->get_param("_good_name"),
            'out_trade_no' => $this->get_param("_order_id"),
            'timeout_express' => "10m",
            'total_amount' => $price
        ];

        $req  = new alipayRequest("alipay.trade.wap.pay",$params);
        $api_params = $req->get_biz_params();

        $system_params = $this->_build_system_params($req);
        $params = array_merge($api_params, $system_params);
        $sys_params["sign"] = $this->getSign($params);

        if($need_return) {
            return $this->getApiUrl()."?".$this->_getSignContent(array_merge($params,$sys_params))."&sign=".urlencode($sys_params["sign"]);
        }else {

            return view()->file(dirname(__FILE__) . DIRECTORY_SEPARATOR.'alipay_form.php',[
                'api_url' => $this->getApiUrl()."?charset=UTF-8",
                'params' => array_merge($api_params,$sys_params)
            ]);
        }
    }

    function pay_force()
    {
        if($this->_is_saoma()){
            return $this->_qr_pay(false);
        }else{
            return $this->_wap_pay(false);
        }
    }

    function pay_url()
    {
        if($this->_is_saoma()){
            return $this->_qr_pay(true);
        }else{
            return $this->_wap_pay(true);
        }
    }

    function pay_check()
    {
        $params = [
            'out_trade_no' => $this->get_param("_order_id"),
        ];

        $req  = new alipayRequest("alipay.trade.query",$params);
        $api_params = $req->get_biz_params();

        $system_params = $this->_build_system_params($req);
        $params = array_merge($api_params, $system_params);
        $sys_params["sign"] = $this->getSign($params);

        $api_url = $this->_urlencode_with_str($this->getApiUrl() . "?",$sys_params);

        $res = $this->_api_request($api_url,$api_params);

        if($res && ($res->trade_status == 'TRADE_FINISHED'|| $res->trade_status == 'TRADE_SUCCESS')) {

            $this->set_param('check_order_id',$res->out_trade_no);
            $this->set_param('check_order_price',$res->total_amount);
            $this->set_param('check_trade_no',$res->trade_no);

            return true;

            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
            //如果有做过处理，不执行商户的业务程序

            //注意：
            //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
        }

        return false;
    }

    private function _api_request($api_url , $params){

        $postBodyString = $this->_urlencode_with_str("",$params);

        $res = $this->getHttpClient()->post($api_url ,$postBodyString,[
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $respObject = json_decode($res);
        if(!empty($respObject)&&("10000"==$respObject->code)){
            return $respObject;
        }

        return false;
    }

    private function _build_system_params($req){
        $params["app_id"] = $this->get_app_id();
        $params["version"] = $this->_api_version;
        $params["format"] = 'json';
        $params["sign_type"] = $this->_sign_type;
        $params["method"] = $req->get_method();
        $params["timestamp"] = date("Y-m-d H:i:s");
        $params["alipay_sdk"] = $this->_alipay_sdk_version;
        $params["terminal_type"] = "";
        $params["terminal_info"] = "";
        $params["prod_code"] = "";
        $params["notify_url"] = $this->notify_url();
        $params["return_url"] = $this->return_url();
        $params["charset"] = "UTF-8";
        $params["app_auth_token"] = "";
        return $params;
    }

    function getSign($data) {
        return $this->_sign($this->_getSignContent($data),$this->_sign_type);
    }

    private function _getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if ($v && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                //$v = mb_convert_encoding();

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    private function _sign($data, $signType = "RSA") {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->_merchant_private_key, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }

        return base64_encode($sign);
    }


    function parse_params($datas){
        return $datas;
    }

    function get_notify_order_id(){
        return $this->get_param('out_trade_no');
    }

    function get_notify_trade_id(){
        return $this->get_param('out_trade_no');
    }

    function get_notify_price(){
        return $this->get_param('total_amount');
    }

    function validate_notify(){
        return $this->checkSign();
    }

    function checkSign() : bool
    {
        $sign = $this->get_param('sign');
        $signType = "RSA";
        $params = $this->get_param_all();
        $params['sign_type'] = null;
        $params['sign'] = null;


        $pubKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->get_app_public_key(), 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";


        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($params, base64_decode($sign), $pubKey, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($params, base64_decode($sign), $pubKey);
        }

        return $result;
    }
}