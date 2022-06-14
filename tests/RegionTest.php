<?php

namespace Ghaffaru\GhCities\Tests;

use Ghaffaru\GhCities\Region;
use PHPUnit\Framework\TestCase;

class RegionTest extends TestCase
{
    /** @test */
    public function it_can_return_all_regions()
    {
        $regions = Region::all();

        $this->assertIsArray($regions);
    }

    /** @test */
    public function it_can_return_a_regions_cities()
    {
        $regions = Region::all();

        foreach ($regions as $region) {
            $cities = Region::getCities($region);

            $this->assertIsArray($cities);
        }
    }
}
