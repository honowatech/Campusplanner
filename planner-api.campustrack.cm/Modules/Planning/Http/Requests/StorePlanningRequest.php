<?php

namespace Modules\Planning\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Planning\Entities\Planning;

class StorePlanningRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => ['required', 'in:weekly,monthly'],
            'starting_date' => ['required', 'date'],
            'ending_date' => ['required', 'date', function ($attribute, $value, $fail) {
                $startingDate = $this->input('starting_date');
                $type = $this->input('type');
                $expectedDate = ($type === 'weekly') ? strtotime($startingDate.' +6 days') : strtotime($startingDate.' +30 days');
                if (strtotime($value) !== $expectedDate) {
                    $fail("The {$attribute} must be ".date('Y-m-d', $expectedDate).'.');
                }
            }],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Planning::class);
    }
}
