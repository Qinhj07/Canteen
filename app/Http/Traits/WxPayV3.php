<?php


namespace App\Http\Traits;


use App\Models\Settings;
use Exception;
use GuzzleHttp\Exception\RequestException;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Transformer;
use WeChatPay\Util\PemUtil;
use GuzzleHttp;

trait WxPayV3
{
    private $key;
    private $mchid;
    private $instance;
    private $appid;

    public function __construct()
    {
        $this->key = Settings::where('name', 'payKey')->value('value');
        $this->mchid = Settings::where('name', 'mchid')->value('value');
        $this->appid = Settings::where('name', 'appiId')->value('value');
        $merchantCertificateSerial = Settings::where('name', 'cateSerial')->value('value');
        $merchantPrivateKeyFilePath = "file://" . resource_path('PemFiles/apiclient_key.pem');
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        $platformCertificateFilePath = "file://" . resource_path('PemFiles/cert.pem');
        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);
        $this->instance = Builder::factory([
            'mchid' => $this->mchid,
            'serial' => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);
    }

    public function doRefund(string $orderId, string $price, string $reason, string $totalPrice, string $bookAt, string $phone)
    {
        try {
            $resp = $this->instance->chain('v3/refund/domestic/refunds')->post(['json' => [
                "out_trade_no" => $orderId,
                "out_refund_no" => strtoupper(time() . mb_substr($orderId, random_int(0, strlen($orderId) - 8), 7) . random_int(0, 9)),
                "reason" => $reason,
                "funds_account" => "AVAILABLE",
                "amount" => [
                    "refund" => $price * 100, // 单位为分
                    "total" => $totalPrice * 100, // 订单总价
                    "currency" => "CNY"
                ],
                "goods_detail" => [
                    [
                        "merchant_goods_id" => $orderId,
                        "goods_name" => "{$phone}在{$bookAt}订餐",
                        "unit_price" => $price * 100,
                        "refund_amount" => $price * 100,
                        "refund_quantity" => 1
                    ]
                ],
            ]]);
        } catch (Exception $e) {
            $msg = $e->getMessage();
//            dump($msg);
            if ($e instanceof RequestException && $e->hasResponse()) {
//                $r = $e->getResponse();
//                dump(json_decode($r->getBody()));
                return -1; //$r->getStatusCode(); // . ' ' . $r->getReasonPhrase(), PHP_EOL);
//                dump($r->getBody(), PHP_EOL, PHP_EOL, PHP_EOL);
            }
            return -2;
//            dump($e->getTraceAsString(), PHP_EOL);
        }
        $jsonResponse = json_decode($resp->getBody());
        return $jsonResponse->amount->refund;

    }

    public function checkResult(string $orderId)
    {
        try {
            $resp = $this->instance->v3->pay->transactions->outTradeNo->_out_trade_no_->get([
                'out_trade_no' => $orderId,
                'query' => [
                    'mchid' => $this->mchid,
                ],
            ]);
        } catch (Exception $e) {
            $r = $e->getResponse();
            if ($e instanceof RequestException && $e->hasResponse()) {
                return [
                    'state' => object_get(json_decode($r->getBody()), "trade_state", "QueryError"),
                    'amount' => -1,
                    'StatusCode' => $r->getStatusCode()
                ];
            }
        }
        $respBody = json_decode($resp->getBody());
        return [
            'state' => object_get($respBody, 'trade_state', 'Error'),
            'amount' => object_get(object_get($respBody, 'amount', null), 'payer_total',-1)
        ];


    }

}
