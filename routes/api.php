<?php

use App\Http\Middleware\CheckLimits;
use App\Http\Middleware\DecryptPas;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::middleware(DecryptPas::class)
    ->prefix('user')
    ->namespace("App\Http\Controllers\Api\Base")
    ->group(function (Router $router) {
        $router->post('/login', "UserApiController@login");
        $router->post('/GetPhone', "UserApiController@GetPhone");
    });

Route::middleware(DecryptPas::class)
    ->prefix('meal')
    ->namespace("App\Http\Controllers\Api\Canteen")
    ->group(function (Router $router) {
        $router->get('/bookList/{chosenDate}', "ReceiptApiController@getReceiptList");
        $router->post('/book', "PayOrderController@newPayOrder");
        $router->post('/myBook', "OrderController@myBookOrderByDate");
        $router->post('/myBookDetail', "OrderController@myBookOrderByDateDetail");
        $router->post('/myBookWeek', "OrderController@getWeek");
        $router->post('/myBookMonth', "OrderController@getMonth");
        $router->middleware(CheckLimits::class)->post('/myOrders', "PayOrderController@getMyOrder");
        $router->middleware(CheckLimits::class)->post('/myOrderDetail', "OrderController@getByOid");
        $router->post('/checkPay', "PayOrderController@checkOrder");
        $router->post('/trade', "OrderController@tradeOrder");
//        $router->post('/GetPhone', "UserApiController@GetPhone");
    });
