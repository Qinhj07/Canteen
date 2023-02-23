<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use HasFactory;

    public function userx()
    {
        return $this->belongsTo(Users::class, 'openid', 'openid');
    }
}
