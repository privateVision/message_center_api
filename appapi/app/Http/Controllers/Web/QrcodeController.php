<?php
namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;

class QrcodeController extends \App\Controller {

    //测试获取二维码
    public function testAction(){

        // Create a basic QR code
        $qrCode = new QrCode('Life is too short to be generating QR codes');
        $qrCode->setSize(300);

        // Set advanced options
        $qrCode
            ->setWriterByName('png')
            ->setMargin(10)
            ->setEncoding('UTF-8')
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::LOW)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
            ->setLabel('Scan the code', 16, public_path('res/qr/noto_sans.otf'), LabelAlignment::CENTER)
            ->setLogoPath(public_path('res/qr/symfony.png'))
            ->setLogoWidth(150)
            ->setValidateResult(false)
        ;

        // Directly output the QR code
        header('Content-Type: '.$qrCode->getContentType());

        echo $qrCode->writeString();

        // Save it to a file
        //$qrCode->writeFile('qrcode/qrcode.png');

        return new Response($qrCode->writeString(), Response::HTTP_OK, ['Content-Type' => $qrCode->getContentType()]);
    }

    //获取二维码
    public function getAction(){


    }

}