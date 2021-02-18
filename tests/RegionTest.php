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
}
