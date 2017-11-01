<?php
namespace App\Utils\Pay\Providers\transfer;

use App\Utils\Pay\Providers\Provider;

class transferProvider extends Provider
{
    const TR_EXPIRED = 1200; //二维码锁住时间

    private $_tr_id = 1001;
    private $_tr_key = "sadkasfhasjk$5";

    function provider_name()
    {
        return 'transfer';
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
        return "https://qr.alipay.com";
    }

    function notify_url(){
        return "";
    }

    function return_url(){
        return "";
    }

    private function _get_qr_url(){
        $params = $this->_build_params();

        $params["sign"] = $this->getSign($params);
        $params['data'] = base64_encode(json_encode($params));
        $params['time'] = APP_TIMENOW;

        $qr_host = $this->getConfig('qr_host');
        $response = $this->getHttpClient()->post(rtrim($qr_host,'/')."/Transfer/getTr",$params);
        $res = json_decode($response,true);

        if(json_last_error() == JSON_ERROR_NONE){

            if(!isset($res['qr']) || !isset($res['qr_id']) || !isset($res['real_price'])){
                throw new \Exception("Provider ".$this->provider->provider_name()."->get_qr() miss params ". json_encode($params));
            }

            $qr_params = [
                'order' => $this->get_param('_order_id'),
                'code' => 200
            ];

            $res['url'] = $this->getApiUrl()."/".$res['qr']."?".http_build_query($qr_params);
            return $res;
        }

        throw new \Exception("Provider ".$this->provider->provider_name()."->get_qr() curl failed: ". json_encode($params));
    }

    private function _transfer_url($qr_id,$url,$price){
        $data = [
            'pay_url' => urlencode($url),
            'qr_id' => $qr_id,
            'price' => $price
        ];
        return route("pay_tr",$data);
    }

    private function _cross_client_url($qr_res ,$return){

        $real_price = intval($qr_res['real_price']);

        $params = [
            'saId' => '10000007',
            'clientVersion' => '3.7.0.0718',
            'qrcode' => urlencode($qr_res['url']),
            '_s' => 'web-other',
            '_t' => intval(microtime(1) * 1000)
        ];

        $platfrom = $this->_get_phone_platfrom();
        switch ($platfrom) {
            case 1:  //android
                $url = "intent://platformapi/startapp?" . http_build_query($params) . '#Intent;scheme=alipays;package=com.eg.android.AlipayGphone;end';
                break;
            case 2: //ios
                $url = "alipays://platformapi/startapp?" . http_build_query($params);
                break;
            default:
                throw new \Exception("Provider " . $this->provider_name() . "->_cross_client_url() unknow phome platform failed: " . json_encode($this->_build_params()));
        }

        if($real_price==0) {
            $url = $this->_transfer_url($qr_res['qr_id'] ,$url , $this->get_param('_good_price'));
        }

        return $return ? $url : redirect()->to($url);
    }

    private function _force_url($qr_res ,$return){
        $real_price = intval($qr_res['real_price']);
        if($real_price==0){
            $url = $this->_transfer_url($qr_res['qr_id'] ,$qr_res['url'] , $this->get_param('_good_price'));
        }else{
            $url = $qr_res['url'];
        }

        return $return ? $url : redirect()->to($url) ;
    }


    function pay_force()
    {
        $qr_res = $this->_get_qr_url();

        $intent = (int)$this->getConfig('intent');
        if($intent == 0){
            return $this->_force_url($qr_res, false);
        }else{
            return $this->_cross_client_url($qr_res, false);
        }
    }

    function pay_url()
    {
        $qr_res = $this->_get_qr_url();

        $intent = (int)$this->getConfig('intent');
        if($intent == 0){
            return $this->_force_url($qr_res , true);
        }else{
            return $this->_cross_client_url($qr_res, true);
        }
    }

    function pay_check()
    {
        $data = [
            'app_id'         => $this->get_app_id(),
            'order_id'     => $this->get_param('_order_id')
        ];

        $params['data'] = base64_encode(json_encode($data));
        $params['time'] = APP_TIMENOW;
        $params['sign'] = $this->getSign($data);

        $res = $this->getHttpClient()->get($this->getConfig('qr_host'),$params);
        $rst = json_decode($res,true);

        if(isset($rst['code']) && $rst['code']==200){
            $data = base64_encode(json_encode($res['data']));
            if($res['sign'] != $this->getSign($data)){
                return false;
            }

            if($res['data']['status']==2||$res['data']['status']==3){

                if(!isset($res['data']['real_price'])){
                    return false;
                }
                $price = round($res['data']['real_price'] * 100);
                if($this->get_param('_good_price') != $price){
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    private function _get_phone_platfrom(){

        $user_agent = $this->get_param("_good_desc");
        if(!$user_agent){
            $ext = $this->get_param('_good_ext',[]);
            $user_agent = $ext['device_type'] ?? "";
        }

        if(stripos($user_agent, 'iphone') !== false ||stripos($user_agent, 'ipad')  !== false){
            return 2;
        }else if(stripos($user_agent, 'android') !== false){
            return 1;
        }else{
            return 0;
        }
    }

    private function _build_params(){

        $price = (int)$this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);

        return  [
            'order_id'      => $this->get_param('_order_id'),
            'price'         => round($price/100,2),
            'user_id'       => $ext['user_id'] ?? "",
            'app_id'        => $ext['app_id'] ?? "",
            'platfrom'      => $this->_get_phone_platfrom()
        ];
    }

    function getSign($data) {
        return md5(base64_encode(json_encode($data)).$this->get_app_secret_key().APP_TIMENOW);
    }

    function parse_params($reqs){

        $data = $reqs['data'] ?? "";
        $data = base64_decode($data);
        $json_arr = json_decode($data,true);

        if(json_last_error() == JSON_ERROR_NONE){

            foreach ($json_arr as $k => $v){
                $this->set_param($k,$v);
            }
        }

        return $reqs;
    }

    function get_notify_order_id(){
        return $this->get_param('order_id');
    }

    function get_notify_trade_id(){
        return $this->get_param('bill_no');
    }

    function get_notify_price(){
        return $this->get_param('price');
    }

    function checkSign() : bool {
        return $this->get_param('sign') != md5($this->get_app_id().$this->get_param('data').$this->get_app_secret_key());
    }
}