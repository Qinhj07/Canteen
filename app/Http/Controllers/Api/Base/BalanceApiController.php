<?php

namespace App\Http\Controllers\Api\Base;

use App\Http\Controllers\Controller;
use App\Http\Traits\CheckUser;
use App\Http\Traits\CheckWxPay;
use App\Http\Traits\StandardResponse;
use App\Http\Traits\UniqueCode;
use App\Models\Base\Balance;
use App\Models\Base\ChargeLogs;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BalanceApiController extends Controller
{
    use StandardResponse, CheckUser, UniqueCode, CheckWxPay;

    public function getMyBalance(Request $request)
    {
        $paramEnum = ['openid', 'phone'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        if ($this->checkUserByOpenid($request->get('openid'))) {
            return $this->standardResponse([4004, "NoUserError",]);
        }
        try {
            $balance = Balance::where('openid', $request->get('openid'))->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            $balance = new Balance();
            $balance->openid = $request->get('openid');
            $balance->amount = 0.00;
            $balance->phone = $request->get('phone');
            $balance->save();
        }
        return $this->standardResponse([2000, 'success', $balance->amount]);
    }

    public function charge(Request $request)
    {
        $paramEnum = ['openid', 'phone', 'money', 'payMoney'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        if ($this->checkUserByOpenid($request->get('openid'))) {
            return $this->standardResponse([4004, "NoUserError",]);
        }

        $chargeLog = new ChargeLogs();
        $chargeLog->order_id = $this->get32RamdonCode($request->get('openid'));
        $chargeLog->openid = $request->get('openid');
        $chargeLog->phone = $request->get('phone');
        $chargeLog->amount = $request->get('money');
        $chargeLog->real_pay = $request->get('payMoney');
        $chargeLog->type = 1;
        $chargeLog->save();
        if ($chargeLog->id) {
            return $this->standardResponse([2000, "success", $chargeLog->order_id]);
        } else {
            return $this->standardResponse([5000, "ServerError",]);
        }
    }

    public function checkCharge(Request $request)
    {
        $paramEnum = ['openid', 'orderId'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        try {
            $chargeLog = ChargeLogs::where('order_id', $request->get('orderId'))->firstOrFail();
        }catch (ModelNotFoundException $exception){
            return $this->standardResponse([4004, "OrderError"]);
        }
        if (Carbon::now()->subMinutes(10) > $chargeLog->created_at) {
            return $this->standardResponse([4004, "OrderOverTimeError"]);
        }

        $checkResult = $this->getWxPayResult($chargeLog->order_id, $chargeLog->real_pay);
//        $checkResult = 1; // 本地环境测试用
        if ($checkResult == 1){
            DB::beginTransaction();
            try {
                Balance::where('openid', $chargeLog->openid)->increment('amount', $chargeLog->amount);
                $chargeLog->status = 1;
                $chargeLog->check_money = $chargeLog->real_pay;
                $chargeLog->check_at = Carbon::now()->toDateTimeString();
                $chargeLog->save();
                DB::commit();
            }catch (QueryException $exception){
                DB::rollBack();
                return $this->standardResponse([5000, "ServerError"]);
            }
            return $this->standardResponse([2000, "success"]);
        }else {
            return $this->standardResponse([5000, "PayError", $checkResult]);
        }
    }

}
