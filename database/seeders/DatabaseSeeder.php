<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CatalogosBaseSeeder::class,  // Catálogos de lookup (tipos, condiciones, etc.)
            SuperAdminSeeder::class,     // Usuario admin + todos los permisos + menú
        ]);
    }
}
