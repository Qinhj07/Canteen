<?php


namespace App\Http\Traits;


use App\Models\Base\Balance;
use App\Models\Base\ChargeLogs;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

trait CheckBalancePay
{
    public function checkBalalnce(string $openid, string $price)
    {
        try {
            $balance = Balance::where('openid', $openid)->firstOrFail();
        }catch (ModelNotFoundException $exception){
            return 0;
        }
        if ($balance->amount < $price){
            return 0;
        }
        DB::beginTransaction();
        try {
            $chargeLog = new ChargeLogs();
            $chargeLog->order_id = $this->get32RamdonCode($openid);
            $chargeLog->openid = $openid;
            $chargeLog->phone = $balance->phone;
            $chargeLog->amount = $price;
            $chargeLog->real_pay = $price;
            $chargeLog->type = 2;
            $chargeLog->status = 1;
            $chargeLog->comment = "订餐支付";
            $chargeLog->save();
            Balance::where('openid', $openid)->decrement('amount', $price);
            DB::commit();
        }catch (QueryException $exception){
            DB::rollBack();
            return 0;
        }
        return 1;
    }
}
