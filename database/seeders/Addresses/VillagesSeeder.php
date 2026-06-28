<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\Commune;
use App\Models\Addresses\Village;
use Illuminate\Database\Seeder;

class VillagesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/kh-addresses.json');
        if (!file_exists($path)) {
            $this->command->warn("kh-addresses.json not found at {$path}; skipping VillagesSeeder.");
            return;
        }

        $rows = json_decode(file_get_contents($path), true);

        $now = now();
        $batch = [];
        $communeCache = [];

        foreach ($rows as $r) {
            $communeKey = $r['d_code'].'-'.$r['c_code'];
            if (!isset($communeCache[$communeKey])) {
                $communeCache[$communeKey] = Commune::where('code', $r['c_code'])->value('id');
            }
            $communeId = $communeCache[$communeKey];
            if (!$communeId) continue;

            $batch[] = [
                'commune_id' => $communeId,
                'code' => $r['v_code'],
                'name_en' => $r['v_name_en'],
                'name_km' => $r['v_name_km'],
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 500) {
                Village::upsert($batch, ['commune_id', 'code'], ['name_en', 'name_km', 'updated_at']);
                $batch = [];
            }
        }

        if ($batch) {
            Village::upsert($batch, ['commune_id', 'code'], ['name_en', 'name_km', 'updated_at']);
        }
    }
}
