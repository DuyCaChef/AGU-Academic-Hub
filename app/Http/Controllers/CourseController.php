<?php

namespace App\Http\Controllers;

use App\Models\MonHoc;
use App\Models\Khoa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Danh sách môn học / Kho tài liệu
     */
    public function index(Request $request)
    {
        // Khoa đã vô hiệu hóa không được xuất hiện trong khu vực tra cứu công khai.
        $faculties = Khoa::with('monHocs')->get();

        return Inertia::render('Documents/Index', [
        'faculties' => $faculties,
        //'documents' => TaiLieu::paginate(12) ?? ['data' => []],
        'filters' => $request->only(['search', 'category', 'sort']),
]);
    }

    /**
     * Chi tiết môn học theo Mã học phần (VD: COS101)
     */
    public function show($course_code)
    {
        // Môn học thuộc Khoa bị vô hiệu hóa được xem là không khả dụng.
        $course = MonHoc::where('ma_hoc_phan', $course_code)
            ->whereHas('khoa')
            ->firstOrFail();

        return Inertia::render('Documents/Index', [
            'course' => $course,
        ]);
    }
}