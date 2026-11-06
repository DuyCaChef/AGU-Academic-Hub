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
        $filters = $request->only(['search', 'category', 'sort', 'ma_khoa', 'ma_chuyen_nganh']);
        $faculties = $this->coursesQuery->getPublicFaculties($request->user());
        $courses = $this->coursesQuery->getPaginatedCourses($filters);
        $documents = $this->coursesQuery->getPaginatedDocuments($filters);

        return Inertia::render('Documents/Index', [
            'faculties' => $faculties,
            'courses' => $courses,
            'documents' => $documents,
            'filters' => $filters,
        ]);
    }

    // Chi tiết môn học theo Mã học phần (VD: COS101)
    public function show(Request $request, string $course_code): Response
    {
        $course = $this->coursesQuery->findActiveCourseByCode($course_code);
        $faculties = $this->coursesQuery->getPublicFaculties($request->user());

        $docFilters = array_merge(
            $request->only(['search', 'category', 'sort']),
            ['course_id' => $course->ma_mon_hoc]
        );
        $documents = $this->coursesQuery->getPaginatedDocuments($docFilters);

        return Inertia::render('Documents/Index', [
            'faculties' => $faculties,
            'course' => $course,
            'documents' => $documents,
            'filters' => $request->only(['search', 'category', 'sort']),
        ]);
    }

    // Danh sách tài liệu thuộc môn học
    public function documents(Request $request, string $course_code): Response
    {
        return $this->show($request, $course_code);
    }
}
