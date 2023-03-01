<?php


namespace App\Http\Traits;


use App\Models\Base\Balance;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        return Balance::where('openid', $openid)->decrement('amount', $price);
    }
}
