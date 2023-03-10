<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    use HasFactory;

    public function amount()
    {
        return $this->hasOne(Balance::class, 'openid', 'openid');
    }
}
