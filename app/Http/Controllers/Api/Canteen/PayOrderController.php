<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Traits\NewOrder;
use App\Http\Traits\StandardResponse;
use App\Http\Traits\UniqueCode;
use App\Models\Canteen\Orders;
use App\Models\Canteen\PayOrders;
use App\Models\Canteen\Receipts;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PayOrderController extends Controller
{
    use NewOrder, StandardResponse, UniqueCode;

    public function newPayOrder(Request $request)
    {
        $paramEnum = ['openid', 'phone', 'money', 'payMoney', 'payMethod', 'item'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        // 这里redis setnx加锁，锁openid, 10秒之内不能下两次单防止卡顿出现两个单
        if (Redis::exists($request->get('openid'))){
            return $this->standardResponse([5002, "ToFrequencyRequest",]);
        }else{
            Redis::setex($request->get('openid'), 10, 'pay');
        }
        // 检查每一餐是不是有订了的，或者餐码对应餐不存在的，不创建订单
        foreach ($request->get('item') as $item) {
            $receipt = Receipts::where('code', $item['receiptCode'])->doesntExist();
            $bookLog = Orders::where('receipt_id', $item['receiptCode'])->where('status', 1)->exists();
            if ($bookLog || $receipt){
                return $this->standardResponse([5002, "{$item['receiptCode']} ExistsError",]);
            }
        }
        // 创建订单
        DB::beginTransaction();
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
            // 逐餐写入
            foreach ($request->get('item') as $item) {
                $this->newOrder($request->get('openid'), $order->order_id, $item['receiptCode'], $item['items'],
                    $request->get('phone'), $item['price'], $item['price']);
            }
            DB::commit();
        }catch (QueryException $exception){
            DB::rollBack();
//            return $this->standardResponse([5002, "ServerError", $exception]);
            return $this->standardResponse([5002, "ServerError"]);
        }
        return $this->standardResponse([2000, "success", $order->order_id]);
    }

}