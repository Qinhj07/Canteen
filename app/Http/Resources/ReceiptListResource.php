<?php

namespace App\Http\Resources;

use App\Models\Settings;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $types = Settings::where('id', 1)->value('extend');
        $typesStatus = Settings::where('id', 2)->value('extend');
        return [
            'code' => $this->code,
            'menu' => $this->menus,
            'type' => $types[$this->meal_type] ?: $this->meal_type,
            'status' => $typesStatus[$this->status] ?: $this->status,
            'useDate' => $this->used_at,
            'startAt' => $this->start_at,
            'endAt' => $this->end_at,
            'stopBookAt' => $this->book_limited_at
        ];
    }
}
