<?php

namespace Database\Factories;

use App\Models\Khoa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Khoa>
 */
class KhoaFactory extends Factory
{
    protected $model = Khoa::class;

    public function definition(): array
    {
        return [
            'ky_hieu_khoa' => strtoupper(fake()->unique()->lexify('???')),
            'ten_khoa' => 'Khoa ' . fake('vi_VN')->words(2, true),
        ];
    }
}
