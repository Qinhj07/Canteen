<?php

namespace App\Models\Canteen;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class PayOrders extends Model
{
    // 支付订单
    use HasFactory, SoftDeletes;

    public function ownOrder(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Orders::class, 'oid', 'order_id');
    }

    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)->timezone("Asia/Shanghai")->toDateTimeString();
    }

    public function getCheckAtAttribute($value): string
    {
        return Carbon::parse($value)->timezone("Asia/Shanghai")->toDateTimeString();
    }
}
