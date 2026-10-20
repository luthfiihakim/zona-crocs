<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['M4/W6', '36-37', 22],
            ['M5/W7', '37-38', 23],
            ['M6/W8', '38-39', 24],
            ['M7/W9', '39-40', 25],
            ['M8/W10', '41-42', 26],
            ['M9/W11', '42-43', 27],
            ['M10/W12', '43-44', 28],
            ['M11', '44-45', 28.5],
            ['M12', '45-46', 29],
        ];

        foreach ($sizes as $i => [$label, $eu, $insole]) {
            Size::create([
                'us_label' => $label,
                'eu_range' => $eu,
                'insole_cm' => $insole,
                'group' => 'adult',
                'is_preorder' => in_array($label, ['M11', 'M12']),
                'sort_order' => $i + 1,
            ]);
        }
    }
}