<?php

namespace App\Http\Controllers;

use App\Exceptions\NotifyException;

use App\Models\Orders;
use App\Models\Pays;
use App\Utils\Pay\PayManager;
use Illuminate\Http\Request;

use Logger;

class NotifyController extends Controller
{

    const NOTIFY_STATUS_NOTIFIED_BY_THIRD_PAY = 1;
    const NOTIFY_STATUS_NOTIFY_TO_CLIENT = 2;
    const NOTIFY_APP_FAILED = 3;
    const NOTIFY_APP_SUCCESS = 4;

    public function notify(Request $request, $pay_type){

        Logger::info("notify: ",$request->all());

        $PayManager = new PayManager();
        if(!$PayManager->isSupport($pay_type)){
            throw new NotifyException($pay_type,"Error: ORDER_NOT_SUPPORTED_PAY_CHANNEL",$request);
        }

        $payProvider = $PayManager->provider($pay_type);
        $payProvider->set_param_all($request->all());

        $order_id = $payProvider->get_notify_order_id();
        //get order
        $order = Orders::findByOrderID($order_id);
        if(!$order){
            throw new NotifyException($pay_type,"Error: NOTIFY_ORDER_NOT_EXIST",$request);
        }

        if($order->status == Orders::STATUS_PAY_SUCCESS){
            return 'success';
        }

        //get app
        $app = Apps::findByAppID($order->app_id);
        if(!$app) {
            throw new NotifyException($pay_type,"Error: APP_ID_INVALID",$request);
        }

        $pay = Pays::findByPayID($order->pay_id);
        if(!$pay){
            throw new NotifyException($pay_type,"Error: NOTIFY_PAY_NOT_EXIST",$request);
        }

        $payProvider->set_app_id($pay->third_app_id);
        $payProvider->set_secret_key($pay->payment_key);
        $payProvider->set_public_key($pay->third_private_key);

        if(!$payProvider->validate_notify()){
            throw new NotifyException($pay_type,"Error: NOTIFY_FAILED",$request);
        }

        \DB::beginTransaction();
        try {

            $amt = $payProvider->get_notify_price();
            $real_pay_money = round($amt * 100);

            $updateArr = [
                'status' => Orders::STATUS_PAY_SUCCESS,
                'trade_id' => $payProvider->get_notify_trade_id(),
                'money' => $real_pay_money,
                'ret_money' => round($real_pay_money * $pay->fee / 10000),
                'notify_status' => self::NOTIFY_STATUS_NOTIFIED_BY_THIRD_PAY,
                'pay_time' => date('Y-m-d H:i:s')
            ];

            if(!Orders::where('order_id',$order->order_id)->update($updateArr)) {
                throw new \Exception("FAILED: NOTIFY_BACK_SAVE_ORDER_FAILED", $order->order_id);
            }

            \DB::commit();
        }catch(\Exception $e) {
            \DB::rollback();
            throw new NotifyException($pay_type,$e->getMessage(),$request);
        }

        return "success";
    }
}
