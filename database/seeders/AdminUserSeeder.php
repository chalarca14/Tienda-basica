<?php
// database/seeders/AdminUserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder {
    public function run(): void {
        User::updateOrCreate(
            ['email' => 'admin@tienda.com'],
            [
                'name'     => 'Administrador',
                'email'    => 'admin@tienda.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );
        echo "✅ Admin creado: admin@tienda.com / admin123\n";
    }
}
