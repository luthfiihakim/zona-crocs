<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::create([
            'name' => 'Supplier Contoh A',
            'contact_person' => 'Bapak Andi',
            'phone' => '081200000001',
            'address' => 'Medan, Sumatera Utara',
        ]);

        Supplier::create([
            'name' => 'Supplier Contoh B',
            'contact_person' => 'Ibu Sari',
            'phone' => '081200000002',
            'address' => 'Jakarta',
        ]);
    }
}