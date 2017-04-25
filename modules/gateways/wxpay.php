<?php
# V1.1
# Add support PHP7
# Thanks to https://github.com/zhangv/wechat-pay
# Thanks to teNsi0n@hostloc
# Andy @2017
# https://www.vmlink.cc


//ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);

require_once "wxpay/WechatPay.php";

//模式二
/**
 * 流程：
 * 1、调用统一下单，取得code_url，生成二维码
 * 2、用户扫描二维码，进行支付
 * 3、支付完成之后，微信服务器会通知支付成功
 * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php
 */
 
function wxpay_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"微信支付"),
	 "appid" => array("FriendlyName" => "公众账号appid", "Type" => "text", "Size" => "32", ),
	 "mch_id" => array("FriendlyName" => "商户号", "Type" => "text", "Size" => "32", ),
	 "apikey" => array("FriendlyName" => "加密key", "Type" => "text", "Size" => "32", ),
	 "appsecret" => array("FriendlyName" => "公众号appsecret", "Type" => "text", "Size" => "32", ),
	 "sslcertPath" => array("FriendlyName" => "证书路径(apiclient_cert.pem)", "Type" => "text", "Size" => "32", ),
	 "sslkeyPath" => array("FriendlyName" => "密钥路径(apiclient_key.pem)", "Type" => "text", "Size" => "32", )
    );
	return $configarray;
}

function wxpay_link($params) 
{
	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
	$amount = $params['amount']; # Format: ##.##
	$currency = $params['currency']; # Currency Code
	//echo $invoiceid;
	# System Variables
	$companyname 		= $params['companyname'];
	$systemurl 			= $params['systemurl'];
	
	$wxconfig['appid']=$params['appid'];
	$wxconfig['mch_id']=$params['mch_id'];
	$wxconfig['apikey']=$params['apikey'];
	$wxconfig['appsecret']=$params['appsecret'];
	$wxconfig['sslcertPath']=$params['sslcertPath'];
	$wxconfig['sslkeyPath']=$params['sslkeyPath'];
	
	$wechatpay = new WechatPay($wxconfig);
	
	$param['body']=$companyname ."-".$invoiceid;
	$param['detail']=$invoiceid;
	$param['out_trade_no']=$invoiceid;
	$param['total_fee']=$amount*100;
	$param["spbill_create_ip"] =$_SERVER['REMOTE_ADDR'];//客户端IP地址
	$param["time_start"] = date("YmdHis");//请求开始时间
	$param["time_expire"] =date("YmdHis", time() + 600);//请求超时时间
	$param["goods_tag"] = urldecode($companyname);//商品标签，自行填写
	$param["notify_url"] = $systemurl."/modules/gateways/wxpay/notify.php";//自行定义异步通知url
	$param["trade_type"] = "NATIVE";//扫码支付模式二
	$param["product_id"] = $invoiceid;//正好有产品id就传了个，看文档说自己定义
	//调用统一下单API接口

	$result=$wechatpay->unifiedOrder($param);

	if(isset($result["code_url"]) && !empty($result["code_url"]))
	{
	//	echo 1;
		$url2 = $result["code_url"];
		$link = urlencode($url2);
		$code = '<div id="wximg" class="wx" style="max-width: 230px;margin: 0 auto"><img alt="模式二扫码支付" src="'.$systemurl.'/modules/gateways/wxpay/qrcode.php?data='.$link.'" style="width:190px;height:190px;"/></div>
		<div id="wxDiv" class="wx" style="max-width: 230px;margin: 0 auto"><img src="'.$systemurl.'/modules/gateways/wxpay/logo.png" border=0 width=160></div>
		';
	
		$code_ajax = '
	<!--微信支付ajax跳转-->
		<script>
		//设置每隔 1200 毫秒执行一次 load() 方法
		setInterval(function(){load()}, 1200);
		function load(){
			var xmlhttp;
			if (window.XMLHttpRequest){
				// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp=new XMLHttpRequest();
			}else{
				// code for IE6, IE5
				xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange=function(){
				if (xmlhttp.readyState==4 && xmlhttp.status==200){
					trade_state=xmlhttp.responseText;
					if(trade_state=="SUCCESS"){
						document.getElementById("wximg").style.display="none";
						document.getElementById("wxDiv").innerHTML="支付成功";
						//延迟 2 秒执行 tz() 方法
						setTimeout(function(){tz()}, 1200);
						function tz(){
							window.location.href="'.$systemurl.'/viewinvoice.php?id='.$invoiceid.'";
						}
					}
				}
			}
			//invoice_status.php 文件返回订单状态，通过订单状态确定支付状态
			xmlhttp.open("get","'.$systemurl.'/modules/gateways/wxpay/invoice_status.php?invoiceid='.$invoiceid.'",true);
			//下面这句话必须有
			//把标签/值对添加到要发送的头文件。
			//xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			//xmlhttp.send("out_trade_no=002111");
			xmlhttp.send();
		}
	</script>';
		
		$code = $code.$code_ajax;
		
		if (stristr($_SERVER['PHP_SELF'], 'viewinvoice')) {
			return $code;
		} 
	}	
	
	else
	{
		//echo 2;
		return $result["err_code_des"];
	}

		
	
	
}


?>