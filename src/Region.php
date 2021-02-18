<?php

namespace Ghaffaru\GhCities;

class Region
{
    public static function all()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/cities.json'));

        $allRegions = [];

        foreach ($json as $key => $value)
        {
            array_push($allRegions, $key);
        }

        return $allRegions;
    }
}