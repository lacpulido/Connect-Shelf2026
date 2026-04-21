<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StudentFormsAndTemplatesController extends Controller
{
    public function index()
{
    $user = Auth::user();

    if ($user->user_type !== 2) {
        abort(403, 'Unauthorized');
    }

    $project = Project::where('user_id', $user->id)->first();

    $isEligible = $project && $project->adviser_id !== null;

    $forms = [];

    if ($isEligible) {
        $forms = Form::latest()->get()->map(function ($form) {
            return [
                'id' => $form->id,
                'title' => $form->title,
                'file_name' => $form->file_name,
                'section' => $form->section, // ✅ REQUIRED
                'created_at' => $form->created_at,
            ];
        });
    }

    return Inertia::render('Student/FormsAndTemplates', [
        'forms' => $forms,
        'isEligible' => $isEligible
    ]);
}

     public function download($id)
{
    $form = Form::findOrFail($id);

    $path = 'forms/' . $form->file_name;

    if (!Storage::exists($path)) {
        abort(404, 'File not found.');
    }

    return Storage::download($path, $form->file_name);
}
}