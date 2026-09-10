<?php

namespace App\Modules\AcademicCatalog\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Courses/Index', [
            'courses' => [
                'data' => [],
                'links' => [],
                'meta' => [],
            ],
        ]);
    }
}