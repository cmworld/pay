<?php

namespace App\Utils\Pay;

use Closure;
use InvalidArgumentException;
use App\Exceptions\ApiException;
use App\Utils\Pay\PayScheduler;
use App\Utils\Pay\Providers\Provider;

class PayManager{

    private $_supported_provider_class = [
        'alipay' => '\Providers\alipay\alipayProvider',
        'haiopay' => '\Providers\haiopay\haiopayProvider',
        'quick' => '\Providers\quick\quickProvider',
        'transfer' => '\Providers\transfer\transferProvider',
        'wangfutong' => '\Providers\wangfutong\wangfutongProvider',
        'wiipay' => '\Providers\wiipay\wiipayProvider',
        'yeecardpay' => '\Providers\yeecardpay\yeecardpayProvider',
        'yeepay' => '\Providers\yeepay\yeepayProvider',
        'ykypay' => '\Providers\ykypay\ykypayProvider',
        'ypt' => '\Providers\ypt\yptProvider',
        'zhi' => '\Providers\zhi\zhiProvider'
    ];

    public function isSupport($name){
        $name = strtolower($name);
        return isset($this->_supported_provider_class[$name]);
    }

    public function provider($name)
    {
        try{
            if(!$this->isSupport($name)){
                throw new InvalidArgumentException("Unsupported provider [{$name}].");
            }

            return $this->createDriver($name);
        }catch (\Exception $e){
            throw new ApiException($e->getCode(),$e->getMessage());
        }
    }

    protected function createDriver($name)
    {
        $name = strtolower($name);
        $provider = __NAMESPACE__.$this->_supported_provider_class[$name];
        return $this->scheduler(new $provider());
    }

    public function scheduler(Provider $provider)
    {
        return new PayScheduler($provider);
    }
}