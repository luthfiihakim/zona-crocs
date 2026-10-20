<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Owner Zona Crocs', 'admin@zonacrocs.test', 'admin'],
            ['Staff Kasir', 'staff@zonacrocs.test', 'staff'],
            ['Customer Demo', 'customer@zonacrocs.test', 'customer'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::forceCreate([
                'name' => $name,
                'email' => $email,
                'password' => 'password123',
                'role' => $role,
                'email_verified_at' => now(),
            ]);
        }
    }
}