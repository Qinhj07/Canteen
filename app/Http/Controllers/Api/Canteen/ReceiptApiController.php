<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiptListResource;
use App\Http\Traits\StandardResponse;
use App\Models\Canteen\Receipts;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReceiptApiController extends Controller
{
    use StandardResponse;
    public function getReceiptList(string $chosenDate)
    {
        if (!strtotime($chosenDate)){
            return $this->standardResponse([
                4003, 'ParamFormatError'
            ]);
        }
        $chosenDateStr = Carbon::parse($chosenDate)->toDateString();
        if ($chosenDateStr < Carbon::now()){
            return $this->standardResponse([
                4003, 'OutOfDate'
            ]);
        }
        $lst = Receipts::where('used_at', $chosenDateStr)->orderBy('meal_type')->get();

        return $this->standardResponse([2000, "success", ReceiptListResource::collection($lst)]);
    }
}
