<?php

namespace App\Http\Requests;

use App\Models\Teacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationRule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation précède la validation (sinon un 422 fuiterait
        // avant le contrôle d'accès)
        return $this->user()->can(
            'update',
            Teacher::findOrFail($this->route('teacher'))
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['user_id' => 'nullable|exists:users,id',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:teachers,email,'.$this->route('teacher'),
            'phone' => 'nullable|string|max:20',
            'speciality' => 'sometimes|string|max:255',
            'department_id' => 'sometimes|exists:departments,id',
            'max_hours_per_week' => 'nullable|integer|min:1|max:40',
            'is_active' => 'boolean',
            'hired_at' => 'sometimes|date',
        ];
    }
}
