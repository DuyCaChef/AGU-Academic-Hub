<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = trim($this->input('email'));
        $password = (string) $this->input('password');

        // 1. Tìm người dùng đã có trong bảng nguoi_dung (theo email hoặc mssv)
        $user = \App\Models\NguoiDung::where('email', strtolower($loginInput))
            ->orWhere('mssv', strtoupper($loginInput))
            ->first();

        // 2. Nếu chưa có trong nguoi_dung, kiểm tra đối soát với bảng dữ liệu thô sinh viên (danh_sach_sinh_vien_raw)
        if (!$user) {
            $rawStudent = \App\Models\DanhSachSinhVienRaw::where('email_sv', strtolower($loginInput))
                ->orWhere('mssv', strtoupper($loginInput))
                ->first();

            // Khớp sinh viên và mật khẩu mặc định được cấp (bcrypt)
            if ($rawStudent && \Illuminate\Support\Facades\Hash::check($password, $rawStudent->mat_khau_mac_dinh)) {
                // Giải quyết ma_khoa ID: nếu bảng raw lưu ký hiệu chuỗi (VD: 'CNTT'), tra cứu ra ID số của bảng khoa
                $maKhoaId = null;
                if (!empty($rawStudent->ma_khoa)) {
                    if (is_numeric($rawStudent->ma_khoa)) {
                        $maKhoaId = (int) $rawStudent->ma_khoa;
                    } else {
                        $maKhoaId = \App\Models\Khoa::where('ky_hieu_khoa', strtoupper(trim($rawStudent->ma_khoa)))
                            ->value('ma_khoa');
                    }
                }

                // Kiểm tra an toàn xem MSSV này đã có bản ghi trong nguoi_dung chưa
                $existingUserByMssv = \App\Models\NguoiDung::where('mssv', strtoupper($rawStudent->mssv))->first();
                if ($existingUserByMssv) {
                    $user = $existingUserByMssv;
                } else {
                    // Tự động tạo bản ghi người dùng mới với trạng thái CHỜ XÁC THỰC (đổi MK lần đầu)
                    $user = \App\Models\NguoiDung::create([
                        'ho_ten'     => $rawStudent->ho_ten,
                        'mssv'       => strtoupper($rawStudent->mssv),
                        'email'      => $rawStudent->email_sv ?? (strtolower($rawStudent->mssv) . '@student.agu.edu.vn'),
                        'mat_khau'   => $password, // model NguoiDung cast 'mat_khau' => 'hashed'
                        'ma_khoa'    => $maKhoaId,
                        'vai_tro'    => \App\Enums\VaiTroNguoiDung::SINH_VIEN,
                        'trang_thai' => \App\Enums\TrangThaiNguoiDung::CHUA_XAC_THUC,
                        'diem_uy_tin'=> 100,
                    ]);
                }
            }
        }

        // 3. Nếu không tìm thấy hoặc mật khẩu không khớp
        if (!$user || !\Illuminate\Support\Facades\Hash::check($password, $user->mat_khau)) {
            \Illuminate\Support\Facades\RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập hoặc mật khẩu không chính xác.',
            ]);
        }

        // 4. Kiểm tra trạng thái cựu sinh viên đang chờ duyệt hồ sơ KYC
        if (
            $user->vai_tro === \App\Enums\VaiTroNguoiDung::CUU_SINH_VIEN &&
            $user->trang_thai === \App\Enums\TrangThaiNguoiDung::CHUA_XAC_THUC
        ) {
            \Illuminate\Support\Facades\RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Hồ sơ minh chứng cựu sinh viên của bạn đang chờ Quản trị viên đối soát và kích hoạt. Vui lòng quay lại sau.',
            ]);
        }

        // 5. Kiểm tra tài khoản bị tạm khóa
        if ($user->trang_thai === \App\Enums\TrangThaiNguoiDung::TAM_KHOA) {
            \Illuminate\Support\Facades\RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Tài khoản của bạn hiện đang bị tạm khóa. Vui lòng liên hệ ban quản trị để được hỗ trợ.',
            ]);
        }

        // Đăng nhập người dùng vào phiên
        \Illuminate\Support\Facades\Auth::login($user, $this->boolean('remember'));

        \Illuminate\Support\Facades\RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
