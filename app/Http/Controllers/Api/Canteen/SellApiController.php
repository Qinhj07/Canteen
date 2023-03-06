<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Traits\StandardResponse;
use App\Models\Canteen\Orders;
use App\Models\Canteen\Receipts;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SellApiController extends Controller
{
    use StandardResponse;

    public function getUsedOrder(): \Illuminate\Http\JsonResponse
    {
        $receipt = Receipts::where('used_at', Carbon::now()->toDateString())
            ->where('start_at', '<=', Carbon::now()->toTimeString())
            ->where('end_at', '>=', Carbon::now()->toTimeString())
            ->value('code');
        $orders = Orders::where('receipt_id', $receipt)->where('status', 0)->get();
        return $this->standardResponse([2000, "success", OrderListResource::collection($orders)]);

    }

    public function findByPhone(string $phone): \Illuminate\Http\JsonResponse
    {
        $orders = Orders::where('phone', $phone)
            ->whereHas('receiptX', function ($query) {
                $query->where('used_at', Carbon::now()->toDateString())
                    ->where('start_at', '<=', Carbon::now()->toTimeString())
                    ->where('end_at', '>=', Carbon::now()->toTimeString());
            })->whereIn('status', [0, 1])
            ->get();
        return $this->standardResponse([2000, "success", OrderListResource::collection($orders)]);
    }

    public function getCount(): \Illuminate\Http\JsonResponse
    {
        $total = Orders::whereHas('receiptX', function ($query) {
            $query->where('used_at', Carbon::now()->toDateString());
        })->whereIn('status', [0, 1])->count();
        $used = Orders::whereHas('receiptX', function ($query) {
            $query->where('used_at', Carbon::now()->toDateString());
        })->where('status', 0)->count();
        $unUsed = Orders::whereHas('receiptX', function ($query) {
            $query->where('used_at', Carbon::now()->toDateString());
        })->where('status', 1)->count();
        return $this->standardResponse([2000, 'success', ['total' => $total, 'used' => $used, 'left' => $unUsed]]);
    }
}
