<?php

namespace App\Http\Controllers\Api\Base;

use App\Http\Controllers\Controller;
use App\Models\Base\Users;
use App\Models\Settings;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserApiController extends Controller
{
    public function login(Request $request)
    {
        $params = ['openid', 'phone'];
        foreach ($params as $param) {
            if (!$request->get($param)) {
                return response()->json(['code' => 4003, 'msg' => "No_{$param}_Error"]);
            }
        }
        try {
            $user = Users::where('openid', $request->get('openid'))->firstOrFail();
        } catch (ModelNotFoundException $e) {
            try {
                $user = Users::where('phone', $request->get('phone'))->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return response()->json([
                    'code' => 4004,
                    'msg' => "NoUserError"
                ]);
            }
            if ($request->get('nickname')) {
                $user->nickname = $request->get('nickname');
            }
            $user->openid = $request->get('openid');
            if ($request->get('avatar')) {
                $user->avatar = $request->get('avatar');
            }
            $user->save();
            // $user->nickname = $request->get('nickname');
        }

        if ($request->get('nickname')) {
            $user->nickname = $request->get('nickname');
        }
        $user->openid = $request->get('openid');
        if ($request->get('avatar')) {
            $user->avatar = $request->get('avatar');
        }
        $user->save();
        return response()->json([
            'code' => 2000,
            'msg' => "success",
            'data' => [
                'name' => $user->name,
                'nickname' => $user->nickname,
                'openid' => $user->openid,
                'mobile' => $user->phone
            ]
        ]);
    }

    public function GetPhone(Request $request)
    {
//        $appid = "wx8ab8f74af695e360";
        $appid = Settings::where('name', 'appId')->value('value'); //env('APP_ID', '');
        $secret = Settings::where('name', 'appKey')->value('value'); //env('APP_ID', '');
//        $secret = env('APP_KEY', '');
//        $secret = "f4c8abfbf7b04b2100dfa3b9e0b129a0";
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";

        $response = Http::withHeaders([
            'Accept' => 'application/json'
        ])->get($url);
        $token = $response->json()['access_token'];
        //使用code获取号码
        $urls = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$token}";
        $post_mobile = [
            'code' => $request->get('codes')
        ];
        $getP = Http::post($urls, $post_mobile);


        //使用code获取openid
        $code_openid = $request->get('openid');
        $urlo = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code_openid}&grant_type=authorization_code";
        $getOpenid = Http::withHeaders([
            'Accept' => 'application/json'
        ])->get($urlo);
        $getO = $getOpenid->json()['openid'];
        // return $getO;
        return response()->json([
            'code' => 2000,
            'msg' => "success",
            'data' => [
                'mobile' => $getP['phone_info']['phoneNumber'],
                'openid' => $getO
            ]
        ]);
    }
}
