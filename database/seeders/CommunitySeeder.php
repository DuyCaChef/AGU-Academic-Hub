<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\DocumentRequest;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo Khoa CNTT mặc định nếu chưa có
        $faculty = Faculty::firstOrCreate(
            ['code' => 'CNTT'],
            [
                'name' => 'Khoa Công nghệ Thông tin',
                'description' => 'Khoa CNTT - Đại học An Giang'
            ]
        );

        // 2. Tạo tài khoản Admin mặc định
        $admin = User::firstOrCreate(
            ['email' => 'admin@agu.edu.vn'],
            [
                'faculty_id' => $faculty->id,
                'full_name' => 'Quản trị viên Hệ thống',
                'password' => Hash::make('Admin@123456'),
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        // 3. Tạo tài khoản Moderator mặc định
        $mod = User::firstOrCreate(
            ['email' => 'mod@agu.edu.vn'],
            [
                'faculty_id' => $faculty->id,
                'full_name' => 'Ban Học Tập Moderation',
                'password' => Hash::make('Mod@123456'),
                'role' => UserRole::MODERATOR,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        // 4. Tạo tài khoản Sinh viên kiểm thử
        $student = User::firstOrCreate(
            ['email' => 'duy_tester@student.agu.edu.vn'],
            [
                'faculty_id' => $faculty->id,
                'full_name' => 'Khánh Duy Tester',
                'student_code' => 'DTH215001',
                'password' => Hash::make('Duy@123456'),
                'role' => UserRole::STUDENT,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        // 5. Tạo 10 Sinh viên ngẫu nhiên bằng Factory
        $users = User::factory()->count(10)->create();

        // 6. Tạo dữ liệu Review và Wishlist mẫu cho các môn học có sẵn
        $courses = Course::take(5)->get();
        if ($courses->isNotEmpty()) {
            foreach ($courses as $course) {
                // Tạo review mẫu (mỗi user 1 review / học kỳ để thỏa mãn unique key)
                CourseReview::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'user_id' => $student->id,
                        'academic_term' => '2025-2',
                    ],
                    [
                        'difficulty_score' => 4,
                        'practicality_score' => 5,
                        'workload_score' => 3,
                        'content' => 'Môn học rất thực tế, giảng viên nhiệt tình hỗ trợ thực hành.',
                        'is_anonymous' => true,
                    ]
                );

                // Tạo Wishlist mẫu
                DocumentRequest::create([
                    'course_id' => $course->id,
                    'creator_id' => $student->id,
                    'title' => "Xin đề thi kết thúc học phần môn {$course->name}",
                    'description' => 'Cần xin file PDF lời giải đề thi cuối kỳ đợt 1 năm học vừa rồi.',
                    'lecturer_name' => 'Ban Giảng viên Bộ môn',
                    'category' => 'EXAM',
                    'bounty_points' => 50,
                ]);
            }
        }
    }
}