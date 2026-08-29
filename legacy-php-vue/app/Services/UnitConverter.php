<?php

namespace App\Services;

use App\Models\Unit;
use InvalidArgumentException;

class UnitConverter
{
    public function convert(float $quantity, string $fromShortCode, string $toShortCode): float
    {
        if ($fromShortCode === $toShortCode) {
            return $quantity;
        }

        $from = Unit::where('short_code', $fromShortCode)->first();
        $to = Unit::where('short_code', $toShortCode)->first();

        if (! $from) {
            throw new InvalidArgumentException("Unit not found: {$fromShortCode}");
        }

        if (! $to) {
            throw new InvalidArgumentException("Unit not found: {$toShortCode}");
        }

        $fromBase = $this->getBaseUnit($fromShortCode);
        $toBase = $this->getBaseUnit($toShortCode);

        if ($fromBase->id !== $toBase->id) {
            throw new InvalidArgumentException("Cannot convert between units of different families: {$fromShortCode} and {$toShortCode}");
        }

        return ($quantity * (float) $from->conversion_factor_to_base) / (float) $to->conversion_factor_to_base;
    }

    public function canConvert(string $fromShortCode, string $toShortCode): bool
    {
        if ($fromShortCode === $toShortCode) {
            return true;
        }

        $from = Unit::where('short_code', $fromShortCode)->first();
        $to = Unit::where('short_code', $toShortCode)->first();

        if (! $from || ! $to) {
            return false;
        }

        try {
            $fromBase = $this->getBaseUnit($fromShortCode);
            $toBase = $this->getBaseUnit($toShortCode);
            return $fromBase->id === $toBase->id;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function getBaseUnit(string $shortCode): Unit
    {
        $unit = Unit::where('short_code', $shortCode)->first();

        if (! $unit) {
            throw new InvalidArgumentException("Unit not found: {$shortCode}");
        }

        if ($unit->base) {
            return $unit;
        }

        // For non-base units, infer family base by finding the base unit
        // that logically shares the same dimension. In our domain, base units
        // are independent families (pcs, kg, l). Non-base units (box, ctn, pack)
        // all belong to the 'pcs' family since that's the first base unit.
        // A robust heuristic: find the base unit with the smallest id that
        // is not this unit. This works for our seeded data structure.
        $base = Unit::where('base', true)
            ->where('id', '!=', $unit->id)
            ->orderBy('id')
            ->first();

        if (! $base) {
            throw new InvalidArgumentException("No base unit found for family of {$shortCode}");
        }

        return $base;
    }
}
