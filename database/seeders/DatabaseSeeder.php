<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PreferenceSeeder::class);
        $this->call(CommunicationSeeder::class);
        $this->call(UserManagementSeeder::class);
        $this->call(CountryUsersSeeder::class);
    }
}
