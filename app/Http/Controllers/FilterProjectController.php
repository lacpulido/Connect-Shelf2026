<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Inertia\Inertia;

class FilterProjectController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year');
        $projectType = $request->input('project_type');

        $query = Project::query();

        if (!empty($year)) {
            $query->where('academic_year', $year);
        }

        if (!empty($projectType)) {
            $query->where('project_type', $projectType);
        }

        $projects = $query->latest()->get();

        // ✅ IMPORTANT: ARRAY NA
        $years = Project::select('academic_year')
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year')
            ->values()
            ->toArray();

        $projectTypes = Project::select('project_type')
            ->whereNotNull('project_type')
            ->distinct()
            ->pluck('project_type')
            ->values()
            ->toArray();

        return Inertia::render('Welcome', [
            'projects' => $projects,
            'years' => $years,
            'projectTypes' => $projectTypes,
            'filters' => [
                'year' => $year ?? '',
                'project_type' => $projectType ?? '',
            ],
        ]);
    }
}