<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAccount extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'admin@nutrylyfe.com',
            'password' => Hash::make('password'),
            'username' => 'admin',
            'phone' => '08167236629',
            'user_type' => 'Admin',
            'status' => '1',
            'isActive' => true,
            'my_ref_id' => 'NL-ADMIN001',
            'state' => 'Lagos',
            'address' => 'Nutry Lyfe HQ',
        ]);
    }
}
