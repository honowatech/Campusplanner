<?php

namespace Modules\Planning\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Planning\Entities\ShiftPlanning;
use Modules\Planning\Facades\Planning;

class UpdateShiftPlanningRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'planning_id' => ['required', 'exists:planning_plannings,id'],
            'course_class_id' => ['required', 'exists:classes,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'date' => ['required',
                'date',
                function ($attribute, $value, $fail) {
                    $planning = Planning::findPlanningById($this->input('planning_id'));
                    if ($planning) {
                        $date = Carbon::parse($value);
                        $startingDate = Carbon::parse($planning->starting_date);
                        $endingDate = Carbon::parse($planning->ending_date);

                        if (! $date->between($startingDate, $endingDate)) {
                            $fail(__('The :attribute must be between :start and :end.', [
                                'start' => $startingDate->toDateString(),
                                'end' => $endingDate->toDateString(),
                            ]));
                        }
                    }
                }],
            'starting_hour' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $minutes = intval(date('i', strtotime($value)));
                    if ($minutes !== 0) {
                        $fail(__('The minutes must be 00.'));
                    }
                    if ($value === '12:00') {
                        $fail(__('The 12:00 hour is reserved for lunch break.'));
                    }
                },
            ],
            'ending_hour' => [
                'required',
                'date_format:H:i',
                'after:starting_hour',
                function ($attribute, $value, $fail) {
                    $minutes = intval(date('i', strtotime($value)));
                    if ($minutes !== 0) {
                        $fail(__('The minutes must be 00.'));
                    }
                    $startHour = $this->input('starting_hour');
                    if ($startHour && $value) {
                        $start = intval(str_replace(':', '', $startHour));
                        $end = intval(str_replace(':', '', $value));
                        if ($start < 1200 && $end > 1200) {
                            $fail(__('The 12:00 hour is reserved for lunch break.'));
                        }
                    }
                },
            ],
            // 'number_teachers' => ['required', 'numeric', 'min:0'],
            // 'teachers' => ['nullable', 'array', 'distinct', 'exists:enseignants,id', 'max:' . $this->input('number_teachers')],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', ShiftPlanning::findOrFail($this->route('id')));
    }
}
