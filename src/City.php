<?php

namespace Ghaffaru\GhCities;

class City
{
    public static function all()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/cities.json'));

        $allCities = [];

        foreach ($json as $key => $value)
        {
            for ($i = 0; $i < count($value); $i++)
            {
                $allCities[] = $value[$i];
            }
        }
        return $allCities;
    }
}