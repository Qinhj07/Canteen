<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Traits\StandardResponse;
use App\Models\Canteen\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    use StandardResponse;

    public function myBookOrderByDate(Request $request)
    {
        if (blank($request->get("openid"))) {
            return $this->standardResponse([4001, "NoOpenidError",]);
        }
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

    public function getWeek(Request $request)
    {
        if (blank($request->get("openid"))) {
            return $this->standardResponse([4001, "NoOpenidError",]);
        }
        if (!strtotime($request->get("chosenDate"))) {
            return $this->standardResponse([
                4003, 'ParamFormatError'
            ]);
        }
        $chosenDate= Carbon::parse($request->get("chosenDate"));
        if (($chosenDate < Carbon::now()->startOfWeek()) || ($chosenDate > Carbon::parse('next Sunday +1 week'))) {
            return $this->standardResponse([
                4003, 'OutOfDate'
            ]);
        }

        $orders = Orders::whereIn('status', [0, 1])
            ->where('openid', $request->get("openid"))
            ->whereHas('receiptX', function ($query) use ($chosenDate) {
                $query->whereBetween('used_at', [$chosenDate->startOfWeek()->toDateString(), $chosenDate->endOfWeek()->toDateString()]);
            })
            ->orderByWith('receiptX', 'used_at', 'asc')
            ->get();
        return $this->standardResponse([2000, "success", OrderListResource::collection($orders)]);

    }

    public function getByOid(Request $request)
    {
        $paramEnum = ['openid', 'orderId'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        $orders = Orders::where('oid', $request->get('orderId'))
            ->whereIn('status',  [0, 1])
            ->orderByWith('receiptX', 'used_at', 'asc')
            ->skip($request->start)
            ->take($request->end)
            ->latest()
            ->get();
        return $this->standardResponse([2000, "success", OrderListResource::collection($orders)]);
    }
}
