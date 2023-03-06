<?php

namespace App\Http\Resources;

use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $status = Settings::where('name', "orderStatus")->value('extend');
        $mealType = Settings::where('name', "mealType")->value('extend');
        return [
            'order_id' => $this->code,
            'phone' => $this->phone,
            'price' => $this->price,
            'realPrice' => $this->real_price,
            'status' => $status[$this->status],
            'item' => $this->items,
            'useDate' => object_get($this->receiptX, 'used_at'),
            'mealType' => $mealType[object_get($this->receiptX, 'meal_type')],
            'createdAt' => $this->created_at,
            'usedAt' => $this->use_at ?: "未使用"
        ];
    }
}
