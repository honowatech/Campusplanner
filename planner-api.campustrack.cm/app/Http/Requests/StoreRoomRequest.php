<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationRule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation précède la validation (sinon un 422 fuiterait
        // avant le contrôle d'accès)
        return $this->user()->can('create', Room::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:rooms,code',
            'department_id' => 'nullable|exists:departments,id',
            'type' => 'required|in:classroom,lab,amphitheater,conference,study_room',
            'capacity' => 'required|integer|min:1',
            'floor' => 'nullable|integer',
            'building' => 'nullable|string|max:100',
            'has_projector' => 'boolean',
            'has_computers' => 'boolean',
            'has_whiteboard' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ];
    }
}
