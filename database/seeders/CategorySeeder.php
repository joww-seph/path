<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'attraction', 'name' => 'Attractions', 'icon' => 'landmark', 'color' => '#9c5a2e'],
            ['slug' => 'food', 'name' => 'Food places', 'icon' => 'utensils', 'color' => '#c2410c'],
            ['slug' => 'lodging', 'name' => 'Lodging', 'icon' => 'bed-double', 'color' => '#4f7fae'],
            ['slug' => 'activity', 'name' => 'Activities', 'icon' => 'mountain', 'color' => '#b45309'],
            ['slug' => 'tour', 'name' => 'Guided tours', 'icon' => 'flag', 'color' => '#15803d'],
            ['slug' => 'shop', 'name' => 'Pasalubong and shops', 'icon' => 'shopping-bag', 'color' => '#a21caf'],
            ['slug' => 'transport', 'name' => 'Transport', 'icon' => 'car', 'color' => '#222d60'],
            ['slug' => 'service', 'name' => 'Services', 'icon' => 'info', 'color' => '#475569'],
        ];

        foreach ($categories as $position => $category) {
            Category::updateOrCreate(['slug' => $category['slug']], [...$category, 'position' => $position]);
        }
    }
}
