<?php

namespace App\Http\Requests\Student;

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

        if (! is_array($researchers)) {
            $researchers = [];
        }

        $adviserId = $this->sanitizeInteger($this->input('adviser_id'));

        $this->merge([
            'title' => $this->sanitizeTitle($this->input('title')),
            'academic_year' => $this->sanitizeAcademicYear($this->input('academic_year')),
            'researchers' => $this->sanitizeIdArray($researchers),
            'adviser_id' => $adviserId,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['bail', 'required', 'string', 'min:5', 'max:200'],

            'academic_year' => [
                'bail',
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
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

            'adviser_id' => [
                'required',
                'integer',
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
                        return;
                    }

                    $researchers = array_map('intval', $this->input('researchers', []));

                    if (in_array((int) $value, $researchers, true)) {
                        $fail('Adviser cannot also be a researcher.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Project title is required.',
            'title.min' => 'Project title must be at least 5 characters.',
            'title.max' => 'Project title cannot exceed 200 characters.',

            'academic_year.required' => 'Academic year is required.',
            'academic_year.regex' => 'Academic year must use this format: 2025-2026.',

            'researchers.array' => 'Researchers must be a valid list.',
            'researchers.max' => 'You can only add up to 4 researchers.',

            'researchers.*.integer' => 'One of the selected researchers is invalid.',
            'researchers.*.distinct' => 'Duplicate researcher selected.',
            'researchers.*.exists' => 'One of the selected researchers does not exist.',

            'adviser_id.required' => 'Adviser is required.',
            'adviser_id.integer' => 'Selected adviser is invalid.',
            'adviser_id.exists' => 'Selected adviser does not exist.',
        ];
    }

    protected function passedValidation(): void
    {
        $this->replace([
            'title' => $this->sanitizeTitle($this->input('title')),
            'academic_year' => $this->sanitizeAcademicYear($this->input('academic_year')),
            'researchers' => $this->sanitizeIdArray($this->input('researchers', [])),
            'adviser_id' => $this->sanitizeInteger($this->input('adviser_id')),
        ]);
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

    private function sanitizeAcademicYear(mixed $value): string
    {
        return Str::of((string) ($value ?? ''))
            ->stripTags()
            ->replaceMatches('/[\x00-\x1F\x7F]/u', '')
            ->squish()
            ->limit(9, '')
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