<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLimits
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->has('start')) {
            $request->start = 0;
        }
        if (!$request->has('end')) {
            $request->end = 10;
        }
        if ($request->end > 100) {
            $request->end = 100;
        }
        return $next($request);
    }
}
