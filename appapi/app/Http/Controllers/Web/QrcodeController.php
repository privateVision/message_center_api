<?php
namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;

class QrcodeController extends \App\Controller {

    /**
     * 获取二维码
     * @param url   必须   自动支持urlencode
     * @param size   可选  像素
     * @param margin 可选  边框像素
     * @param f_rgb  可选 前景色  例:FF33FF
     * @param b_rgb  可选 背景色  例:FF33FF
     * @param label  可选 tip
     * @param logo   可选
     * @param logo_width 可选
     * @return image url
     */
    public function getAction(Request $request){
        return qrcode($request->all());
    }
}