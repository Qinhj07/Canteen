<?php


namespace App\Http\Traits;


use App\Models\Settings;

trait CheckWxPay
{
    private $appid;
    private $mchid;
    private $key;

    protected  $payService;

    public function __construct()
    {
        $appid = Settings::where('name', 'appId')->value('value');
        $key = Settings::where('name', 'appKey')->value('value');
        $mchid = Settings::where('name', 'mchid')->value('value');
        $this->payService = new WxPayService($this->mchid, $this->appid, $this->key);
    }

    public function checkWxPay(string $oid)
    {
//        $payService = new WxPayService('', '', '');
        $payRet = $this->payService->orderquery($oid);
        if ($payRet['code'] == 0){
            // pay success
            $realMoney = $payRet['amount'];  // 微信支付收到的金额，单位为分，  订单价格乘100做比较
            $payAt = $payRet['time'];  // 微信支付交易时间
            // 核对金额，成功-》修改订单状态为已支付，
            // 金额异常，
        }else{
            // pay error
            // 返回支付异常信息，返回错误，交易失败

        }
    }
}
