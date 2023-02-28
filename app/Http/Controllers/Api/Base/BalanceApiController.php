<?php

namespace App\Http\Controllers\Api\Base;

use App\Http\Controllers\Controller;
use App\Http\Traits\CheckUser;
use App\Http\Traits\StandardResponse;
use App\Models\Base\Balance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class BalanceApiController extends Controller
{
    use StandardResponse, CheckUser;

    public function getMyBalance(Request $request)
    {
        $paramEnum = ['openid', 'phone'];
        foreach ($paramEnum as $key => $value) {
            if (blank($request->get($value))) {
                return $this->standardResponse([4001, "No {$value} Error",]);
            }
        }
        if ($this->checkUserByOpenid($request->get('openid'))){
            return $this->standardResponse([4004, "NoUserError",]);
        }
        try {
            $balance = Balance::where('openid', $request->get('openid'))->firstOrFail();
        }catch (ModelNotFoundException $exception){
            $balance = new Balance();
            $balance->openid = $request->get('openid');
            $balance->amount = 0.00;
            $balance->phone = $request->get('phone');
            $balance->save();
        }
        return $this->standardResponse([2000, 'success', $balance->amount]);
    }


}
