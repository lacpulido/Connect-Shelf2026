<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
class StoreProjectDocumentRequest extends FormRequest
{
    public function authorize(): bool
{
    return true;
}
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => strip_tags(trim($this->title)),
            'description' => $this->description
                ? strip_tags(trim($this->description))
                : null,
        ]);
    }
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'document'    => ['required', 'file', 'mimes:pdf', 'max:10240'], // 10MB
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
    public function messages(): array
    {
        return [
            'document.mimes' => 'Only PDF files are allowed.',
            'document.max'   => 'File must not exceed 10MB.',
        ];
    }
}
