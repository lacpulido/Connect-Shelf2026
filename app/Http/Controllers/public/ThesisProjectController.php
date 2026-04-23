<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ThesisProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedYear = $request->get('academic_year');

        $projects = Project::query()
            ->withTrashed()
            ->with([
                'user' => fn ($query) => $query->withTrashed(),
                'department',
                'manuscript' => fn ($query) => $query->withTrashed()->where('status', 'approved'),
                'researchers' => fn ($query) => $query->withTrashed(),
            ])
            ->where('project_type', 'Thesis')
            ->whereHas('manuscript', function ($query) {
                $query->withTrashed()->where('status', 'approved');
            })
            ->when($selectedYear, function ($query) use ($selectedYear) {
                $query->where('academic_year', $selectedYear);
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($project) {
                $manuscript = $project->manuscript;

                return [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'title' => $project->title,
                    'academic_year' => $project->academic_year,
                    'abstract' => optional($manuscript)->abstract,
                    'department' => $project->department,
                    'user' => $project->user,
                    'project_type' => $project->project_type,
                    'researchers' => collect($project->researchers)
                        ->when($project->user, fn ($collection) => $collection->push($project->user))
                        ->filter()
                        ->unique('id')
                        ->values(),
                    'manuscript' => $manuscript ? [
                        'id' => $manuscript->id,
                        'title' => $manuscript->title,
                        'abstract' => $manuscript->abstract,
                        'filename' => $manuscript->filename,
                        'deleted_at' => $manuscript->deleted_at?->format('F d, Y'),
                        'download_url' => route('manuscripts.download', $manuscript->id),
                    ] : null,
                    'created_at' => $project->created_at?->format('F d, Y'),
                    'updated_at' => $project->updated_at?->format('F d, Y'),
                    'deleted_at' => $project->deleted_at?->format('F d, Y'),
                ];
            });

        $years = Project::query()
            ->withTrashed()
            ->where('project_type', 'Thesis')
            ->whereHas('manuscript', function ($query) {
                $query->withTrashed()->where('status', 'approved');
            })
            ->whereNotNull('academic_year')
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year')
            ->values();

        return Inertia::render('public/ThesisProjects', [
            'projects' => $projects,
            'years' => $years,
            'filters' => [
                'academic_year' => $selectedYear,
            ],
        ]);
    }

    public function show($slug): Response
    {
        $project = Project::query()
            ->withTrashed()
            ->with([
                'user' => fn ($query) => $query->withTrashed(),
                'department',
                'manuscript' => fn ($query) => $query->withTrashed()->where('status', 'approved'),
                'researchers' => fn ($query) => $query->withTrashed(),
            ])
            ->where('slug', $slug)
            ->where('project_type', 'Thesis')
            ->whereHas('manuscript', function ($query) {
                $query->withTrashed()->where('status', 'approved');
            })
            ->first();

        if (! $project) {
            abort(404, 'Project not found');
        }

        $manuscript = $project->manuscript;

        return Inertia::render('ProjectShow', [
            'project' => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'academic_year' => $project->academic_year,
                'project_type' => $project->project_type,
                'abstract' => optional($manuscript)->abstract,
                'department' => $project->department,
                'user' => $project->user,
                'researchers' => collect($project->researchers)
                    ->when($project->user, fn ($collection) => $collection->push($project->user))
                    ->filter()
                    ->unique('id')
                    ->values(),
                'manuscript' => $manuscript ? [
                    'id' => $manuscript->id,
                    'title' => $manuscript->title,
                    'abstract' => $manuscript->abstract,
                    'filename' => $manuscript->filename,
                    'deleted_at' => $manuscript->deleted_at?->format('F d, Y'),
                    'download_url' => route('manuscripts.download', $manuscript->id),
                ] : null,
                'created_at' => $project->created_at?->format('F d, Y'),
                'updated_at' => $project->updated_at?->format('F d, Y'),
                'deleted_at' => $project->deleted_at?->format('F d, Y'),
            ],
        ]);
    }
}