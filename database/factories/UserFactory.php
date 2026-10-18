<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        // 1. Danh sách tiền tố ngành đào tạo thực tế tại AGU
        $majorPrefixes = [
            'DTH', // Đại học Công nghệ thông tin 
            'DPM', // Đại học Kỹ thuật phần mềm
            'DMK', // Đại học Marketing
            'DQT', // Đại học Quản trị kinh doanh
            'DTC', // Đại học Tài chính - Ngân hàng
            'DKT', // Đại học Kế toán
            'DTA', // Đại học Ngôn ngữ Anh / Sư phạm Tiếng Anh
            'DLU', // Đại học Luật
            'DTP', // Đại học Công nghệ thực phẩm
            'DNN', // Đại học Nông nghiệp
            'DBT', // Đại học Bảo vệ thực vật
            'DTS', // Đại học Thủy sản
            'DMN', // Đại học Giáo dục Mầm non
            'DGD', // Đại học Giáo dục Tiểu học
        ];

        // 2. Chọn ngẫu nhiên ngành
        $prefix = fake()->randomElement($majorPrefixes);

        // 3. Khóa tuyển sinh: 2 số cuối của năm (2020 - 2025)
        $cohortYear = fake()->numberBetween(20, 25);

        // 4. Số thứ tự hồ sơ (unique để tránh trùng MSSV)
        $studentIndex = fake()->unique()->numberBetween(1000, 9999);

        // Ghép thành MSSV chuẩn AGU (ví dụ: DTH221234, DMK234567)
        $studentCode = "{$prefix}{$cohortYear}{$studentIndex}";

        // Email sinh viên: MSSV chữ thường + @student.agu.edu.vn
        $email = strtolower($studentCode) . '@student.agu.edu.vn';

        return [
            'faculty_id' => Faculty::inRandomOrder()->value('id') ?? null,
            'full_name' => fake('vi_VN')->name(),
            'email' => $email,
            'student_code' => $studentCode,
            'password' => static::$password ??= Hash::make('password123'),
            'role' => UserRole::STUDENT,
            'status' => UserStatus::ACTIVE,
            'reputation_score' => 100,
            'avatar_path' => null,
            'email_verified_at' => now(),
            'graduation_year' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Tài khoản Quản trị viên (dùng domain trường @agu.edu.vn)
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::ADMIN,
            'email' => 'admin@agu.edu.vn',
            'student_code' => null,
            'faculty_id' => null,
        ]);
    }

    /**
     * Tài khoản Kiểm duyệt viên / Ban học tập (dùng domain @agu.edu.vn)
     */
    public function moderator(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::MODERATOR,
            'email' => 'moderator@agu.edu.vn',
            'student_code' => null,
        ]);
    }

    /**
     * Tài khoản Cựu sinh viên
     */
    public function alumni(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => UserRole::ALUMNI,
            'graduation_year' => fake()->numberBetween(2020, 2024),
        ]);
    }
}