<?php

namespace Database\Factories;

use App\Enums\CourseReviewStatus;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseReviewFactory extends Factory
{
    protected $model = CourseReview::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id' => User::factory(),
            'academic_term' => fake()->randomElement(['2025-1', '2025-2', '2026-1']),
            'difficulty_score' => fake()->numberBetween(1, 5),
            'practicality_score' => fake()->numberBetween(1, 5),
            'workload_score' => fake()->numberBetween(1, 5),
            'content' => fake('vi_VN')->paragraph(3),
            'is_anonymous' => fake()->boolean(70), // 70% chọn ẩn danh
            'status' => CourseReviewStatus::PUBLISHED,
        ];
    }
}