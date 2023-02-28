<?php

namespace App\Http\Traits;

use App\Models\Base\Users;

trait CheckUser
{
    public function checkUserByOpenid(string $openid): bool
    {
        return Users::where('openid', $openid)->doesntExist();
    }

    public function checkUserByPhone(string $phone): bool
    {
        return Users::where('phone', $phone)->doesntExist();
    }
}
