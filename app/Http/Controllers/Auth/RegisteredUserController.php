<?php

namespace App\Http\Controllers\Auth;

use App\Enums\VaiTroNguoiDung;
use App\Http\Controllers\Controller;
use App\Models\NguoiDung;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'ho_ten' => ['required', 'string', 'max:255'],
            'mssv'   => ['required', 'string', 'max:50', 'unique:nguoi_dung,mssv'],
            'email'  => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:191',
                'unique:nguoi_dung,email',
                function ($attribute, $value, $fail) {
                    $val = strtolower($value);
                    if (!str_ends_with($val, '@student.agu.edu.vn') && !str_ends_with($val, '@agu.edu.vn')) {
                        $fail('Chỉ chấp nhận Email sinh viên do trường cấp (@student.agu.edu.vn hoặc @agu.edu.vn).');
                    }
                },
            ],
            'mat_khau' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.unique'   => 'Email này đã được đăng ký tài khoản trong hệ thống.',
            'mssv.unique'    => 'Mã số sinh viên này đã tồn tại trong hệ thống.',
            'mat_khau.confirmed' => 'Mật khẩu xác nhận không trùng khớp.',
        ]);

        // vai_tro và trang_thai giữ giá trị mặc định từ DB: SINH_VIEN và CHUA_XAC_THUC
        // mat_khau được model NguoiDung cast 'hashed' nên không cần Hash::make() thủ công
        // diem_uy_tin mặc định 100 theo schema
        // viDiem được tự động tạo trong NguoiDung::booted() → static::created()
        $user = NguoiDung::create([
            'ho_ten'    => $request->ho_ten,
            'mssv'      => strtoupper($request->mssv),
            'email'     => strtolower($request->email),
            'mat_khau'  => $request->mat_khau,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
