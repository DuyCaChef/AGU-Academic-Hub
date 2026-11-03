<?php

namespace App\Modules\Identity\Application\Actions;

use App\Models\NguoiDung;
use App\Modules\Identity\Domain\Services\IdentityRuleEvaluator;
use App\Modules\Identity\Infrastructure\Notifications\FirstTimePasswordChangeRequiredNotification;
use App\Modules\Identity\Infrastructure\Persistence\IdentityRepository;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Action xử lý xác thực đăng nhập người dùng, đối soát dữ liệu thô và kiểm tra giới hạn tốc độ
class AuthenticateUserAction
{
    public function __construct(
        protected IdentityRepository $identityRepository,
        protected IdentityRuleEvaluator $ruleEvaluator
    ) {}

    public function execute(string $loginInput, string $password, bool $remember = false, string $ip = '127.0.0.1'): NguoiDung
    {
        $throttleKey = Str::transliterate(Str::lower($loginInput).'|'.$ip);

        // 1. Kiểm tra giới hạn tốc độ thử đăng nhập (Rate Limiting)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout(request()));
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        // 2. Tìm người dùng trong bảng nguoi_dung
        $user = $this->identityRepository->findUserByLogin($loginInput);

        // 3. Nếu chưa có, kiểm tra đối soát với dữ liệu thô nhà trường
        if (!$user) {
            $rawStudent = $this->identityRepository->findRawStudent($loginInput);

            if ($rawStudent && Hash::check($password, $rawStudent->mat_khau_mac_dinh)) {
                $user = $this->identityRepository->createUserFromRaw($rawStudent, $password);
            }
        }

        // 4. Kiểm tra tài khoản và mật khẩu
        if (!$user || !Hash::check($password, $user->mat_khau)) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'Thông tin đăng nhập hoặc mật khẩu không chính xác.',
            ]);
        }

        // 5. Kiểm tra trạng thái tài khoản (khóa, chờ duyệt KYC)
        $check = $this->ruleEvaluator->canLogin($user);
        if (!$check['allowed']) {
            RateLimiter::hit($throttleKey);

            if (!empty($check['is_pending'])) {
                session()->flash('kyc_status', [
                    'type' => 'pending',
                    'message' => 'Hồ sơ cựu sinh viên của bạn đang chờ xử lý. Bạn sẽ được phép đăng nhập sau khi hồ sơ được duyệt.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => $check['message'],
            ]);
        }

        // 6. Đăng nhập người dùng vào phiên làm việc
        Auth::login($user, $remember);
        RateLimiter::clear($throttleKey);

        // 7. Gửi thông báo yêu cầu đổi mật khẩu lần đầu cho sinh viên đang học
        if ($this->ruleEvaluator->isFirstTimeLogin($user)) {
            $alreadyNotified = $user->notifications()
                ->where('type', FirstTimePasswordChangeRequiredNotification::class)
                ->exists();

            if (!$alreadyNotified) {
                $user->notify(new FirstTimePasswordChangeRequiredNotification((string) $user->mssv));
            }
        }

        return $user;
    }
}

