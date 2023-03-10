<?php

namespace App\Models\Canteen;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Receipts extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'menus' => "3",
    ];

    protected $casts = [
        'menus' => 'json',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone("Asia/Shanghai")->toDateTimeString();
    }
}
