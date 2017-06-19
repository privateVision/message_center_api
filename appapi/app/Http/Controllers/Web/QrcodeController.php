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

        //return new Response($qrCode->writeString(), Response::HTTP_OK, ['Content-Type' => $qrCode->getContentType()]);
    }

    /**
     * 获取二维码
     * @param url   必须   自动支持urlencode
     * @param size   可选  像素
     * @param margin 可选  边框像素
     * @param f_rgb  可选 前景色
     * @param b_rgb  可选 背景色
     * @param label  可选 tip
     * @param logo   可选
     * @param logo_width 可选
     * @return image
     */
    public function getAction(Request $request){
        $data = $request->all();
        if(!isset($data['url']) || empty($data['url'])) {
            echo 'qrcode url is empty';exit;
        }

        //检测数据是否urlencode
        if(strpos($data['url'], '%') !== false || strpos($data['url'], '+') !== false){
            $data['url'] = urldecode($data['url']);
        }

        $default = array(
            'url'=>$data['url'],
            'size'=>300,
            'margin'=>10,
            'f_rgb'=>array(0,0,0),
            'b_rgb'=>array(255,255,255),
            'label'=>'',
            'logo'=>'',
            'logo_width'=>150
        );

        //设置二维码像素
        if(isset($data['size'])) {
            $default['size'] = $data['size'];
        }

        //设置边框像素
        if(isset($data['margin'])) {
            $default['margin'] = $data['margin'];
        }

        //设置前景色
        if(isset($data['f_rgb'])) {
            $default['f_rgb'] = explode(',', $data['f_rgb']);
        }

        //设置背景色
        if(isset($data['b_rgb'])) {
            $default['b_rgb'] = explode(',', $data['b_rgb']);
        }

        //设置label
        if(isset($data['label'])) {
            $default['label'] = $data['label'];
        }

        //设置logo
        if(isset($data['logo'])) {
//            $file = file_get_contents($data['logo']);
//            $file = public_path('res/qr/');
        }

        $file = 'qrcode/'.md5(json_encode($default)).'.png';
        if(file_exists($file)) {
            // Directly output the QR code
            header('Content-Type: image/png');
            echo file_get_contents($file);exit;

//            $fb = fopen($file,"rb");
//            $content = fread($fb, filesize($file));
//            fclose($fb);
//            echo $content;
        } else {
            $qrCode = new QrCode($default['url']);

            //默认参数
            $qrCode ->setWriterByName('png')
                ->setEncoding('UTF-8')
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::LOW)
                ->setValidateResult(false);

            //设置参数
            $qrCode ->setSize($default['size'])
                ->setMargin($default['margin'])
                ->setForegroundColor(['r' => $default['f_rgb'][0], 'g' => $default['f_rgb'][1], 'b' => $default['f_rgb'][2]])
                ->setBackgroundColor(['r' => $default['b_rgb'][0], 'g' => $default['b_rgb'][1], 'b' => $default['b_rgb'][2]]);

            //设置label
            if(!empty($default['label'])) {
                $qrCode ->setLabel($default['label'], 16, public_path('res/qr/noto_sans.otf'), LabelAlignment::CENTER);
            }

            //设置logo
            if(!empty($default['logo'])) {
                $qrCode->setLogoPath($default['logo'])
                       ->setLogoWidth($default['logo_width']);
            }

            // Directly output the QR code
            header('Content-Type: '.$qrCode->getContentType());
            echo $qrCode->writeString();

            //save file
            $qrCode->writeFile($file);
        }
    }

}