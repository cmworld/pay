<?php
// 支付接口
$router->post('pay/official', 'PayController@official_pay');

//第三方回调接口  pay_type 为支付方式  比如 ALIPAY
$router->get('notify/{pay_type}', 'NotifyController@notify');