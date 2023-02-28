<?php

namespace App\Http\Resources;

use App\Models\Settings;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTradeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $status = Settings::where('name', "orderStatus")->value('extend');
        $mealType = Settings::where('name', "mealType")->value('extend');
        return [
            'order_id' => $this->code,
            'realPrice' => $this->real_price,
            'status' => $status[$this->status],
            'item' => $this->items,
            'useDate' => object_get($this->receiptX, 'used_at'),
            'mealType' => $mealType[object_get($this->receiptX, 'meal_type')],
        ];    }
}
