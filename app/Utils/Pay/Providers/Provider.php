<?php

namespace App\Utils\Pay\Providers;

use App\Utils\Pay\HttpClient;
use App\Utils\Logger;

abstract class Provider
{

    public $_app_id;
    public $_app_secret_key;
    public $_app_public_key;
    public $_api_host;

    private $_config;
    private $_params = [];

    private $_pay_money = null;

    function __construct()
    {
        $this->_config = [];
    }

    public function log($message){
        Logger::with($this->provider_name())->info($message);
    }

    public function getHttpClient(){
        return new HttpClient();
    }

    public function setConfig($config){
        $this->_config = $config;
    }

    public function getConfig($key){
        return $this->_config[$key] ?? '';
    }

    function set_app_id($app_id)
    {
        $this->_app_id = $app_id;
    }

    function set_secret_key($app_key)
    {
        $this->_app_secret_key = $app_key;
    }

    function set_public_key($app_key)
    {
        $this->_app_public_key = $app_key;
    }

    function set_api_host($api_host)
    {
        $this->_api_host = $api_host;
    }

    public function get_app_id(){
        return $this->_app_id;
    }

    public function get_app_secret_key(){
        return $this->_app_secret_key;
    }

    public function get_app_public_key(){
        return $this->_app_public_key;
    }

    public function get_api_host(){
        return $this->_api_host;
    }

    abstract function provider_name();

    abstract function getApiUrl();

    abstract function getSign($data);

    abstract function checkSign() : bool ;

    abstract function pay_force();

    abstract function pay_url();

    abstract function pay_check();

    abstract function notify_url();

    abstract function return_url();

    abstract function get_notify_order_id();

    abstract function get_notify_price();

    abstract function get_notify_trade_id();

    function get_check_order_price(){
        return $this->get_param('check_order_price');
    }

    function get_check_trade_id(){
        return $this->get_param('check_trade_no');
    }

    function get_check_order_id(){
        return $this->get_param('get_check_order_id');
    }

    public function validate_notify(){
        return $this->checkSign();
    }

    protected function parse_params($data){
        return $data;
    }

    public function get_param($key,$def = null){
        return isset($this->_params[$key]) ? $this->_params[$key] :  $def;
    }

    public function set_pay_money($money){
        $this->_pay_money = $money;
    }

    public function get_param_all(){
        return $this->_params;
    }

    public function get_pay_money(){
        return $this->_pay_money;
    }

    public function set_param($key,$value){
        $this->_params[$key] = $value;
    }

    public function set_param_all($dates){

        $dates = $this->parse_params($dates);

        foreach ($dates as $k=>$v)
            $this->set_param($k,$v);
    }

    public function __get($name){
        $getter = 'get_' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            $this->get_param($name);
        }
    }

    public function __set($name, $value)
    {
        $setter = 'set_' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->set_param($name, $value);
        }
    }
}