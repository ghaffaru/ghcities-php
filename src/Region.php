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
            $allRegions[] = $key;
        }

        return $allRegions;
    }

    public static function getCities($region)
	{
		$json = json_decode(file_get_contents(__DIR__ . '/cities.json'));

		$cities = [];

		foreach ($json->$region as $key => $value)
		{
			$cities[] = $value;
		}

		return $cities;
	}
}