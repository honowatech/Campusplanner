<?php

namespace Modules\Planning\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Planning\Entities\ShiftPlanning;

class RecurrenceService
{
    protected ConflictDetectionService $conflictService;

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_BIWEEKLY = 'biweekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public function __construct()
    {
        $this->conflictService = new ConflictDetectionService;
    }

    public function createRecurring(ShiftPlanning $original, array $recurrenceData): array
    {
        $pattern = $recurrenceData['pattern'] ?? [];
        $endDate = isset($recurrenceData['end_date'])
            ? Carbon::parse($recurrenceData['end_date'])
            : Carbon::parse($original->date)->addWeeks(12);

        $original->update([
            'is_recurring' => true,
            'recurrence_pattern' => $pattern,
            'recurrence_end_date' => $endDate,
        ]);

        $dates = $this->generateDates(
            Carbon::parse($original->date),
            $endDate,
            $pattern
        );

        $created = [];
        $conflicts = [];

        foreach ($dates as $date) {
            if ($date === $original->date->format('Y-m-d')) {
                continue;
            }

            $newShift = $original->replicate();
            $newShift->date = $date;
            $newShift->parent_id = $original->id;
            $newShift->status = 'pending';
            $newShift->save();

            $shiftConflicts = $this->conflictService->detectConflicts($newShift, $newShift->id);

            $created[] = [
                'id' => $newShift->id,
                'date' => $date,
                'has_conflicts' => ! empty($shiftConflicts),
                'conflicts' => $shiftConflicts,
            ];

            if (! empty($shiftConflicts)) {
                $conflicts[] = [
                    'shift_planning_id' => $newShift->id,
                    'date' => $date,
                    'conflicts' => $shiftConflicts,
                ];
            }
        }

        return [
            'original' => $original,
            'created' => $created,
            'total_created' => count($created),
            'conflicts' => $conflicts,
            'total_conflicts' => count($conflicts),
        ];
    }

    public function updateRecurringSeries(ShiftPlanning $parent, array $data): array
    {
        $children = ShiftPlanning::where('parent_id', $parent->id)->get();

        $updated = [];
        foreach ($children as $child) {
            $child->update($data);
            $updated[] = $child->id;
        }

        $parent->update($data);

        return [
            'parent_updated' => $parent->id,
            'children_updated' => $updated,
            'total_updated' => count($updated) + 1,
        ];
    }

    public function deleteRecurringSeries(ShiftPlanning $parent, bool $deleteChildren = true): array
    {
        if ($deleteChildren) {
            $children = ShiftPlanning::where('parent_id', $parent->id)->get();
            $deletedCount = $children->count();

            foreach ($children as $child) {
                $child->delete();
            }
        } else {
            ShiftPlanning::where('parent_id', $parent->id)->update(['parent_id' => null]);
            $deletedCount = 0;
        }

        $parent->delete();

        return [
            'deleted' => true,
            'parent_id' => $parent->id,
            'children_deleted' => $deletedCount,
        ];
    }

    public function generateDates(Carbon $startDate, Carbon $endDate, array $pattern): array
    {
        $frequency = $pattern['frequency'] ?? self::FREQUENCY_WEEKLY;
        $daysOfWeek = $pattern['days'] ?? [];
        $interval = $pattern['interval'] ?? 1;

        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            switch ($frequency) {
                case self::FREQUENCY_DAILY:
                    $dates[] = $currentDate->format('Y-m-d');
                    $currentDate->addDays($interval);
                    break;

                case self::FREQUENCY_WEEKLY:
                    if (empty($daysOfWeek) || in_array($currentDate->dayOfWeek, $daysOfWeek)) {
                        $dates[] = $currentDate->format('Y-m-d');
                    }
                    $currentDate->addDay();
                    break;

                case self::FREQUENCY_BIWEEKLY:
                    if (empty($daysOfWeek) || in_array($currentDate->dayOfWeek, $daysOfWeek)) {
                        $dates[] = $currentDate->format('Y-m-d');
                    }
                    $currentDate->addDay();
                    break;

                case self::FREQUENCY_MONTHLY:
                    if ($currentDate->day === ($pattern['day_of_month'] ?? $startDate->day)) {
                        $dates[] = $currentDate->format('Y-m-d');
                    }
                    $currentDate->addDay();
                    break;

                default:
                    $currentDate->addDay();
            }
        }

        return array_unique($dates);
    }

    public function expandRecurringSeries(ShiftPlanning $parent): Collection
    {
        $dates = $parent->getRecurrenceDates();

        $shifts = collect([$parent]);

        foreach ($dates as $date) {
            $existing = ShiftPlanning::where('parent_id', $parent->id)
                ->where('date', $date)
                ->first();

            if (! $existing) {
                $newShift = $parent->replicate();
                $newShift->date = $date;
                $newShift->parent_id = $parent->id;
                $newShift->status = 'pending';
                $newShift->save();

                $shifts->push($newShift);
            } else {
                $shifts->push($existing);
            }
        }

        return $shifts->sortBy('date');
    }

    public function getNextOccurrence(ShiftPlanning $parent, ?Carbon $fromDate = null): ?Carbon
    {
        $from = $fromDate ?? Carbon::now();
        $dates = $parent->getRecurrenceDates();

        foreach ($dates as $dateStr) {
            $date = Carbon::parse($dateStr);
            if ($date->gt($from)) {
                return $date;
            }
        }

        return null;
    }

    public function validateRecurrencePattern(array $pattern): array
    {
        $errors = [];

        if (! isset($pattern['frequency'])) {
            $errors[] = 'La fréquence est requise';
        } else {
            $validFrequencies = [
                self::FREQUENCY_DAILY,
                self::FREQUENCY_WEEKLY,
                self::FREQUENCY_BIWEEKLY,
                self::FREQUENCY_MONTHLY,
            ];

            if (! in_array($pattern['frequency'], $validFrequencies)) {
                $errors[] = 'Fréquence invalide';
            }
        }

        if (isset($pattern['days']) && ! is_array($pattern['days'])) {
            $errors[] = 'Les jours doivent être un tableau';
        }

        return $errors;
    }

    public static function getWeekDays(): array
    {
        return [
            0 => 'Dimanche',
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];
    }

    public static function getFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Quotidien',
            self::FREQUENCY_WEEKLY => 'Hebdomadaire',
            self::FREQUENCY_BIWEEKLY => 'Bi-hebdomadaire',
            self::FREQUENCY_MONTHLY => 'Mensuel',
        ];
    }
}
