<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed a default Filament admin user.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'allan@logicmedia.be'],
            [
                'name' => 'Allan (Admin)',
                'password' => Hash::make('123456'),
            ]
        );
    }
}
