<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationRule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation précède la validation (sinon un 422 fuiterait
        // avant le contrôle d'accès)
        return $this->user()->can(
            'update',
            Room::findOrFail($this->route('room'))
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:rooms,code,'.$this->route('room').',',
            'department_id' => 'nullable|exists:departments,id',
            'type' => 'sometimes|in:classroom,lab,amphitheater,conference,study_room',
            'capacity' => 'sometimes|integer|min:1',
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
