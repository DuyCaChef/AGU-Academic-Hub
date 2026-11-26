<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KhoaSeeder extends Seeder
{
    public function run(): void
    {
        $khoas = [
            ['ky_hieu_khoa' => 'CNTT', 'ten_khoa' => 'Khoa Công nghệ thông tin'],
            ['ky_hieu_khoa' => 'KT', 'ten_khoa' => 'Khoa Kinh tế'],
            ['ky_hieu_khoa' => 'SP', 'ten_khoa' => 'Khoa Sư phạm'],
        ];

        foreach ($khoas as $khoa) {
            DB::table('khoa')->updateOrInsert(
                ['ky_hieu_khoa' => $khoa['ky_hieu_khoa']],
                $khoa + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}