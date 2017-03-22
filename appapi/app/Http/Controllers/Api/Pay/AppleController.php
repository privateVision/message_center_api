<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/21
 * Time: 14:40
 */
namespace App\Http\Controllers\Api\Pay ;

use App\Exceptions\ApiException;
use App\Model\IosOrderExt;
use App\Model\Orders;
use App\Parameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class  AppleController extends Controller{


    /*
     * 验证苹果信息
     * */

    public function validateReceiptAction (Request $request ,Parameter $parameter)
    {

        $receipt ='';
        $dat = $this->validate_receipt($receipt, true); //开启黑盒测试

        $receipt = $parameter->tough("receipt");
        $bundle_id = $parameter->tough("bundle_id");
        $transaction = $parameter->tough("transaction_id");
        //订单完成，通知发货，添加日志记录
        var_dump($dat); //

        //查看当前的是否存在当前的文档中

        $dat = IosOrderExt::where("transaction_id")->where("status",0)->frist();

        if($dat || !empty($dat)) throw new ApiException(ApiException::Remind,""); //存在表示已经验证过

    }

    /*
     * 验证当前的支付信息
     * */
    function validate_receipt($receipt_data, $sandbox_receipt = FALSE) {

        if ($sandbox_receipt) {
            $url = "https://sandbox.itunes.apple.com/verifyReceipt";
        }
        else {
            $url = "https://buy.itunes.apple.com/verifyReceipt";
        }
        $ch = curl_init($url);
        $data_string = json_encode(array(
            'receipt-data' => $receipt_data
        ));

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (200 != $httpCode) {
            die("Error validating App Store transaction receipt. Response HTTP code $httpCode");
        }
        $decoded = json_decode($output, TRUE);
        var_dump($decoded);exit;
    }

    /*
     * 苹果订单的创建
     * */

    public function orderCreateAction(Request $request,Parameter $parameter){
        echo "1234";
        return 1233;
        $uid = $parameter->tough('uid');
        $ucid = $parameter->tough("ucid");
        $vorderid = $parameter->tough('vorderid'); //厂家订单id
        $zone_name = $parameter->tough("zone_name");
        $role_name = $parameter->tough('role_name');
        $product_id = $parameter->tough('product_id');

        $sql = "select p.fee,p.product_name,con.notify_url from ios_products as p LEFT JOIN ios_application_config as con ON p.app_id = con.app_id WHERE p.product_id = :product_id";
        $dat = DB::select($sql,[$product_id]);

        $order = new Orders;
        $order->ucid = $this->user->ucid;
        $order->uid = $this->user->uid;
        $order->sn = date('ymdHis') . substr(microtime(), 2, 6) . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $order->vid = $this->procedure->pid;
        $order->notify_url = $dat[0]['notify_url'];
        $order->vorderid = $vorderid;
        $order->fee = $dat[0]['fee'];
        $order->subject = $dat[0]['product_name'];
        $order->body = "role_name: ".$role_name."zone_name: ".$zone_name;
        $order->createIP = $request->ip();
        $order->status = Orders::Status_WaitPay;
        $order->paymentMethod = Orders::Way_Unknow;
        $order->hide = false;
        $order->save();

        $ext = $order->ios_order_ext();
        $ext ->oid = $order->id;
        $ext ->product_id = $product_id;
        $ext->zone_name = $zone_name;
        $ext->role_name = $role_name;
        $ext->save();

        return [
            'order_id' => $order->sn,
            'way' => [1, 2, 3],
            'vip' => $this->user->vip(),
            'balance' => $this->user->balance,
            'coupon' => $this->user->coupon(),
            'fee'    =>$dat[0]['fee']
        ];

    }

}
