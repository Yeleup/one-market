<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\User;
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
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        Language::factory()->default()->create([
            'code' => 'ru',
            'name' => 'Русский',
            'sort_order' => 0,
        ]);

        Language::factory()->create([
            'code' => 'kk',
            'name' => 'Қазақша',
            'sort_order' => 1,
        ]);

        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'sort_order' => 2,
        ]);
    }
}
