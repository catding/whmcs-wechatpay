<?php
/**
 * 查询账单状态，并返回
 * http://docs.whmcs.com/Creating_Pages
 * http://stackoverflow.com/questions/20087207/call-whmcs-sessionuid-from-external-domain
 */
# Required File Includes
$whmcs_version = "6";
if($whmcs_version == "5"){
    //WHMCS5.x 包含以下2个文件
    require_once __DIR__ . '/../../../dbconnect.php';
    require_once __DIR__ . '/../../../includes/functions.php';
} else if($whmcs_version == "6") {
    //WHMCS 6.x 包含以下1个文件
    require_once __DIR__ . '/../../../init.php';
}

$invoiceid = $_REQUEST['invoiceid'];

$ca = new WHMCS_ClientArea();

$userid = $ca->getUserID() ;

if($userid == 0){
    exit;
}


use Illuminate\Database\Capsule\Manager as Capsule;
$order_data     = Capsule::table('tblinvoices')->where('id',$invoiceid)->get();
$invoiceid      = $order_data[0]->id;
$status  = $order_data[0]->status;
$total   = $order_data[0]->total;	
$paymentmethod   = $order_data[0]->paymentmethod;	


if($status == "Paid"){
    if($paymentmethod == "wxpay"){
        echo "SUCCESS";
    }else{
        echo "FAIL. It is not wxpay";
    }
}else{
    echo "FAIL. Not paid";
}

