<?php

namespace App\Http\Controllers;


use App\Utils\Pay\PayManager;
use InvalidArgumentException;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Exceptions\PayException;

class PayController extends Controller
{

    public function official_pay(Request $request){


        //检查是否支持该支付方式
        $payinfo = Pays::findByID($id);
        if(empty($payinfo)){
            return null;
        }
/*   数据库中会存储 支付需要的 appid  和 appkey 或支付网关
        $payinfo = [
            'type' => 'ALIPAY',
            'app_id' => 'app id',
            'app_key' => 'app key',
            'api_host' => ''
        ];
*/
        if(is_null($payInfo)){
            throw new ApiException(602,'NOT_SUPPORT_PAY');
        }

        //调用聚合支付类
        $PayManager = new PayManager();
        if(!$PayManager->isSupport($payInfo['type'])){
            throw new ApiException(602,"Unsupported provider [{$payInfo['type']}].");
        }

        //创建订单
        $order_id = Orders::generateId();

        $order = new Orders();
        $order->uid = $uid;
        $order->order_id = $order_id;
        $order->product = $remark;
        $order->price = $price;
        $order->money = $money;
        $order->currency = 'CNY';
        $order->comment = $comment;
        $order->status = Orders::STATUS_ORDER_CREATED;

        $order->pay_type = $payInfo['type'];
        $order->pay_id = $payInfo['id'];
        $order->created = date("Y-m-d H:i:s");

        if(!$order->save()) {
            throw new ApiException(310,'ORDER_CREATE_ORDER_FAILED'); //create order failed
        }

        //Orders::setOrderIdinRedis($order);
        $payProvider = $PayManager->provider($payInfo['type']);
        $payProvider->set_app_id($payInfo['app_id']);
        $payProvider->set_app_key($payInfo['app_key']);
        $payProvider->set_api_host($payInfo['api_host']);

        $payProvider->set_order_id($order->order_id);
        $payProvider->set_good_price($order->money,$order->currency);
        $payProvider->set_good_name($order->product);
        $payProvider->set_good_desc($order->comment);

        //支付额外参数
        $payProvider->set_good_ext([
            'app_id' => $_app->app_id,
            'user_id' => $order->uid,
            'sub_type'=>$payInfo['sub_type'],
            'ip' => $request->ip(),
            'device_id' => $_data['device_id'] ?? md5($order->uid.':'.$order->app_id),
            'device_type' => get_user_agent(),
        ]);

        //支付验证成功后回调
        $payProvider->onPayCallBack(function($order_id){

            //更新支付状态  这里更新为正在第三方支付 但还没支付完成
            $update = [
                'status' => Orders::STATUS_ORDER_SUBMIT_THIRD
            ];

            if(!Orders::where('order_id',$order_id)->update($update)){
                throw new PayException("Order update statue failed: ");
            }
        });

        //返回支付url
        //return $payProvider->pay_url();

        //直接进行支付跳转
        return $payProvider->pay_force();
    }
}
