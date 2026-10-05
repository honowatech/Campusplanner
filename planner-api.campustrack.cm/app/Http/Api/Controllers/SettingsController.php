<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use HttpResponses;

    /**
     * Correspondance champ camelCase (front) -> colonne snake_case (base).
     *
     * @var array<string, string>
     */
    private const FIELDS = [
        'maxWeeklyHoursPerTeacher' => 'max_weekly_hours_per_teacher',
        'maxConsecutiveHours' => 'max_consecutive_hours',
        'maxDailyHoursPerTeacher' => 'max_daily_hours_per_teacher',
        'maxDailyHoursPerClass' => 'max_daily_hours_per_class',
        'enableRoomConflict' => 'enable_room_conflict',
        'enableTeacherConflict' => 'enable_teacher_conflict',
        'enableGroupConflict' => 'enable_group_conflict',
    ];

    /**
     * Récupère les réglages de l'application.
     */
    public function index(): JsonResponse
    {
        return $this->success($this->toPayload(AppSetting::instance()), 'Réglages récupérés');
    }

    /**
     * Met à jour les réglages de l'application.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'maxWeeklyHoursPerTeacher' => 'sometimes|integer|min:0|max:100',
            'maxConsecutiveHours' => 'sometimes|integer|min:0|max:24',
            'maxDailyHoursPerTeacher' => 'sometimes|integer|min:0|max:24',
            'maxDailyHoursPerClass' => 'sometimes|integer|min:0|max:24',
            'enableRoomConflict' => 'sometimes|boolean',
            'enableTeacherConflict' => 'sometimes|boolean',
            'enableGroupConflict' => 'sometimes|boolean',
            'smsConfig' => 'sometimes|array',
            'smsConfig.apiKey' => 'nullable|string|max:255',
            'smsConfig.sender_id' => 'nullable|string|max:11',
            'smsConfig.balance' => 'nullable|integer|min:0',
        ]);

        $settings = AppSetting::instance();

        foreach (self::FIELDS as $camel => $column) {
            if (array_key_exists($camel, $validated)) {
                $settings->{$column} = $validated[$camel];
            }
        }

        if (array_key_exists('smsConfig', $validated)) {
            $settings->sms_config = $validated['smsConfig'];
        }

        $settings->save();

        return $this->success($this->toPayload($settings), 'Réglages mis à jour');
    }

    /**
     * Sérialise les réglages dans le format attendu par le front (camelCase).
     *
     * @return array<string, mixed>
     */
    private function toPayload(AppSetting $settings): array
    {
        $payload = [];

        foreach (self::FIELDS as $camel => $column) {
            $payload[$camel] = $settings->{$column};
        }

        $sms = $settings->sms_config ?? [];
        $payload['smsConfig'] = [
            'apiKey' => $sms['apiKey'] ?? '',
            'sender_id' => $sms['sender_id'] ?? 'CAMPUS PLANNER',
            'balance' => (int) ($sms['balance'] ?? 0),
        ];

        return $payload;
    }
}
