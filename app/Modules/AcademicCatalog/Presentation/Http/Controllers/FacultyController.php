<?php

namespace App\Modules\AcademicCatalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCatalog\Application\Queries\GetPublicFacultiesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacultyController extends Controller
{
    public function __construct(
        protected GetPublicFacultiesQuery $facultiesQuery
    ) {}

    // Hiển thị danh sách Khoa và Môn học phục vụ sinh viên trên Dashboard
    public function index(Request $request): Response
    {
        $faculties = $this->facultiesQuery->execute($request->user());

        return Inertia::render('Dashboard', [
            'faculties' => $faculties,
        ]);
    }

    // Lấy danh sách môn học thuộc một khoa (dạng JSON cho Dynamic Dropdown)
    public function layDanhSachMonHoc(int $id): JsonResponse
    {
        $courses = $this->facultiesQuery->getCoursesByFacultyId($id);

        return response()->json([
            'courses' => $courses,
        ]);
    }
}
