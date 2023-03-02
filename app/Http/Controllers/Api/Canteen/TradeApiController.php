<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Resources\OrderTradeListResource;
use App\Http\Traits\StandardResponse;
use App\Http\Traits\UniqueCode;
use App\Http\Traits\WxPayV3;
use App\Models\Canteen\Orders;
use App\Models\Canteen\PayOrders;
use App\Models\Canteen\Receipts;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TradeApiController extends Controller
{
    use StandardResponse, UniqueCode, WxPayV3;

    public function getOnTradeOrderNum(int $type)
    {
        $receipt = Receipts::where('used_at', Carbon::now()->toDateString())->where('meal_type', $type)->value('menus');
//        $lunchReceipt = Receipts::where('used_at', Carbon::now()->toDateString())->where('meal_type', $type)->value('menus');
        $receiptList = [];
        collect($receipt)->each(function ($item) use (&$receiptList) {
            $receiptList[] = ['name' => $item['name'], 'price' => $item['discountPrice']];
        });
//        collect($lunchReceipt)->each(function ($item) use (&$receiptList) {
//            $receiptList[] = $item['name'];
//        });
        $ret = [];
        collect($receiptList)->each(function ($item) use (&$ret) {
            $name = $item["name"];
            $cnt = Orders::whereHas('receiptX', function ($query) {
                $query->where('used_at', Carbon::now()->toDateString());
            })->where('status', 8)
                ->whereRaw("items-> '$[*].name' LIKE '%$name%'")
                ->count();
            $ret[] = ['name' => $item['name'], 'price' => $item['price'], 'number' => $cnt];
        });
        return $this->standardResponse([2000, "success", $ret]);
    }

    public function getTradeOrderList(int $mealType, string $mealName)
    {
        $order = Orders::whereHas('receiptX', function ($query) use ($mealType) {
            $query->where('used_at', Carbon::now()->toDateString())
                ->where('meal_type', $mealType);
        })->where('status', 8)
            ->whereRaw("items-> '$[*].name' LIKE '%$mealName%'")
            ->get();
        return $this->standardResponse([2000, "success", OrderTradeListResource::collection($order)]);
    }

    public function buyTradeOrder(Request $request)
    {
        $paramEnum = ['openid', 'phone', 'mealType', 'mealName', 'payMethod'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        // 防止重复点
        if (Redis::exists($request->get('openid'))) {
            return $this->standardResponse([5002, "TooFrequencyRequest",]);
        } else {
            Redis::setex($request->get('openid'), 10, 'pay');
        }
        $order = PayOrders::where('openid', $request->get('openid'))
            ->where('status', 1)->whereDate('created_at', Carbon::now()->toDateString())->exists();
        if ($order){
            return $this->standardResponse([4003, "ExistsUnPayError"]);
        }
        if (!in_array($request->get('mealType'), [1, 2])) {
            return $this->standardResponse([4003, "MealTypeError",]);
        }
        $mealName = $request->get('mealName');
        $orders = Orders::whereHas('receiptX', function ($query) use ($request, $mealName) {
            $query->where('used_at', Carbon::now()->toDateString())
                ->where('meal_type', $request->get('mealType'));
        })
            ->where('status', 8)
            ->whereRaw("items-> '$[*].name' LIKE '%$mealName%'")
            ->get();
        $order = null;
        $orders->each(function ($item) use (&$order) {
            $addResult = Redis::sismember('tradeCode', $item->code);
            if (!$addResult) {
                if (Redis::sadd('tradeCode', $item->code)) {
                    $order = $item;
                }
                return false;
            } else {
                return true;
            }
        });
        if (isset($order)) {
            $bookLog = Orders::where('receipt_id', $order->receipt_id)->where('status', 1)->exists();
            if ($bookLog) {
                Redis::srem('tradeCode', $order->code);
                return $this->standardResponse([4003, "OrderExistsError",]);
            }
            $item[] = [
                "items" => $order->items,
                "price" => $order->price,
                "receiptCode" => $order->receipt_id,
                "originOrder" => $order->code
            ];
            $payOrder = new PayOrders();
            $payOrder->order_id = $this->get32RamdonCode($request->get('openid'));
            $payOrder->openid = $request->get('openid');
            $payOrder->phone = $request->get('phone');
            $payOrder->price = $order->price;
            $payOrder->real_pay = $order->real_price;
            $payOrder->pay_type = $request->get('payMethod');
            $payOrder->status = 1;
            $payOrder->items = $item;
            $payOrder->save();
            if ($payOrder->id) {
                // 原订单退款
                $this->doRefund($order->oid, $order->real_price, "交易成功退款", $order->orderX->real_pay, $order->created_at, $order->phone);
                return $this->standardResponse([2000, "success",
                    [
                        "orderId" => $payOrder->order_id,
                        'price' => $payOrder->real_pay
                    ]
                ]);
            }
            return $this->standardResponse([5002, "ServerError"]);
        } else {
            return $this->standardResponse([5000, "OrderNotExistsError"]);
        }
    }

    public function cancelUnPayOrder(Request $request)
    {
        $paramEnum = ['openid', 'orderId'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        try {
            $payOrder = PayOrders::where('order_id', $request->get('orderId'))->where('openid', $request->get('openid'))
                ->where('status', 1)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            return $this->standardResponse([4003, "OrderExistsError"]);
        }
        if (array_key_exists('originOrder', ($payOrder->items)[0])){
            $code = ($payOrder->items)[0]['originOrder'];
            Redis::srem('tradeCode', $code);
        }
        $payOrder->status = 8;
        if($payOrder->save()){
            return $this->standardResponse([2000, "success", $code]);
        }else{
            return $this->standardResponse([5000, "ServerError"]);
        }
    }
}
