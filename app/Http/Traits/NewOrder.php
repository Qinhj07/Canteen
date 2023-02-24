<?php


namespace App\Http\Traits;


use App\Models\Canteen\Orders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

trait NewOrder
{
    public function newOrder(string $openid, string $oid, string $code, array $data, string $phone, string $price, string $realPrice): bool
    {
        try {
            Orders::where('receipt_id', $code)->where('openid', $openid)->where('status', 1)->firstOrFail();
        }catch (ModelNotFoundException $exception){
            $order = new Orders();
            $order->oid = $oid;
            $order->code = strtoupper(Str::random(10));
            $order->openid = $openid;
            $order->phone = $phone;
            $order->receipt_id = $code;
            $order->price = $price; // 这里是两位小数的元
            $order->real_price = $realPrice;
            $order->items = $data;
            if ($order->save()){
                $mark = 1;
            }else{
                return false;
            }
        }
        if (isset($mark)){
            return true;
        }else{
            return false;
        }
    }
}
