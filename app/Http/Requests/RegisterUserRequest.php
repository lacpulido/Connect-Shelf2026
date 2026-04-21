<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Models\Department;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'first_name'     => 'required|string|max:200',
            'middle_name'    => 'nullable|string|max:200',
            'last_name'      => 'required|string|max:200',
            'extension_name' => 'nullable|string|max:10',
            'expertise'      => 'nullable|string',
            'college_id'     => 'required|exists:colleges,id',
            'department_id'  => 'required|exists:departments,id',
            'user_type'      => 'required|in:1,2',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $departmentBelongsToCollege = Department::where('id', $this->department_id)
                ->where('college_id', $this->college_id)
                ->exists();

            if (!$departmentBelongsToCollege) {
                $validator->errors()->add(
                    'department_id',
                    'Invalid department selection.'
                );
            }
            $email = strtolower($this->email);
            $userType = (int) $this->user_type;

            if ($userType === 2 && !str_ends_with($email, '@mymail.mmsu.edu.ph')) {
                $validator->errors()->add(
                    'email',
                    'Students must register using their @mymail.mmsu.edu.ph email.'
                );
            }

            if ($userType === 1 && !str_ends_with($email, '@mmsu.edu.ph')) {
                $validator->errors()->add(
                    'email',
                    'Faculty must register using their @mmsu.edu.ph email.'
                );
            }
        });
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'first_name'     => strip_tags(trim($this->first_name)),
            'middle_name'    => strip_tags(trim($this->middle_name ?? '')),
            'last_name'      => strip_tags(trim($this->last_name)),
            'extension_name' => strip_tags(trim($this->extension_name ?? '')),
            'expertise'      => strip_tags(trim($this->expertise ?? '')),
            'email'          => strtolower(trim($this->email)),
        ]);
    }

    public function messages(): array
    {
        return [
            // ✅ CUSTOM MESSAGE (THIS FIXES YOUR UI)
            'email.unique' => 'This email is already registered. If your account was deactivated, please contact the administrator at connectshelf@gmail.com.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.mixed_case' => 'Password must include both uppercase and lowercase letters.',
            'password.letters' => 'Password must include at least one letter.',
            'password.numbers' => 'Password must include at least one number.',
            'password.symbols' => 'Password must include at least one special character.',
            'password.uncompromised' => 'Please choose another password.',
        ];
    }
}