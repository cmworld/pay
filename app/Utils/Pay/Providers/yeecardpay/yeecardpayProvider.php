<?php
namespace App\Utils\Pay\Providers\yeecardpay;

use App\Utils\Pay\Providers\Provider;

class yeecardpayProvider extends Provider
{
    function provider_name()
    {
        return 'yeecardpay';
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
        return "http://merchant.doyun.net/gateway/bank.aspx";
    }

    function notify_url(){
        return $this->getConfig('host')."notify/yeecardpay";
    }

    function return_url(){
        return "";
    }

    function pay_force()
    {
        $params = $this->_build_params();
        $params["hmac"] = $this->getSign($params);

        return view()->file(dirname(__FILE__) . DIRECTORY_SEPARATOR.'pay_form.php',[
            'api_url' => $this->getApiUrl(),
            'params' => $params
        ]);
    }

    function pay_url()
    {
        return "";
    }

    function pay_check()
    {
        // TODO: Implement pay_check() method.
        return false;
    }

    private function _build_params(){
        $price = (int)$this->get_param('_good_price',0);
        $ext = $this->get_param('_good_ext',[]);
        return  [
            'p0_Cmd'          => "Buy",
            'p1_MerId'        => $this->get_app_id(),
            'p2_Order'        => $this->get_param('_order_id'),
            'p3_Amt'          => sprintf("%.2f", $price/100),
            'p4_Cur'          => $this->get_param('_good_cur',"CNY"),
            'p5_Pid'          => $this->get_param('_good_name',""),
            'p6_Pcat'         => $this->get_param('_good_cat',""),
            'p7_Pdesc'        => $this->get_param('_good_desc',""),
            'p8_Url'          => $this->notify_url(),
            'p9_SAF'          => "0",
            'pa_MP'           => "",
            'pd_FrpId'        => isset($ext['sub_type']) ? $ext['sub_type'] : '',
            'pr_NeedResponse' => "1"
        ];
    }

    function getSign($data) {
        $s = join("",array_values($data));
        return $this->_hmacMd5($s,$this->get_app_secret_key());
    }

    function get_notify_order_id(){
        return $this->get_param('r6_Order');
    }

    function get_notify_trade_id(){
        return $this->get_param('r2_TrxId');
    }

    function get_notify_price(){
        return $this->get_param('r3_Amt');
    }

    function checkSign() : bool
    {
        $notifyParams = [
            $this->get_app_id(),
            $this->get_param('r0_Cmd'),
            $this->get_param('r1_Code'),
            $this->get_param('r2_TrxId'),
            $this->get_param('r3_Amt'),
            $this->get_param('r4_Cur'),
            $this->get_param('r5_Pid'),
            $this->get_param('r6_Order'),
            $this->get_param('r7_Uid'),
            $this->get_param('r8_MP'),
            $this->get_param('r9_BType'),
        ];

        $s = join("",array_values($notifyParams));
        return $this->get_param('hmac') == $this->_hmacMd5($s,$this->get_app_secret_key());
    }


    private function _hmacMd5($data,$key){
        //需要配置环境支持iconv，否则中文参数不能正常处理
        $key = iconv("GB2312","UTF-8",$key);
        $data = iconv("GB2312","UTF-8",$data);

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*",md5($k_ipad . $data)));
    }

}