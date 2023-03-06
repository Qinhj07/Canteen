<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class BalanceLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $orderType = [1 => "充值", 2 => "支付", 3 => "其他"];
        $status = [0 => "未支付", 1 => "已支付", 8 => "支付异常", 9 => "已退款"];
        return [
            'orderId' => $this->order_id,
            'price' => $this->amount,
            'realPrice' => $this->real_pay,
            'type' => $orderType[$this->type] ?: "其他",
            'status' => $status[$this->status] ?: "其他",
            'comment' => $this->comment ?: "无",
            'chargeAt' => Carbon::parse($this->created_at)->toDateTimeString()
        ];
    }
}
