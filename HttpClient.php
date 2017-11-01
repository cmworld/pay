<?php


namespace App\Utils\Pay;


class HttpClient
{
    private $_timeout = 30;
    private $_usecert = false;

    private $_curl_info;

    private function _combine_data($data){
        $valueArr = array();
        foreach($data as $key => $val){
            $valueArr[] = "$key=$val";
        }

        return implode("&",$valueArr);
    }

    public function get($url, $data = []){
        if($data){
            $data_combined = $this->_combine_data($data);
            $url .= "?".$data_combined;
        }

        $response = $this->_curl('GET',$url);
        return $response;
    }

    public function post($url, $data, $header=[]){
        return $this->_curl('POST',$url, $data,$header);
    }

    function _curl($mothed,$url,$data = null,$header=[]){
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $url);

        if($mothed == 'POST'){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if(!empty($header)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        if($this->_usecert){
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        }

        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $data = curl_exec($ch);

        $this->_curl_info = curl_getinfo($ch);

        curl_close($ch);
        return $data;
    }
}