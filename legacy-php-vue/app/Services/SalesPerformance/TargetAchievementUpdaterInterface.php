<?php

namespace App\Services\SalesPerformance;

use App\Models\Sale;

interface TargetAchievementUpdaterInterface
{
    public function applySale(Sale $sale): void;

    public function reverseSale(Sale $sale): void;
}