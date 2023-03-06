<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Traits\CheckUser;
use App\Http\Traits\StandardResponse;
use App\Http\Traits\WxPayV3;
use App\Models\Base\Balance;
use App\Models\Canteen\Orders;
use App\Models\Canteen\PayOrders;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use QrCode;

class OrderController extends Controller
{
    use StandardResponse, CheckUser, WxPayV3;

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
//        if (Carbon::parse($request->get("chosenDate")) < Carbon::now()) {
//            return $this->standardResponse([
//                4003, 'OutOfDate'
//            ]);
//        }
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
//            ->whereIn('status', [0, 1])
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
        collect(Carbon::parse($request->get("chosenDate"))->startOfMonth()->daysUntil(Carbon::parse($request->get("chosenDate"))->endOfMonth()))->each(function ($item) use (&$dateList, $request) {
            $orders = Orders::where('openid', $request->get('openid'))
                ->whereIn('status', [0, 1])
                ->whereHas('receiptX', function ($query) use ($item){
                    $query->where('used_at', $item->toDateString());
                })
                ->exists();
            if ($orders){
                $dateList[$item->toDateString()] = 1;
            }else{
                $dateList[$item->toDateString()] = 0;
            }
        });
        return $this->standardResponse([2000, "success", array_values($dateList)]);
    }

    public function tradeOrder(Request $request)
    {
        $paramEnum = ['openid', 'code'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        try {
            $order = Orders::where('code', $request->get('code'))
                ->where('openid', $request->get('openid'))
                ->where('status', 1)
                ->firstorFail();
        } catch (ModelNotFoundException $exception) {
            return $this->standardResponse([4004, "OrderNotExistsError",]);
        }
        $useLimitedAt = $order->receiptX->used_at . " " . $order->receiptX->end_at;
        if (Carbon::now() > $useLimitedAt) {
            return $this->standardResponse([4004, "OutOfUsedLimitedTimeError"]);
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
        }elseif ($request->get('type') == 3) {
            $orders = Orders::where('openid', $request->get('openid'))
                ->where('status', 7)
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
        if ($order->status == 1) {
            $img = QrCode::format('png')->size(200)->generate($order->code);
            return $this->standardResponse([2000, "success", 'data:image/png;base64,' . base64_encode($img)]);
        } elseif ($order->status == 0) {
            return $this->standardResponse([2000, "success", $order->items]);
        } else {
            return $this->standardResponse([4003, "无订餐信息",]);
        }

    }

    public function withdrawal(Request $request)
    {
        $paramEnum = ['openid', 'code'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        if ($this->checkUserByOpenid($request->get('openid'))) {
            return $this->standardResponse([4004, "NoUserError",]);
        }
        try {
            $order = Orders::where('code', $request->get('code'))->where('status', 1)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            return $this->standardResponse([4004, "OrderNotFoundError",]);
        }
        // 超时检测，超过预定时间不可以退
        if (Carbon::now() > $order->receiptX->book_limited_at) {
            return $this->standardResponse([4004, "OverBookTimeLimitError"]);
        }
        $payOrder = PayOrders::where('order_id', $order->oid)->first();
        if (object_get($payOrder, 'pay_type', -1) == 1) {
            // 微信支付
            $reason = "退餐退款";
            $ret = $this->doRefund($order->oid, $order->real_price, $reason, $order->orderX->real_pay, $order->created_at, $order->phone);
        } elseif (object_get($payOrder, 'pay_type', -1) == 2) {
            // 余额支付
            Balance::where('openid', $order->openid)->increment('amount', $order->real_price);
            $ret = $order->real_price * 100;
        } else {
            $ret = 0;
        }
        if ($ret == $order->real_price * 100) {
            // 退款成功
            $order->status = 7;
            $order->save();
            return $this->standardResponse([2000, 'success']);
        } else {
            return $this->standardResponse([5000, 'ServerError']);
        }

    }

    public function useOrder(string $code)
    {
        try {
            $order = Orders::where('code', $code)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            return $this->standardResponse([4004, "OrderNotFoundError",]);
        }
        if ($order->status == 0) {
            return $this->standardResponse([4002, "OrderUsedError"]); // 订单已使用
        }
        $startAt = $order->receiptX->used_at . " " . $order->receiptX->start_at;
        $endAt = $order->receiptX->used_at . " " . $order->receiptX->end_at;
        if (Carbon::now() < $startAt || Carbon::now() > $endAt) {
            return $this->standardResponse([4003, "OrderNotAtUseTimeError"]);  // 超时刷码
        }
        $order->status = 0;
        $order->use_at = Carbon::now()->toDateTimeString();
        if ($order->save()) {
            return $this->standardResponse([2000, "success"]);
        }else{
            return $this->standardResponse([5000, "ServerError"]);
        }
    }


}
