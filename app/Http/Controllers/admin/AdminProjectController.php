<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use Inertia\Inertia;

class AdminProjectController extends Controller
{
    public function projects(Request $request)
    {
        if (!$request->session()->has('admin_user_id')) {
            return redirect()->route('admin.login');
        }

        $projects = Project::all();

        return Inertia::render('Admin/Projects', [
            'projects' => $projects,
        ]);
    }

    public function projectShow(Request $request, $id)
    {
        if (!$request->session()->has('admin_user_id')) {
            return redirect()->route('admin.login');
        }

        $project = Project::findOrFail($id);

        return Inertia::render('Admin/ProjectShow', [
            'project' => $project,
        ]);
    }
}
