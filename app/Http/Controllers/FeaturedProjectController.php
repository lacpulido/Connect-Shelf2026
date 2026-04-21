<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeaturedProjectController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year');
        $projectType = $request->input('project_type');

        $query = Project::query()
            ->with([
                'user',
                'department',
                'manuscripts' => fn ($q) => $q->withTrashed(), // ✅ include deleted manuscripts
            ])
            ->whereHas('manuscripts', function ($q) {
                $q->withTrashed(); // ✅ include deleted in existence check
            });

        if (!empty($year)) {
            $query->where('academic_year', $year);
        }

        if (!empty($projectType)) {
            $query->where('project_type', $projectType);
        }

        $projects = $query
            ->inRandomOrder()
            ->limit(3)
            ->get()
            ->map(function ($project) {

                // ⚠️ include deleted manuscripts
                $manuscript = $project->manuscripts->first();

                return [
                    'id' => $manuscript?->id,
                    'type' => strtolower($project->project_type ?? 'thesis'),
                    'title' => $manuscript?->title ?? 'Untitled Project',
                    'author' => $project->user->name ?? 'Unknown Author',
                    'department' => $project->department->name ?? 'Unknown Department',
                    'year' => $project->academic_year ?? now()->year,
                    'views' => 0,
                    'downloads' => 0,
                    'abstract' => $manuscript?->abstract ?? 'No abstract available.',
                ];
            })
            ->filter(fn ($project) => !is_null($project['id']))
            ->values();

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