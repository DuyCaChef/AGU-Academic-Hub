<?php

namespace App\Http\Requests;

use App\Modules\Identity\Presentation\Http\Requests\ProfileUpdateRequest as BaseProfileUpdateRequest;

// Lớp bọc kế thừa bảo đảm tương thích ngược
class ProfileUpdateRequest extends BaseProfileUpdateRequest
{
}
