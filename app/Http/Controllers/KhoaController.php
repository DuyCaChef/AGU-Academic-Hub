<?php

namespace App\Http\Controllers;
use App\Models\Khoa;
use Inertia\Inertia;
use Illuminate\Http\Request;

class KhoaController extends Controller
{
    public function __invoke(Request $request)
    {
        $khoa = Khoa::all();
        return Inertia::render('Khoa/Index', [
            'khoa' => $khoa,
        ]);
    }
}
