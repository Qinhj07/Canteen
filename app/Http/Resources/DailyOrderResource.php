<?php

namespace App\Http\Resources;

use App\Models\Settings;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $mealType = Settings::where('name', "mealType")->value('extend');
        return [
            'item' => $this->items,
            'useDate' => object_get($this->receiptX, 'used_at'),
            'mealType' => $mealType[object_get($this->receiptX, 'meal_type')],
            'usedAt' => $this->use_at ?: "未使用"
        ];
    }
}
