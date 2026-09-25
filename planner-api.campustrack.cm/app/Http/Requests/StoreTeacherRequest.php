<?php

namespace App\Http\Requests;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationRule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation précède la validation (sinon un 422 fuiterait
        // avant le contrôle d'accès)
        return $this->user()->can('create', Teacher::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['user_id' => 'nullable|exists:users,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email',
            'phone' => 'nullable|string|max:20',
            'speciality' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'max_hours_per_week' => 'nullable|integer|min:1|max:40',
            'is_active' => 'boolean',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
        ];
    }
}
