<?php

namespace App\Models\Canteen;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Orders extends Model
{
    // 订餐订单
    use HasFactory, SoftDeletes;

    public $casts =[
        'items' => 'json'
    ];

    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)->timezone("Asia/Shanghai")->toDateTimeString();
    }

    public function orderX(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PayOrders::class, 'oid', 'order_id');
    }

    public function receiptX(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Receipts::class, 'receipt_id', 'code');
    }
}
