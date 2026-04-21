<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectManuscript;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeaturedProjectController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year');
        $projectType = $request->input('project_type');

        $query = ProjectManuscript::with([
            'project.user',
            'project.department',
        ])->whereHas('project');

        if (!empty($year)) {
            $query->whereHas('project', function ($q) use ($year) {
                $q->where('academic_year', $year);
            });
        }

        if (!empty($projectType)) {
            $query->whereHas('project', function ($q) use ($projectType) {
                $q->where('project_type', $projectType);
            });
        }

        $projects = $query
            ->inRandomOrder()
            ->limit(3)
            ->get()
            ->map(function ($manuscript) {
                $project = $manuscript->project;

                return [
                    'id' => $manuscript->id,
                    'type' => strtolower($project->project_type ?? 'thesis'),
                    'title' => $manuscript->title,
                    'author' => $project->user->name ?? 'Unknown Author',
                    'department' => $project->department->name ?? 'Unknown Department',
                    'year' => $project->academic_year ?? now()->year,
                    'views' => 0,
                    'downloads' => 0,
                    'abstract' => $manuscript->abstract,
                ];
            })
            ->values();

        // ✅ FILTER CHOICES FROM DATABASE
        $years = Project::query()
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year')
            ->values()
            ->toArray();

        $projectTypes = Project::query()
            ->whereNotNull('project_type')
            ->where('project_type', '!=', '')
            ->distinct()
            ->orderBy('project_type')
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