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
            KhoaSeeder::class,
            ChuyenNganhSeeder::class,
            MonHocSeeder::class,
            NguoiDungSeeder::class,
            TaiLieuSeeder::class,
            BaoCaoViPhamSeeder::class,
            CommunitySeeder::class,
        ]);
    }
}