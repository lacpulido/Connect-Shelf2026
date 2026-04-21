<?php

namespace App\Http\Controllers;

use App\Models\ManuscriptDownload;
use App\Models\ProjectManuscript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManuscriptController extends Controller
{
    public function download(Request $request, int $id): StreamedResponse
    {
        $manuscript = ProjectManuscript::query()
            ->withTrashed()
            ->with([
                'project' => fn ($query) => $query->withTrashed(),
            ])
            ->findOrFail($id);

        if (! $manuscript->filename) {
            abort(404, 'No file is attached to this manuscript.');
        }

        $disk = Storage::disk('local');
        $filePath = 'final_manuscripts/' . $manuscript->filename;

        if (! $disk->exists($filePath)) {
            abort(404, 'File not found.');
        }

        ManuscriptDownload::create([
            'manuscript_id' => $manuscript->id,
            'project_id'    => $manuscript->project_id,
            'user_id'       => Auth::id(),
            'file_name'     => $manuscript->filename,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ]);

        return $disk->download(
            $filePath,
            $this->makeDownloadName($manuscript),
            [
                'Content-Type' => $disk->mimeType($filePath) ?? 'application/pdf',
            ]
        );
    }

    private function makeDownloadName(ProjectManuscript $manuscript): string
    {
        $title = $manuscript->project?->title ?? 'manuscript';
        $safeTitle = str($title)->slug('_');
        $extension = pathinfo($manuscript->filename, PATHINFO_EXTENSION) ?: 'pdf';

        return $safeTitle . '.' . $extension;
    }
}