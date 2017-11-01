<?php
namespace App\Utils\Pay\Providers\haiopay;

use App\Utils\Pay\Providers\Provider;

class haiopayProvider extends Provider
{
    function provider_name()
    {
        return 'haiopay';
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
        return "http://api.haiopay.com/GateWay/Bank/Defaultv2.aspx";
    }

    function notify_url(){
        return $this->getConfig('host')."notify/haiopay";
    }

    function return_url(){
        return $this->getConfig('host')."return/haiopay";
    }

    function pay_force()
    {
        $params = $this->_build_params();
        $params["sign"] = $this->getSign($params);
        $params['format'] = "json";
        $params['timespan'] = date("YmdHis");
        $params['custom'] = $this->get_param('_good_name',"");

        if($params['payvia'] == 'alipay') {
            $rst = $this->getHttpClient()->post($this->getApiUrl(), $params);
            $res = json_decode($rst, true);
            if (isset($res['state']) && $res['state'] == 1) {
                // var_dump($res['data']);
                if (!isset($res['img'])) {
                    throw new \Exception("Provider " . $this->provider_name() . "->pay_force(): cannot get url " . json_encode($params));
                }

                return redirect()->to($res['img']);
            } else {
                throw new \Exception("Provider " . $this->provider_name() . "->pay_force(): cannot get result " . json_encode($params));
            }
        }else{
            return view()->file(dirname(__FILE__) . DIRECTORY_SEPARATOR.'pay_form.php',[
                'api_url' => $this->getApiUrl(),
                'params' => $params
            ]);
        }
    }

    function pay_url()
    {
        $params = $this->_build_params();
        $params["sign"] = $this->getSign($params);
        $params['format'] = "json";
        $params['timespan'] = date("YmdHis");
        $params['custom'] = $this->get_param('_good_name',"");

        if($params['payvia'] == 'alipay'){
            $rst = $this->getHttpClient()->post($this->getApiUrl(),$params);
            $res = json_decode($rst,true);
            if(isset($res['state'])&&$res['state']==1){
                // var_dump($res['data']);
                if(!isset($res['img'])){
                    throw new \Exception("Provider ".$this->provider_name()."->pay_url(): cannot get url ".json_encode($params));
                }

                return $res['img'];
            }else{
                throw new \Exception("Provider ".$this->provider_name()."->pay_url(): cannot get result ".json_encode($params));
            }

        }else{
            $params['pay_url'] = $this->getApiUrl();
            return $this->getConfig('host') . '/order/paychannel?'.http_build_query($params);
        }
    }

    function pay_check()
    {
        $params = [
            'userid'      => $this->get_app_id(),
            'orderid' => $this->get_param("_order_id")
        ];

        $params["sign"] = $this->getSign($params);
        $rst = $this->getHttpClient()->post("http://api.haiopay.com/gateway/bank/query.aspx",http_build_query($params));

        $res = json_decode($rst,true);
        if(isset($res['code'])&&$res['code']=='1'){
            // var_dump($res['data']);
            if(!isset($res['state'])||$res['state']!=2){
                return false;
            }

            $this->set_param('check_order_id',$res['orderid']);
            $this->set_param('check_order_price',$res['price']);
            $this->set_param('check_trade_no',$res['billno']);

            return true;

        }else{
            return false;
        }

    }


    private function _build_params(){

        $price = (int)$this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);

        return  [
            'userid'                => $ext['user_id'] ?? '',
            'orderid'               => $this->get_param('_order_id'),
            'price'                 => sprintf("%.2f", $price/100),
            'payvia'                => isset($ext['sub_type']) ? $ext['sub_type'] : '',
            'notify'                 => $this->notify_url(),
            'callback'               => $this->return_url()
        ];
    }

    function getSign($params) {

        $para='';
        foreach ($params as $k => $val) {
            $para.=$k."=".$val."&";
        }
        $para.="key=".$this->get_app_secret_key();
        $m1 = md5($para);
        $m2 = md5($m1.$this->get_app_secret_key());
        return $m2;
    }

    function get_notify_order_id(){
        return $this->get_param('orderid');
    }

    function get_notify_trade_id(){
        return $this->get_param('billno');
    }

    function get_notify_price(){
        return $this->get_param('price');
    }

    function validate_notify(){
        return $this->checkSign() && $this->get_param('state') == 1;
    }

    function checkSign() : bool
    {
        $sign = $this->get_param('sign');

        $arr_sign['userid'] = $this->get_app_id();
        $arr_sign['orderid'] = $this->get_param('orderid');
        $arr_sign['billno'] = $this->get_param('billno');
        $arr_sign['price'] = sprintf("%.2f",$this->get_param('price'));
        $arr_sign['payvia'] = $this->get_param('payvia');
        $arr_sign['state'] = $this->get_param('state');
        $arr_sign['timespan'] = $this->get_param('timespan');

        return $this->getSign($arr_sign) == $sign;
    }
}