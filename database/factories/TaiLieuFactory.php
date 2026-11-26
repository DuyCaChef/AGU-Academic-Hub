<?php

namespace Database\Factories;

use App\Models\TaiLieu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaiLieu>
 */
class TaiLieuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tieu_de' => fake()->sentence(4),
            'duong_dan_tep' => 'documents/' . fake()->uuid() . '.pdf',
            'dinh_dang_tep' => 'pdf',
            'dung_luong_kb' => fake()->numberBetween(100, 5000),
            'ma_bam_file' => fake()->sha256(),
            'phan_loai' => 'DE_THI',
            'trang_thai' => 'DA_DUYET',
            'phien_ban' => '1.0',
            'luot_tai' => 0,
            'luot_thich' => 0,
        ];
    }
}
