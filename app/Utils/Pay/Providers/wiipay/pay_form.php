<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta content="telephone=no" name="format-detection">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <title>微派-充值</title>
    <script src="https://cdn.bootcss.com/blueimp-md5/2.7.0/js/md5.min.js"></script>
    <style>
        .desccontent{
            margin-top: 10px;padding-left: 5px;padding-right: 5px;
        }
        .desctitle{
            padding-left: 5px;padding-right: 5px;color: #494949;font-size:20px;margin-top: 20px;line-height:1.65;
        }
        .descdesc{
            padding-left: 15px;padding-right: 15px;color: #808080;font-size:16px;line-height:1.65;
        }
        p {
            margin: 0 0 10px;
        }
    </style>
</head>
<body>
<div class="all_page">
    @if ( $params['total_fee'] >=1000 ):

        <div class=" desccontent" style="font-size:18px;" >
            <p  class= "desctitle">充值提示:</p>
            <p  class= "descdesc" >
                该充值方式最高充值金额：<span style="color: red">999元</span>。<br/>
                请选择其他金额充值，可分多次充入>
            </p>
        </div>
    @endif
</div>

<!-- #################微派支付--H5支付--集成示例2.0#################开始######################-->
<!--1.添加控制台活的的script标签，实际项目中请替换真实appId-->
<script id='wpay-jspay-script' src='http://jspay.wiipay.cn/1/jspay/wpayscripts.do?appId={{$params["app_id"]}}'></script>
<script>
    setTimeout(function(){
        sub_do_pay();
    },100);
    // document.getElementById("buyButton").onclick = function() {
    function sub_do_pay(){
        /**
         * 2. 需要支付时调用WP.click接口传入参数
         */

        var app_id = '{{$params["app_id"]}}';//微派分配的appId,联系运营人员获取，实际项目中请替换真实appId
        var body = '{{$params["body"]}}';  //商品名称，必填项，请勿包含敏感词
        var callback_url = '{{$params["callback_url"]}}'; //支付成功后跳转的商户页面(用户看到的页面)
        var channel_id = '{{$params["channel_id"]}}'; //渠道编号
        var out_trade_no = '{{$params["out_trade_no"]}}'; //商户单号，确保不重复，如想透传更多参数信息，建议以Json-->String->Base64编码后传输，必填项
        var total_fee = '{{$params["total_fee"]}}';//商品价格(单位:元)，必填项
        var version = '{{$params["version"]}}';
        var sign = '{{$params["sign"]}}';

        WP.click({
            //"instant_channel":"ali",//配置默认支付方式，当仅有一个支付方式时，不会弹出收银台，会直接跳转到支付界面；支付宝ali，微信公众号wx，银联un，网银wy，此参数不参与签名
            // "debug":1, //开启调试模式，会显示错误信息，注意，上线后需要去掉，此参数不参与签名
            "body":body,
            "callback_url" : callback_url,
            "channel_id":channel_id,
            "out_trade_no":out_trade_no,
            "total_fee":total_fee,
            "sign":sign,
            "version":version
        });


        /**
         * click调用错误返回：默认行为console.log(err)
         */
        WP.err = function(err) {
            //err 为object, 例 ｛"ERROR" : "xxxx"｝;
            console.log(err);
        }
    }

</script>
</body>
</html>
