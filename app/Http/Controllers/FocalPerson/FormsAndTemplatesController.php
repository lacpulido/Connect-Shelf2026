<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormsAndTemplatesController extends Controller
{
    public function index(): Response
    {
        $forms = Form::with('department')
            ->latest()
            ->get()
            ->map(function ($form) {
                return [
                    'id' => $form->id,
                    'file_name' => $form->file_name,
                    'section' => $form->section,
                    'created_at' => $form->created_at,
                    'department_name' => $form->department->name ?? 'Unknown',
                ];
            });

        return Inertia::render('focalperson/FormsandTemplates', [
            'forms' => $forms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'section' => ['required', 'string', 'max:100'],
        ], [
            'file.max' => 'File must not exceed 10MB.',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();

        $file->storeAs('forms', $filename);

        Form::create([
            'title' => $filename,
            'file_name' => $filename,
            'section' => $validated['section'],
            'department_id' => Auth::user()->department_id,
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully.');
    }

    public function download($id): StreamedResponse
    {
        $form = Form::findOrFail($id);

        $path = 'forms/' . $form->file_name;

        abort_if(!Storage::exists($path), 404, 'File not found.');

        return Storage::download($path, $form->file_name);
    }

    public function destroy($id): RedirectResponse
    {
        $form = Form::findOrFail($id);

        $path = 'forms/' . $form->file_name;

        if ($form->file_name && Storage::exists($path)) {
            Storage::delete($path);
        }

        $form->delete();

        return redirect()->back()->with('success', 'File deleted successfully.');
    }
}