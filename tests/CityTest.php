<?php

namespace Ghaffaru\GhCities\Tests;

use Ghaffaru\GhCities\City;
use PHPUnit\Framework\TestCase;

class CityTest extends TestCase
{
    /** @test */
    public function it_can_return_all_cities()
    {
        $cities = City::all();

        $this->assertIsArray($cities);
    }
}