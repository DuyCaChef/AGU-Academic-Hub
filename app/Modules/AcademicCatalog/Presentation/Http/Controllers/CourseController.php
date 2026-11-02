<?php

namespace App\Modules\AcademicCatalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AcademicCatalog\Application\Queries\GetPublicCoursesQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        protected GetPublicCoursesQuery $coursesQuery
    ) {}

    // Danh sách môn học / Kho tài liệu công khai
    public function index(Request $request): Response
    {
        $faculties = $this->coursesQuery->getPublicFaculties();

        return Inertia::render('Documents/Index', [
            'faculties' => $faculties,
            'filters' => $request->only(['search', 'category', 'sort']),
        ]);
    }

    // Chi tiết môn học theo Mã học phần (VD: COS101)
    public function show(string $course_code): Response
    {
        $course = $this->coursesQuery->findActiveCourseByCode($course_code);

        return Inertia::render('Documents/Index', [
            'course' => $course,
        ]);
    }

    // Danh sách tài liệu thuộc môn học
    public function documents(string $course_code): Response
    {
        $course = $this->coursesQuery->findActiveCourseByCode($course_code);

        return Inertia::render('Documents/Index', [
            'course' => $course,
        ]);
    }
}
