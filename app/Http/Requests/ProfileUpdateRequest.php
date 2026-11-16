<?php

namespace App\Http\Requests;

use App\Http\Requests\Auth\ProfileUpdateRequest as BaseProfileUpdateRequest;

// Lớp bọc kế thừa bảo đảm tương thích ngược
class ProfileUpdateRequest extends BaseProfileUpdateRequest
{
}
