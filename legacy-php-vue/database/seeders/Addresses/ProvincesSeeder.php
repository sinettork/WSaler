<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\Province;
use Illuminate\Database\Seeder;

class ProvincesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/kh-addresses.json');
        if (!file_exists($path)) {
            $this->command->warn("kh-addresses.json not found at {$path}; skipping ProvincesSeeder.");
            return;
        }

        $rows = json_decode(file_get_contents($path), true);
        $provinces = [];
        foreach ($rows as $r) {
            $provinces[$r['p_code']] = [
                'code' => $r['p_code'],
                'name_en' => $r['p_name_en'],
                'name_km' => $r['p_name_km'],
                'type' => $r['p_type'] ?? 'province',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($provinces as $code => $data) {
            Province::updateOrCreate(['code' => $code], $data);
        }
    }
}
