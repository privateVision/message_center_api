<?php
namespace App\Http\Controllers\Api\Pay;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\OrderExtend;
use App\Model\Orders;

class HuaweiController extends Controller {

    use RequestAction;

    const PayMethod = '-12';
    const PayText = 'huawei';
    const PayTypeText = '华为平台支付';

    /**
     * hauwei config
     * {
     *      "cp_id":"890086000001009560",
     *      "app_id":"10814160",
     *      "pay_id":"890086000001009560",
     *      "pay_pub_key":"MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkw7D+a9GmnkEkLaujKakqbC3X829A5nEMAngybHnw5Qk2SZfLvkHts9Fl2BhCsz14x4Vb0e03GPkNdtJX58DJ6q/LU+RRiz/++oc3EJWpz8APJBQUqOf3A03eOokBFMsg2Ts1BF1oyQ24mM4UbDQNsZxA+RPxmik+GaZB+XkKGboF/roudDEwTqybjolnEuXEzxRRptaFrGjuT1L7+Ii8YLLLU8T5wRv0hS3xQFIVth9j+ydzDCXMYjjnjmPBVeIQlEnYiY4OVPG7p1mM72AzQrxj6z9KSiUF5Ih4QBZDxYHOzXxEGSTzZC6A6eE4oMzAe7kPzJDxzf0O8Z+F8grGwIDAQAB",
     *      "pay_pri_key":"MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCTDsP5r0aaeQSQtq6MpqSpsLdfzb0DmcQwCeDJsefDlCTZJl8u+Qe2z0WXYGEKzPXjHhVvR7TcY+Q120lfnwMnqr8tT5FGLP/76hzcQlanPwA8kFBSo5/cDTd46iQEUyyDZOzUEXWjJDbiYzhRsNA2xnED5E/GaKT4ZpkH5eQoZugX+ui50MTBOrJuOiWcS5cTPFFGm1oWsaO5PUvv4iLxgsstTxPnBG/SFLfFAUhW2H2P7J3MMJcxiOOeOY8FV4hCUSdiJjg5U8bunWYzvYDNCvGPrP0pKJQXkiHhAFkPFgc7NfEQZJPNkLoDp4TigzMB7uQ/MkPHN/Q7xn4XyCsbAgMBAAECggEAFadM8A0BBmnfZDeiCA/ZmyrsNW0j8c3Id5JcyRYrHD9KFbdyuIXuvtpSUvHcGC0J38FkQph6ZyFGTy/U5AGUA1k/ATsAFyc1IjDcwwn5nHLIZCKT0TdKqKpBispfN0vP+CD6RlezzKvecQNPHag9YHNj3MDXhk2OTQ0+Z01QhSo4u4Ft1yOfsqgBWUUyjIMd8lVasf9d8ozQHE03Z7tM2yn81lBzn95VKl7tnQu0I9nRCrnkMZvYosIKREFsDJOYzE0ZpSOydPRnwj8pEik9upEjLSCRqdCYJN40DexEikfabxtVM0ibGXSnC7gi/ZuoHcnzEbQV5m/GBqNFC+Bl4QKBgQDLFxG1Sy0XgqYA38taGxUvZDN4NWfv3FynZSnZHnZQ1eFzZRYEjLuiVUXpBetIlRArC7Zp7zXmg/3s3K5DCs9JWpE5LpuaxaH3t2lbQB6Xzo09l48511h2mzmb3tSZj14MTaV3qMLJ2YMtjbJJWjfYvhKnazN8LBoIDHec8w6tswKBgQC5XqiKsiRe7MZa09EdRfTrpquL4u75DzrCCtJ2Iq+PFapc0ITGICKTAAPrBsi3c7ygSJqddbqyaao9hrZRY/dB7qZbwfbBg2370bvjBImjH4XGRFAX1EX0svw7noV0g8sq+N8mTRvPT3oJdAQ6lYqalbm6US3aDk/xbTD2tQ3o+QKBgGBmPt2TJYA3X5yind/TYybvpQ62KvPL4Z8Dge2xa+/K1gz0OpNGSfowB9MoIBp/xwDnulpmVWtp06oOxhjElMf42V4PJYU9sjfnM3dA5ESioqBNxIpsEW2bGKlICBor1zR31scJsAwn1wBUdgAjdsbG0gvt8q5KMMEJSe2R4bHJAoGBAK8PWhRB0F9lNJ4qU95VZsv1hySAmDbVzyPZnJC8iReT2mP0+K8zQfOZnBmlOoEl6AlnB72UpVBAwemBA0UyJxw5CRq2vxZZzNB4bfwjGOjYqDlp4kneyoIhVlvnhRlYLdLTXcqKH61U3Wd4DVZWS6NZqyDt8WNxCMZz3D3hFtXhAoGABI1v3aS3HY2g5TzkfkiZYvtZ9JfBgLUyTl+A7QTFPEDSMpvSS6VsMrrmC4/9ZPaKWMVJHu7XBnLwVYzpIOW+dmczinnsfj1xSOeQKAAue9Ib19peFXkfBAZpp325IzK6dNZWC8nYhVoZVE8jwDG4uRlhaIjiR1NOAcxC3OpEnYo="
     * }
     */

    /**
     * @param $config
     * @param Orders $order
     * @param $real_fee
     * @param $accountId
     * @return array
     */
    public function getData($config, Orders $order, OrderExtend $order_extend, $real_fee) {

        $cfg = $this->procedure_extend->third_config;
        if(empty($cfg) || !isset($cfg['app_id'])) {
            throw new ApiException(ApiException::Remind, trans('messages.error_third_params'));
        }

        $params = array(
            'userName'=>'武汉爪游互娱科技有限公司',
            'userID'=>$cfg['pay_id'],
            'applicationID'=>$cfg['app_id'],
            'amount'=>sprintf("%.2f", $real_fee/100),
            'productName'=>mb_substr($order->subject, 0, 50),
            'requestId'=>$order->sn,
            'productDesc'=>mb_substr($order->body, 0, 100),
            'notifyUrl'=>url('pay_callback/huawei'),
            'serviceCatalog'=>'X6'
        );

        $params['sign'] = self::verify($params, $cfg);

        return [
            'data' =>$params
        ];
    }

    protected function verify($data, $cfg)
    {
        //去掉不签名的字段
        unset($data['userName']);unset($data['notifyUrl']);unset($data['serviceCatalog']);

        ksort($data);

        $content = "";
        $i = 0;
        foreach($data as $key=>$value)
        {
            if($key != "sign" && !empty($value))
            {
                $content .= ($i == 0 ? '' : '&').$key.'='.$value;
            }
            $i++;
        }

        $priKey = $cfg['pay_pri_key'];
        if(strpos($priKey, "BEGIN RSA PRIVATE KEY") === false)
        {
            $priKey = wordwrap($priKey, 64, "\n", true);
            $priKey = "-----BEGIN RSA PRIVATE KEY-----\n".$priKey."\n-----END RSA PRIVATE KEY-----";
        }
        $res = openssl_get_privatekey($priKey);
        openssl_sign($content, $sign, $res);
        openssl_free_key($res);
        return base64_encode($sign);
    }

}