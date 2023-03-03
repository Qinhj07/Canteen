<?php

namespace App\Http\Controllers\Api\Canteen;

use App\Http\Controllers\Controller;
use App\Http\Traits\StandardResponse;
use App\Models\Canteen\MenuLists;
use App\Models\Canteen\Receipts;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MenuApiController extends Controller
{
    use StandardResponse;

    public function getWeeklyMenu()
    {
        $menus = MenuLists::whereBetween('use_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
        $menusNext = MenuLists::whereBetween('use_at', [Carbon::now()->nextWeekday(), Carbon::parse('+ 7 days')->endOfWeek()])->get();
        $ret = [];
        $menus->each(function ($item) use (&$ret) {
            $ret['thisWeek'][] = [$item->use_at,
                object_get($item, 'breakfast', '暂无菜单'),
                object_get($item, 'lunch', '暂无菜单'),
                object_get($item, 'dinner', '暂无菜单')
            ];
        });
        $menusNext->each(function ($item) use (&$ret) {
            $ret['nextWeek'][] = [$item->use_at,
                object_get($item, 'breakfast', '暂无菜单'),
                object_get($item, 'lunch', '暂无菜单'),
                object_get($item, 'dinner', '暂无菜单')
            ];
        });
        return $this->standardResponse([2000, 'success', $ret]);
    }

    public function getTodaySchedule()
    {
        $receipt = Receipts::where('used_at', Carbon::now()->toDateString())->orderBy('meal_type')->get();
        $ret = [];
        $receipt->each(function ($item) use (&$ret) {
            $ret[] = ["startAt" => $item->start_at, "endAt" => $item->end_at];
        });
        return $this->standardResponse([2000, 'success', $ret]);
    }

    public function getAnnouncement()
    {
        $announcement = Settings::where('name', 'announcement')->value('value');
        return $this->standardResponse([
            2000,
            'success',
            $announcement
        ]);

    }
}
