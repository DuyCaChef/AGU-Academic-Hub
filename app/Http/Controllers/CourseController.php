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
        $faculties = Khoa::with('monHocs')->get();

        return Inertia::render('Documents/Index', [
        'faculties' => Khoa::with('monHocs')->get() ?? [],
        //'documents' => TaiLieu::paginate(12) ?? ['data' => []],
        'filters' => $request->only(['search', 'category', 'sort']),
]);
    }

    /**
     * Chi tiết môn học theo Mã học phần (VD: COS101)
     */
    public function show($course_code)
    {
        $course = MonHoc::where('ma_hoc_phan', $course_code)->firstOrFail();

        return Inertia::render('Documents/Index', [
            'course' => $course,
        ]);
    }
}