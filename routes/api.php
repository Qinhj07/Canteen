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
        $router->post('/getBalance', "BalanceApiController@getMyBalance");
        $router->post('/charge', "BalanceApiController@charge");
        $router->post('/checkCharge', "BalanceApiController@checkCharge");
        $router->middleware(CheckLimits::class)->post('/chargeLogs', "BalanceApiController@getBalanceLog");
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
        $router->middleware(CheckLimits::class)->post('/other', "OrderController@getExtraOrder");
        $router->get('/tradeCnt/{type}', "TradeApiController@getOnTradeOrderNum");
        $router->get('/tradeList/{mealType}/{mealName}', "TradeApiController@getTradeOrderList");
        $router->post('/getQrcode', "OrderController@getQrcode");
        $router->post('/buyOrder', "TradeApiController@buyTradeOrder");
        $router->post('/errorOrder', "PayOrderController@getPayErrorOrder");
        $router->post('/refund', "OrderController@withdrawal");
        $router->post('/cancelOrder', "TradeApiController@cancelUnPayOrder");
        $router->get('/use/{code}', "OrderController@useOrder");
        $router->get('/getUse', "SellApiController@getUsedOrder");
        $router->get('/geByPhone/{phone}', "SellApiController@findByPhone");
        $router->get('/getCount', "SellApiController@getCount");
    });

Route::namespace("App\Http\Controllers\Api\Canteen")
    ->prefix("menu")
    ->group(function (Router $router) {
        $router->get("menus", "MenuApiController@getWeeklyMenu");
        $router->get("schedule", "MenuApiController@getTodaySchedule");
        $router->get("announce", "MenuApiController@getAnnouncement");
    });
