<?php


namespace App\Http\Traits;


use App\Models\Settings;

trait CheckWxPay
{

    protected  $payService;

    public function __construct()
    {
        $appid = Settings::where('name', 'appId')->value('value');
        $key = Settings::where('name', 'appKey')->value('value');
        $mchid = Settings::where('name', 'mchid')->value('value');
        $this->payService = new WxPayService($mchid, $appid, $key);
    }

    /**
     * @param string $oid
     * @param float $payMoney
     * @return bool
     * @descriptioon check amount with orderId and payMoney form tencent Pay
     */
    public function getWxPayResult(string $oid, float $payMoney): bool
    {
        $payResult = $this->payService->orderquery($oid);
        if ($payResult['code'] == 0){
            // pay success
            if ($payResult['amount'] == $payMoney * 100){
                return true;
            }else{
                return $payResult['msg'];
            }
        }else{
            return false;
        }
    }
}
