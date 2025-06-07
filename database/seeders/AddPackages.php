<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddPackages extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            ['name' => 'IGNITE', 'price' => 14600, 'pv' => 14],
            ['name' => 'ENHANCED', 'price' => 38600, 'pv' => 36],
            ['name' => 'BIZPRRNEUR', 'price' => 62600, 'pv' => 90],
            ['name' => 'ELITE', 'price' => 122600, 'pv' => 120],
            ['name' => 'BIZPRRNEUR 2', 'price' => 242600, 'pv' => 240],
            ['name' => 'BIZ PRO', 'price' => 482600, 'pv' => 480],
            ['name' => 'PREMIUM', 'price' => 962600, 'pv' => 960],
        ];

        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
