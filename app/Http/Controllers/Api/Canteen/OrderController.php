<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Traits\StandardResponse;
use App\Models\Canteen\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    use StandardResponse;

    public function myBookOrderByDate(Request $request)
    {
        if (!strtotime($request->get("chosenDate"))) {
            return $this->standardResponse([
                4003, 'ParamFormatError'
            ]);
        }
        if (Carbon::parse($request->get("chosenDate")) < Carbon::now()) {
            return $this->standardResponse([
                4003, 'OutOfDate'
            ]);
        }
        $orders = Orders::whereIn('status', [0, 1])
            ->where('openid', $request->get("openid"))
            ->whereHas('receiptX', function ($query) use ($request) {
                $query->where('used_at', $request->get("chosenDate"));
            })->get('code');

        return $this->standardResponse([2000, "success", array_column($orders->toArray(), 'code')]);
    }
}
