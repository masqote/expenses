<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Masqote',  'email' => 'masqote@gmail.com',  'password' => '12345678'],
            ['name' => 'Widyanuf', 'email' => 'widyanuf@gmail.com', 'password' => '12345678'],
            ['name' => 'Masqote1', 'email' => 'masqote1@gmail.com', 'password' => '12345678'],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make($data['password'])]
            );
        }
    }
}
