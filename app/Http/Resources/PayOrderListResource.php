<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayOrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $status = [1 => "未支付", 2 => "已支付", 9 => "支付异常"];
        return [
            'orderId' => $this->order_id,
            'price' => $this->price,
            'payPrice' => $this->real_pay,
            'payMethod' => $this->pay_type == 1 ? "微信支付" : "余额支付",
            'status' => $status[$this->status],
            'tradeAt' => $this->created_at,
        ];
    }
}
