<?php

namespace Database\Factories;

use App\Enums\DocumentRequestStatus;
use App\Models\Course;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentRequestFactory extends Factory
{
    protected $model = DocumentRequest::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'creator_id' => User::factory(),
            'title' => 'Cần tìm ' . fake('vi_VN')->sentence(4),
            'description' => fake('vi_VN')->paragraph(2),
            'lecturer_name' => 'ThS. ' . fake('vi_VN')->name(),
            'category' => fake()->randomElement(['EXAM', 'LECTURE', 'PROJECT', 'EXERCISE']),
            'bounty_points' => fake()->randomElement([20, 50, 100, 200]),
            'status' => DocumentRequestStatus::OPEN,
            'fulfilled_document_id' => null,
            'confirmed_by' => null,
            'confirmed_at' => null,
        ];
    }
}