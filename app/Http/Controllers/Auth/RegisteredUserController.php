<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
        'full_name' => ['required', 'string', 'max:255'],
        'student_code' => ['required', 'string', 'max:50', 'unique:users,student_code'],
        'email' => [
            'required', 
            'string', 
            'lowercase', 
            'email', 
            'max:255', 
            'unique:users,email',
            function ($attribute, $value, $fail) {
                $val = strtolower($value);
                if (!str_ends_with($val, '@student.agu.edu.vn') && !str_ends_with($val, '@agu.edu.vn')) {
                    $fail('Chỉ chấp nhận Email sinh viên do trường cấp (@student.agu.edu.vn hoặc @agu.edu.vn).');
                }
            },
        ],
        'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
    ], [
        'email.unique' => 'Email này đã được đăng ký tài khoản trong hệ thống.',
        'student_code.unique' => 'Mã số sinh viên này đã tồn tại trong hệ thống.',
        'password.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
    ]);

    $user = \App\Models\User::create([
        'full_name' => $request->full_name,
        'student_code' => strtoupper($request->student_code),
        'email' => strtolower($request->email),
        'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        'role' => \App\Enums\UserRole::STUDENT,
        'status' => \App\Enums\UserStatus::ACTIVE,
        'reputation_score' => 100, // Điểm uy tín khởi tạo
    ]);

    // Tự động tạo ví điểm cho người dùng mới (nếu chưa có trong model hook)
    if (method_exists($user, 'pointWallet') && !$user->pointWallet) {
        $user->pointWallet()->create([
            'available_points' => 100,
            'held_points' => 0,
        ]);
    }

    event(new \Illuminate\Auth\Events\Registered($user));

    \Illuminate\Support\Facades\Auth::login($user);

    return redirect(route('dashboard', absolute: false));
    }
}
