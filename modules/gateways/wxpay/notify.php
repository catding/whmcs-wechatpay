<?php

# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

require_once "WechatPay.php";

$ca = new WHMCS_ClientArea();
$userid = $ca->getUserID() ;
use Illuminate\Database\Capsule\Manager as Capsule;

$gatewaymodule = "wxpay"; # Enter your gateway module name here replacing template
$GATEWAY = getGatewayVariables($gatewaymodule);

$url			= $GATEWAY['systemurl'];
$companyname 	= $GATEWAY['companyname'];
$currency		= $GATEWAY['currency'];

if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

$wxconfig['appid']=$GATEWAY['appid'];
$wxconfig['mch_id']=$GATEWAY['mch_id'];
$wxconfig['apikey']=$GATEWAY['apikey'];
$wxconfig['appsecret']=$GATEWAY['appsecret'];
$wxconfig['sslcertPath']=$GATEWAY['sslcertPath'];
$wxconfig['sslkeyPath']=$GATEWAY['sslkeyPath'];

$wxpay = new WechatPay($wxconfig);

$verify_result = $wxpay->get_back_data();

if(!$verify_result) { 
	//echo 1;
	logTransaction($GATEWAY["name"],$_POST,"Unsuccessful1");
	$wxpay->response_back("FAIL");
	
	exit;
}
# Get Returned Variables
$status = $verify_result['result_code'];    //获取支付宝传递过来的交易状态
$invoiceid = $verify_result['out_trade_no']; //获取支付宝传递过来的订单号
$transid = $verify_result['transaction_id'];       //获取支付宝传递过来的交易号
$amount = $verify_result['total_fee']/100;       //获取支付宝传递过来的总价格
$fee = 0;

if($status == 'SUCCESS' ) {
	
	$orderQuery = $wxpay->orderQuery($transid,$invoiceid);
	
	if($orderQuery['result_code']=="SUCCESS")
	{
		$paidcurrency = "CNY"; /////////////////////////////////使用的货币符号
		
		$currency_data     = Capsule::table('tblcurrencies')->where('code',$paidcurrency)->get();
		$paidcurrencyid =  $currency_data[0]->id;

		$userid = $data['userid'];
		$currency = getCurrency( $userid );
		
		if ($paidcurrencyid != $currency['id']) {
			$amount = convertCurrency( $amount, $paidcurrencyid, $currency['id'] );
			$fee = convertCurrency( $fee, $paidcurrencyid, $currency['id'] );
		}
		
		$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
		checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does
		addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
		logTransaction($GATEWAY["name"],$verify_result,"Successful");
		$wxpay->response_back();
			
		//}
	}else{
		//echo 3;
		logTransaction($GATEWAY["name"],$verify_result,"Unsuccessful2");
		$wxpay->response_back("FAIL");
		
		
	}
		
}

?>