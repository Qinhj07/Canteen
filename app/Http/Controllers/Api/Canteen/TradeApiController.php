<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderListResource;
use App\Http\Traits\StandardResponse;
use App\Models\Canteen\Orders;
use App\Models\Canteen\Receipts;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TradeApiController extends Controller
{
    use StandardResponse;

    public function getOnTradeOrderNum()
    {
        $breakfastReceipt = Receipts::where('used_at', Carbon::now()->toDateString())->where('meal_type', 1)->value('menus');
        $lunchReceipt = Receipts::where('used_at', Carbon::now()->toDateString())->where('meal_type', 2)->value('menus');
        $receiptList = [];
        collect($breakfastReceipt)->each(function ($item) use (&$receiptList) {
            $receiptList[] = $item['name'];
        });
        collect($lunchReceipt)->each(function ($item) use (&$receiptList) {
            $receiptList[] = $item['name'];
        });
        $ret = [];
        collect($receiptList)->each(function ($item) use (&$ret) {
            $ret[$item] = Orders::whereHas('receiptX', function ($query) {
                $query->where('used_at', Carbon::now()->toDateString());
            })->where('status', 8)
                ->whereRaw("items-> '$[*].name' LIKE '%$item%'")
                ->count();
        });
        return $this->standardResponse([2000, "success", $ret]);
    }
}
