<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ambil semua user yang sudah terdaftar
        $users = User::all();

        // 2. Daftar kategori default
        $defaultCategories = ['Kuliah', 'Kerja', 'Daily'];

        // 3. Loop setiap user
        foreach ($users as $user) {
            // 4. Loop setiap kategori default
            foreach ($defaultCategories as $catName) {
                
                // Gunakan firstOrCreate agar tidak duplikat jika seeder dijalankan 2x
                Category::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $catName
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}