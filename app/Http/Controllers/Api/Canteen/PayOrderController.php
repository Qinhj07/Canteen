<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayOrderListResource;
use App\Http\Traits\CheckBalancePay;
use App\Http\Traits\CheckWxPay;
use App\Http\Traits\NewOrder;
use App\Http\Traits\StandardResponse;
use App\Http\Traits\UniqueCode;
use App\Models\Canteen\Orders;
use App\Models\Canteen\PayOrders;
use App\Models\Canteen\Receipts;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PayOrderController extends Controller
{
    use NewOrder, StandardResponse, UniqueCode, CheckWxPay, CheckBalancePay;

    public function newPayOrder(Request $request)
    {
        $paramEnum = ['openid', 'phone', 'money', 'payMoney', 'payMethod', 'item'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        // 这里redis setnx加锁，锁openid, 10秒之内不能下两次单防止卡顿出现两个单
        if (Redis::exists($request->get('openid'))) {
            return $this->standardResponse([5002, "TooFrequencyRequest",]);
        } else {
            Redis::setex($request->get('openid'), 10, 'pay');
        }
        // 检查每一餐是不是有订了的，或者餐码对应餐不存在的，不创建订单
        foreach ($request->get('item') as $item) {
            $receipt = Receipts::where('code', $item['receiptCode'])->doesntExist();
            $bookLog = Orders::where('receipt_id', $item['receiptCode'])->where('status', 1)->exists();
            if ($bookLog || $receipt) {
                return $this->standardResponse([5002, "{$item['receiptCode']} ExistsError",]);
            }
        }
        // 创建订单
//        DB::beginTransaction();
        try {
            $order = new PayOrders();
            $order->order_id = $this->get32RamdonCode($request->get('openid'));
            $order->openid = $request->get('openid');
            $order->phone = $request->get('phone');
            $order->price = $request->get('money');
            $order->real_pay = $request->get('payMoney');
            $order->pay_type = $request->get('payMethod');
            $order->status = 1;
            $order->items = $request->get('item');
            $order->save();
            // 修改到检查支付时候写入
//            foreach ($request->get('item') as $item) {
//                $this->newOrder($request->get('openid'), $order->order_id, $item['receiptCode'], $item['items'],
//                    $request->get('phone'), $item['price'], $item['price']);
//            }
//            DB::commit();
        } catch (QueryException $exception) {
//            DB::rollBack();
//            return $this->standardResponse([5002, "ServerError", $exception]);
            return $this->standardResponse([5002, "ServerError"]);
        }
        return $this->standardResponse([2000, "success", $order->order_id]);
    }

    public function getMyOrder(Request $request)
    {
        $paramEnum = ['openid', 'phone'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }

        $orders = PayOrders::where('openid', $request->get('openid'))
            ->where('phone', $request->get('phone'))
            ->where('status', 2)
            ->skip($request->start)
            ->take($request->end)
            ->latest()
            ->get();
        return $this->standardResponse([2000, "success", PayOrderListResource::collection($orders)]);
    }

    public function checkOrder(Request $request)
    {
        $paramEnum = ['openid', 'orderId'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        try {
            $order = PayOrders::where('order_id', $request->get('orderId'))
                ->where('openid', $request->get('openid'))
                ->where('status', 1)
                ->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            return $this->standardResponse([4004, "OrderError"]);
        }
        if (Carbon::now()->subMinutes(10) > $order->created_at) {
            return $this->standardResponse([4004, "OrderOverTimeError"]);
        }
        if ($order->pay_type == 1) {
            // 微信支付
            $checkResult = $this->getWxPayResult($order->order_id, $order->real_pay);
        } elseif ($order->pay_type == 2) {
            $checkResult = $this->checkBalalnce($order->openid, $order->real_pay);
//            return $this->standardResponse([4004, "暂不支持余额支付"]);
        } else {
            return $this->standardResponse([4004, "支付方式异常"]);
        }
        if ($checkResult == 1) {
            DB::beginTransaction();
            try {
                $order->status = 2;
                $order->check_money = $order->real_pay;
                $order->check_at = Carbon::now()->toDateTimeString();
                $order->save();
                foreach ($order->items as $item) {
                    if (array_key_exists("originOrder", $item)) {
                        // 删redis, 改状态
                        Redis::srem('tradeCode', $item["originOrder"]);
                        Orders::where('code', $item["originOrder"])->update(['status' => 9]);
                    }
                    $this->newOrder($order->openid, $order->order_id, $item['receiptCode'], $item['items'],
                        $order->phone, $item['price'], $item['price']);
                }
                DB::commit();
            } catch (QueryException $exception) {
                DB::rollBack();
                return $this->standardResponse([5000, "ServerError"]);
            }
            return $this->standardResponse([2000, "success"]);
        } else {
            return $this->standardResponse([5000, "PayError", $checkResult]);
        }
    }

    public function getPayErrorOrder(Request $request)
    {
        $paramEnum = ['openid'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        $orders = PayOrders::where('status', 1)->latest()->take(1)->get();
        return $this->standardResponse([2000, "success", PayOrderListResource::collection($orders)]);
    }
}
