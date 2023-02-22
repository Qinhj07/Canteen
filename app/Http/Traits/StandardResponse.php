<?php


namespace App\Http\Traits;

trait StandardResponse
{
    public function standardResponse(array $data): \Illuminate\Http\JsonResponse
    {
        if (sizeof($data) == 3) {
            return response()->json([
                'code' => $data[0],
                'msg' => $data[1],
                'data' => $data[2]
            ]);
        } else {
            return response()->json([
                'code' => $data[0],
                'msg' => $data[1],
            ]);
        }
    }
}
