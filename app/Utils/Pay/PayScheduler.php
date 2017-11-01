<?php

namespace App\Utils\Pay;

use App\Exceptions\PayException;
use Closure;
use InvalidArgumentException;
use App\Exceptions\ApiException;
use App\Utils\Pay\Providers\Provider;


use App\Models\SystemConfig;

class PayScheduler{

    public $provider;

    public $onPayCallback = null;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;

        $configRows = SystemConfig::all();

        $conf = [];
        foreach ($configRows as $r){
            $conf[$r['key']] = $r['value'];
        }

        if(!$conf){
            throw new PayException($this->provider_name(),"Cannot read system_config for PayScheduler");
        }

        $this->provider->setConfig($conf);
    }

    function provider_name(){
        return $this->provider->provider_name();
    }

    function set_app_id($app_id)
    {
        $this->provider->set_app_id($app_id);
    }

    function set_app_key($app_key)
    {
        $this->set_secret_key($app_key);
    }

    function set_secret_key($app_key)
    {
        $this->provider->set_secret_key($app_key);
    }

    function set_public_key($app_key)
    {
        $this->provider->set_public_key($app_key);
    }

    function set_api_host($api_host){
        $this->provider->set_api_host($api_host);
    }

    public function set_order_id($value){
        $this->provider->set_param('_order_id',$value);
    }

    public function set_good_price($price,$cur=""){
        $this->provider->set_param('_good_price',$price);
        if($cur){
            $this->provider->set_param('_good_cur',$cur);
        }
    }

    public function set_good_name($value){
        $this->provider->set_param('_good_name',$value);
    }

    public function set_good_desc($value){
        $this->provider->set_param('_good_desc',$value);
    }

    public function set_good_ext($value){
        $this->provider->set_param('_good_ext',$value);
    }

    public function set_trade_id($trade_id){
        $this->provider->set_param('_order_trade_id',$trade_id);
    }

    public function set_param_all($datas){
        $this->provider->set_param_all($datas);
    }

    public function pay_force(){

        try{
            $res = $this->provider->pay_force();
        }catch (\Exception $e){
            throw new PayException($this->provider_name(),$e->getMessage(),$e);
        }

        $callback = $this->onPayCallback;
        if($callback && $callback instanceof Closure){
            $callback($this->provider->get_param('_order_id'));
        }

        /*
        if(filter_var($res, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
            return redirect()->to($res);
        }else*/
        if ($res instanceof \Illuminate\Http\RedirectResponse){
            return $res;
        }

        return "";
    }

    public function pay_url(){

        try{
            $url = $this->provider->pay_url();
        }catch (\Exception $e){
            throw new PayException($this->provider_name(),$e->getMessage(),$e);
        }

        $callback = $this->onPayCallback;
        if($callback && $callback instanceof Closure){
            $callback($this->provider->get_param('_order_id'));
        }

        return $url;
    }

    public function pay_check(){
        try{
            $res = $this->provider->pay_check();
        }catch (\Exception $e){
            throw new PayException($this->provider_name(),$e->getMessage(),$e);
        }

        $callback = $this->onPayCallback;
        if($callback && $callback instanceof Closure){
            $callback($this->provider->get_param('_order_id'));
        }

        /*
        if(filter_var($res, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)){
            return redirect()->to($res);
        }else*/
        if ($res instanceof \Illuminate\Http\RedirectResponse){
            return $res;
        }

        return "";
    }

    public function get_notify_order_id(){
        return $this->provider->get_notify_order_id();
    }

    public function get_notify_price(){
        return $this->provider->get_notify_price();
    }

    public function get_notify_trade_id(){
        return $this->provider->get_notify_trade_id();
    }

    public function get_check_order_price(){
        return $this->provider->get_check_order_price();
    }

    public function get_check_order_id(){
        return $this->provider->get_check_order_id();
    }

    public function get_check_trade_id(){
        return $this->provider->get_check_trade_id();
    }

    public function validate_notify(){
        return $this->provider->validate_notify();
    }

    public function onPayCallBack(Closure $callback = null){
        return $this->onPayCallback = $callback;
    }
}