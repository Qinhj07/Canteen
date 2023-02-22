<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RuntimeException;

class DecryptPas
{
    /**
     * Decrypt Pas, Merge Decrypt Param into Request Params
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('post')){
            $privateKey = "r9C9PFwAvG0P3qP7";
            $iv = "r9C9PFwAvG0P3qP7";
            if (!isset($request->pas)) {
                throw new RuntimeException("NoEncrypted", 4000);
            }
            $ret = openssl_decrypt($request->pas, "AES-128-CBC", $privateKey, OPENSSL_ZERO_PADDING, $iv);
            $pRet = json_decode(trim($ret), true);
            if (!is_array($pRet) || !isset($pRet)) {
                throw new RuntimeException("ParamError", 4000);
            }
            $request->attributes->add($pRet);
            unset($request['pas']);
            return $next($request);
        }elseif ($request->isMethod('get')){
            return $next($request);
        }else{
            throw new RuntimeException("MethodError", 4000);
        }
    }
}
