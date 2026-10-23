<?php

namespace App\Http\Controllers;
use App\Models\Khoa;
use App\Models\MonHoc;
use Inertia\Inertia;
use Illuminate\Http\Request;

class KhoaController extends Controller
{
    // hien thi danh sach khoa va mon hoc
    public function index(Request $request)
    {
        $user = $request->user();

        $faculties = Khoa::with('monHocs')
            ->get()
            ->sortBy(function ($faculty) use ($user) {
                return $user && $faculty->ma_khoa === $user->ma_khoa
                    ? 0
                    : 1;
            })
            ->values();

        return Inertia::render('Dashboard', [
            'faculties' => $faculties,
        ]);
    }
    /**
     * Lấy danh sách môn học thuộc một khoa.
     */
    public function courses($id)
    {
        $courses = MonHoc::query()
            ->where('ma_khoa', $id)
            ->get();

        return response()->json([
            'courses' => $courses,
        ]);
    }
    // public function __invoke(Request $request)
    // {
    //     // lay user dang login bang co che xac thuc 
    //     $user = $request->user();
    //     // ghim khoa cua user hien tai len dau
    //     $faculties = Khoa::with('monHocs')
    //         ->get()
    //         ->sortBy(function ($faculty) use ($user) {
    //             return $faculty->ma_khoa === $user->ma_khoa ? 0 : 1;
    //         })
    //         ->values();
    //     return Inertia::render('Dashboard', [
    //         'faculties' => $faculties,
    //     ]);
    // }
}
