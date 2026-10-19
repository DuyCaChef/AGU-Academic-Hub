<?php

namespace Database\Factories;

use App\Models\Khoa;
use App\Models\MonHoc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonHoc>
 */
class MonHocFactory extends Factory
{
    protected $model = MonHoc::class;

    public function definition(): array
    {
        return [
            'ma_khoa' => Khoa::factory(),
            'ma_hoc_phan' => strtoupper(fake()->unique()->bothify('???###')),
            'ten_mon_hoc' => fake('vi_VN')->words(3, true),
            'so_tin_chi' => fake()->numberBetween(2, 4),
            'mo_ta' => fake('vi_VN')->sentence(),
        ];
    }
}
