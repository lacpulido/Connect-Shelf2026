<?php

namespace App\Http\Requests\Student;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && (int) Auth::user()->user_type === 2;
    }

    protected function prepareForValidation(): void
    {
        $researchers = $this->input('researchers', []);
        $titles = $this->input('proposal_titles', []);
        $preferredAdvisers = $this->input('preferred_adviser_ids', []);

        if (! is_array($researchers)) {
            $researchers = [];
        }

        if (! is_array($titles)) {
            $titles = [];
        }

        if (! is_array($preferredAdvisers)) {
            $preferredAdvisers = [];
        }

        $this->merge([
            'proposal_titles' => array_map(fn ($title) => $this->sanitizeTitle($title), $titles),
            'researchers' => $this->sanitizeIdArray($researchers),
            'preferred_adviser_ids' => $this->sanitizeIdArray($preferredAdvisers),
        ]);
    }

    public function rules(): array
    {
        return [
            'proposal_titles' => ['required', 'array', 'size:3'],

            'proposal_titles.*' => [
                'bail',
                'required',
                'string',
                'min:5',
                'max:200',
                'distinct',
                function ($attribute, $value, $fail) {
                    $normalizedTitle = Str::of((string) $value)
                        ->lower()
                        ->squish()
                        ->toString();

                    $exists = Project::withTrashed()
                        ->whereRaw('LOWER(TRIM(title)) = ?', [$normalizedTitle])
                        ->exists();

                    if ($exists) {
                        $fail('This proposal title already exists.');
                    }
                },
            ],

            'proposal_files' => ['required', 'array', 'size:3'],

            'proposal_files.*' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
            ],

            'researchers' => ['nullable', 'array', 'max:4'],

            'researchers.*' => [
                'bail',
                'integer',
                'distinct',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);

                    if (! $user || (int) $user->user_type !== 2) {
                        $fail('Selected researcher must be a student.');
                        return;
                    }

                    if ((int) $value === (int) Auth::id()) {
                        $fail('You cannot add yourself as a researcher.');
                    }
                },
            ],

            'preferred_adviser_ids' => ['required', 'array', 'size:3'],

            'preferred_adviser_ids.*' => [
                'bail',
                'integer',
                'distinct',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::with('roles')->find($value);

                    if (! $user) {
                        $fail('Selected adviser does not exist.');
                        return;
                    }

                    if ((int) $user->user_type !== 1) {
                        $fail('Selected adviser must be a faculty member.');
                        return;
                    }

                    if ($user->roles->contains('name', 'Administrator')) {
                        $fail('Administrator cannot be selected as adviser.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proposal_titles.required' => 'Please enter 3 proposal titles.',
            'proposal_titles.size' => 'Exactly 3 proposal titles are required.',
            'proposal_titles.*.required' => 'Each proposal title is required.',
            'proposal_titles.*.min' => 'Each proposal title must be at least 5 characters.',
            'proposal_titles.*.max' => 'Each proposal title cannot exceed 200 characters.',
            'proposal_titles.*.distinct' => 'Proposal titles must be different.',

            'proposal_files.required' => 'Please upload one file for each proposal title.',
            'proposal_files.size' => 'Exactly 3 proposal files are required.',
            'proposal_files.*.required' => 'Each proposal title must have a proposal file.',
            'proposal_files.*.mimes' => 'Each proposal file must be PDF, DOC, or DOCX.',

            'researchers.max' => 'You can only add up to 4 researchers.',

            'preferred_adviser_ids.required' => 'Please select 3 preferred advisers.',
            'preferred_adviser_ids.size' => 'Exactly 3 preferred advisers are required.',
            'preferred_adviser_ids.*.distinct' => 'Preferred advisers must be different.',
            'preferred_adviser_ids.*.exists' => 'Selected adviser does not exist.',
        ];
    }

    private function sanitizeTitle(mixed $value): string
    {
        return Str::of((string) ($value ?? ''))
            ->stripTags()
            ->replaceMatches('/[\x00-\x1F\x7F]/u', '')
            ->squish()
            ->limit(200, '')
            ->toString();
    }

    private function sanitizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false
            ? (int) $value
            : null;
    }

    private function sanitizeIdArray(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($value) {
            return $this->sanitizeInteger($value);
        }, $values), fn ($value) => $value !== null)));
    }
}