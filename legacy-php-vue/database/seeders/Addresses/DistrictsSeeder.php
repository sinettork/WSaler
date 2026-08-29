<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\District;
use App\Models\Addresses\Province;
use Illuminate\Database\Seeder;

class DistrictsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/kh-addresses.json');
        if (!file_exists($path)) {
            $this->command->warn("kh-addresses.json not found at {$path}; skipping DistrictsSeeder.");
            return;
        }

        $rows = json_decode(file_get_contents($path), true);
        $districts = [];
        foreach ($rows as $r) {
            $province = Province::where('code', $r['p_code'])->first();
            if (!$province) continue;
            $key = $r['p_code'].'-'.$r['d_code'];
            $districts[$key] = [
                'province_id' => $province->id,
                'code' => $r['d_code'],
                'name_en' => $r['d_name_en'],
                'name_km' => $r['d_name_km'],
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($districts as $data) {
            District::updateOrCreate(
                ['province_id' => $data['province_id'], 'code' => $data['code']],
                $data
            );
        }
    }
}
