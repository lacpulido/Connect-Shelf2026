<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();

    $hasProject = $user->project()->exists();

    return Inertia::render('Student/Dashboard', [
        'hasProject' => $hasProject,
    ]);
}
}
