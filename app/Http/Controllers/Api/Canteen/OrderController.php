<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Traits\StandardResponse;
use App\Models\Canteen\Orders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use QrCode;

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
            })->get('receipt_id');

        return $this->standardResponse([2000, "success", array_column($orders->toArray(), 'receipt_id')]);
    }

    public function myBookOrderByDateDetail(Request $request)
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
            })->get();

        return $this->standardResponse([2000, "success", OrderListResource::collection($orders)]);
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
        $chosenDate = Carbon::parse($request->get("chosenDate"));
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
            ->whereIn('status', [0, 1])
            ->orderByWith('receiptX', 'used_at', 'asc')
            ->skip($request->start)
            ->take($request->end)
            ->latest()
            ->get();
        return $this->standardResponse([2000, "success", OrderListResource::collection($orders)]);
    }

    public function getMonth(Request $request)
    {
        $paramEnum = ['openid', 'chosenDate'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        if (!strtotime($request->get("chosenDate"))) {
            return $this->standardResponse([
                4003, 'ParamFormatError'
            ]);
        }
        $dateList = [];
        collect(Carbon::parse($request->get("chosenDate"))->startOfMonth()->daysUntil(Carbon::parse($request->get("chosenDate"))->endOfMonth()))->each(function ($item) use (&$dateList) {
            $dateList[$item->toDateString()] = 0;
        });
        $orders = Orders::where('openid', $request->get('openid'))
            ->whereIn('status', [0, 1, 8])
            ->whereHas('receiptX', function ($query) {
                $query->whereBetween('used_at', [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()]);
            })
            ->get();

        $orders->each(function ($item) use (&$dateList) {
            if (object_get($item, 'receiptX')) {
                $dateList[$item->receiptX->used_at] = 1;
            }
        });
        return $this->standardResponse([2000, "success", array_values($dateList)]);
    }

    public function tradeOrder(Request $request)
    {
        $paramEnum = ['openid', 'orderId'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        try {
            $order = Orders::where('oid', $request->get('orderId'))
                ->where('openid', $request->get('openid'))
                ->wherHas('receiptX', function ($query) {
                    $query->whereBetween('used_at', ">=", Carbon::now()->toDateString());
                })
                ->where('status', 1)
                ->firstorFail();
        } catch (ModelNotFoundException $exception) {
            return $this->standardResponse([4004, "OrderNotExistsError",]);
        }
        $order->status = 8;
        if ($order->save()) {
            return $this->standardResponse([2000, "success"]);
        } else {
            return $this->standardResponse([5000, "ServerError"]);
        }
    }

    public function getExtraOrder(Request $request)
    {
        $paramEnum = ['openid', 'type'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        if ($request->get('type') == 1) {
            $orders = Orders::where('openid', $request->get('openid'))
                ->where('status', 0)
                ->skip($request->start)
                ->take($request->end)
                ->latest()
                ->get();
        } elseif ($request->get('type') == 2) {
            $orders = Orders::where('openid', $request->get('openid'))
                ->where('status', 9)
                ->skip($request->start)
                ->take($request->end)
                ->latest()
                ->get();
        } else {
            return $this->standardResponse([4001, "OrderTypeError",]);
        }
        return $this->standardResponse([2000, "success", OrderListResource::collection($orders)]);

    }

    public function getQrcode(Request $request)
    {
        $paramEnum = ['openid', 'code'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        try {
            $order = Orders::where('code', $request->get('code'))->whereHas('receiptX', function ($query) {
                $query->where('used_at', Carbon::now()->toDateString());
            })->firstorFail();
        } catch (ModelNotFoundException $exception) {
            return $this->standardResponse([4004, "OrderNotFoundError",]);
        }
        if ($order->status == 1){
            $img = QrCode::format('png')->size(200)->generate($order->code);
            return $this->standardResponse([2000, "success", 'data:image/png;base64,' . base64_encode($img)]);
        }elseif ($order->status == 0){
            return $this->standardResponse([2000, "success", $order->items]);
        }else{
            return $this->standardResponse([4003, "无订餐信息",]);
        }

    }
}
