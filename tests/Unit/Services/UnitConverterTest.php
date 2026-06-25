<?php

namespace Tests\Unit\Services;

use App\Models\Unit;
use App\Services\UnitConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UnitConverterTest extends TestCase
{
    use RefreshDatabase;

    protected UnitConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new UnitConverter();

        Unit::create(['name' => 'Piece', 'short_code' => 'pcs', 'base' => true, 'conversion_factor_to_base' => 1]);
        Unit::create(['name' => 'Box', 'short_code' => 'box', 'base' => false, 'conversion_factor_to_base' => 6]);
        Unit::create(['name' => 'Carton', 'short_code' => 'ctn', 'base' => false, 'conversion_factor_to_base' => 24]);
        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg', 'base' => true, 'conversion_factor_to_base' => 1]);
    }

    public function test_convert_ctn_to_pcs(): void
    {
        $result = $this->converter->convert(1, 'ctn', 'pcs');
        $this->assertEqualsWithDelta(24.0, $result, 0.0001);
    }

    public function test_convert_box_to_pcs(): void
    {
        $result = $this->converter->convert(1, 'box', 'pcs');
        $this->assertEqualsWithDelta(6.0, $result, 0.0001);
    }

    public function test_convert_ctn_to_box(): void
    {
        $result = $this->converter->convert(1, 'ctn', 'box');
        $this->assertEqualsWithDelta(4.0, $result, 0.0001);
    }

    public function test_convert_ctn_to_pcs_decimal(): void
    {
        $result = $this->converter->convert(2.5, 'ctn', 'pcs');
        $this->assertEqualsWithDelta(60.0, $result, 0.0001);
    }

    public function test_convert_pcs_to_ctn(): void
    {
        $result = $this->converter->convert(60, 'pcs', 'ctn');
        $this->assertEqualsWithDelta(2.5, $result, 0.0001);
    }

    public function test_same_unit_returns_same_quantity(): void
    {
        $result = $this->converter->convert(100, 'pcs', 'pcs');
        $this->assertEqualsWithDelta(100.0, $result, 0.0001);
    }

    public function test_cross_family_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->converter->convert(1, 'kg', 'pcs');
    }

    public function test_canConvert_same_unit(): void
    {
        $this->assertTrue($this->converter->canConvert('pcs', 'pcs'));
    }

    public function test_canConvert_same_family(): void
    {
        $this->assertTrue($this->converter->canConvert('box', 'ctn'));
    }

    public function test_canConvert_different_family(): void
    {
        $this->assertFalse($this->converter->canConvert('kg', 'pcs'));
    }

    public function test_getBaseUnit_for_base_returns_itself(): void
    {
        $base = $this->converter->getBaseUnit('pcs');
        $this->assertEquals('pcs', $base->short_code);
    }

    public function test_getBaseUnit_for_non_base_returns_family_base(): void
    {
        $base = $this->converter->getBaseUnit('box');
        $this->assertEquals('pcs', $base->short_code);
    }
}
