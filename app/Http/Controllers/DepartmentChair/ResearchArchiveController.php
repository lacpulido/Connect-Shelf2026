<?php

namespace App\Http\Controllers\DepartmentChair;

use App\Http\Controllers\Controller;
use App\Models\ProjectManuscript;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ResearchArchiveController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $manuscripts = ProjectManuscript::query()
            ->with([
                'project' => function ($query) {
                    $query->withTrashed()->with([
                        'adviser',
                        'researchers',
                        'department',
                    ]);
                },
            ])
            ->whereHas('project', function ($query) use ($user) {
                $query->withTrashed()
                    ->where('department_id', $user->department_id);
            })
            ->latest()
            ->get()
            ->map(fn ($manuscript) => $this->formatManuscript($manuscript))
            ->values();

        return Inertia::render('DepartmentChair/ResearchArchives', [
            'manuscripts' => $manuscripts,
        ]);
    }

    private function formatManuscript(ProjectManuscript $manuscript): array
    {
        $project = $manuscript->project;
        $adviser = $project?->adviser;
        $researchers = $project?->researchers ?? collect();

        return [
            'id'                => $manuscript->id,
            'title'             => $manuscript->title ?? '',
            'filename'          => $manuscript->filename ?? '',
            'original_filename' => $manuscript->original_filename ?? $manuscript->filename ?? '',
            'status'            => $manuscript->status ?? '',
            'abstract'          => $manuscript->abstract ?? '',
            'project_title'     => $project?->title ?? null,
            'adviser'           => $adviser ? $this->getFullName($adviser) : null,
            'researchers'       => $researchers
                ->map(fn ($researcher) => $this->getFullName($researcher))
                ->filter()
                ->values()
                ->toArray(),
            'created_at'        => $manuscript->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    private function getFullName(object $person): string
    {
        return trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
    }
}