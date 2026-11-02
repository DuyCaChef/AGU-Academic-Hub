<?php

namespace App\Http\Requests\Auth;

use App\Modules\Identity\Presentation\Http\Requests\LoginRequest as BaseLoginRequest;

// Lớp bọc kế thừa bảo đảm tương thích ngược
class LoginRequest extends BaseLoginRequest
{
}
