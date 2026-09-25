<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationRule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation précède la validation (sinon un 422 fuiterait
        // avant le contrôle d'accès)
        return $this->user()->can('create', Student::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:students,email',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'matricule' => 'required|string|unique:students,matricule',
            'admission_date' => 'required|date',
            'course_class_id' => 'nullable|exists:classes,id',
            'is_active' => 'boolean',
            'photo_url' => 'nullable|string',
            'password' => 'required|string|min:8', // For user account
        ];
    }
}
