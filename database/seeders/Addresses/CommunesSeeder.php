<?php

namespace Database\Seeders\Addresses;

use App\Models\Addresses\Commune;
use App\Models\Addresses\District;
use Illuminate\Database\Seeder;

class CommunesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/kh-addresses.json');
        if (!file_exists($path)) {
            $this->command->warn("kh-addresses.json not found at {$path}; skipping CommunesSeeder.");
            return;
        }

        $rows = json_decode(file_get_contents($path), true);
        $communes = [];
        foreach ($rows as $r) {
            $district = District::where('code', $r['d_code'])->first();
            if (!$district) continue;
            $key = $r['d_code'].'-'.$r['c_code'];
            $communes[$key] = [
                'district_id' => $district->id,
                'code' => $r['c_code'],
                'name_en' => $r['c_name_en'],
                'name_km' => $r['c_name_km'],
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($communes as $data) {
            Commune::updateOrCreate(
                ['district_id' => $data['district_id'], 'code' => $data['code']],
                $data
            );
        }
    }
}
