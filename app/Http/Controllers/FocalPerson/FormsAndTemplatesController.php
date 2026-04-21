<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;

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
                    'title' => $form->title,
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

    public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:150',
        'file' => 'required|file|max:10240',
        'section' => 'required|string|max:100',
    ]);
    $file = $request->file('file');
    $filename = $file->getClientOriginalName();
    $file->storeAs('forms', $filename);
    Form::create([
        'title' => $request->title,
        'file_name' => $filename,
        'section' => $request->section,
        'department_id' => Auth::user()->department_id,
    ]);
    return redirect()->back()->with([
        'forms' => Form::latest()->get()
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
public function destroy($id): RedirectResponse
{
    $form = Form::findOrFail($id);
    if ($form->file_name && Storage::exists($form->file_name)) {
        Storage::delete($form->file_name);
    }
    $form->delete();
    return redirect()->back();
}
}