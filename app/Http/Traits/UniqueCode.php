<?php


namespace App\Http\Traits;


trait UniqueCode
{
    public function get16OrderCode(string $oid)
    {
        return strtoupper(time() . mb_substr($oid, random_int(0, strlen($oid) - 8), 7) . random_int(0, 9));
    }

    public function get32RamdonCode(string $oid)
    {
        return strtoupper(md5(uniqid(md5($oid . time()), true)));
    }
}
