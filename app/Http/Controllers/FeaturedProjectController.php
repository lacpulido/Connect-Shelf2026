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
        $projects = ProjectManuscript::query()
            ->withTrashed()
            ->with([
                'project.user',
                'project.department',
            ])
            ->whereHas('project')
            ->inRandomOrder()
            ->limit(3)
            ->get()
            ->map(function ($manuscript) {
                $project = $manuscript->project;

                return [
                    'id' => $manuscript->id,
                    'type' => strtolower($project->project_type ?? 'thesis'),
                    'title' => $manuscript->title ?? 'Untitled Project',
                    'author' => $project->user->name ?? 'Unknown Author',
                    'department' => $project->department->name ?? 'Unknown Department',
                    'year' => $project->academic_year ?? now()->year,
                    'views' => 0,
                    'downloads' => 0,
                    'abstract' => $manuscript->abstract ?? 'No abstract available.',
                ];
            })
            ->values();

        return Inertia::render('Welcome', [
            'projects' => $projects,
        ]);
    }
}