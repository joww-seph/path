<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            PaoayGuideSeeder::class,
            ItineraryTemplateSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                DemoAccountsSeeder::class,
                DemoListingsSeeder::class,
            ]);
        }
    }
}
