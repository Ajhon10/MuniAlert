<?php

namespace App\Support;

final class Location
{
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lng2 - $lng1) / 2) ** 2;

        return round(6371000 * 2 * atan2(sqrt($a), sqrt(max(0, 1 - $a))), 1);
    }
}
