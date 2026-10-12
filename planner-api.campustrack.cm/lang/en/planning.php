<?php

return [
    'planning' => [
        'list' => 'Plannings retrieved',
        'created' => 'Planning created successfully',
        'retrieved' => 'Planning retrieved',
        'updated' => 'Planning updated successfully',
        'deleted' => 'Planning deleted successfully',
        'not_owned_by_department' => 'This planning does not belong to your department.',
        'classes_not_owned_by_department' => 'These classes do not belong to your department.',
    ],

    'shift_planning' => [
        'list' => 'Scheduled courses retrieved',
        'created' => 'Scheduled course created successfully',
        'retrieved' => 'Scheduled course retrieved',
        'updated' => 'Scheduled course updated successfully',
        'deleted' => 'Scheduled course deleted successfully',
        'teachers_retrieved' => 'Teachers retrieved',
    ],

    'scheduling' => [
        'generated' => 'Generation completed',
        'conflicts_detected' => 'Conflict detection completed',
        'statistics_retrieved' => 'Statistics retrieved',
        'optimization_completed' => 'Optimization completed',
        'optimized' => ':count conflicts resolved automatically',
        'course_scheduled' => 'Course scheduled successfully',
        'warning_class_not_found' => 'Class ID :class not found',
        'warning_course_not_found' => 'Course ID :course not found',
        'warning_no_teacher' => 'No teacher assigned to course: :course',
        'no_slots_available' => 'No available time slots found',
    ],

    'conflicts' => [
        'room_occupied' => 'The room is already occupied during this time slot',
        'teacher_busy' => 'The teacher is already assigned to another course during this time slot',
        'class_busy' => 'The class already has a course during this time slot',
        'room_blocked' => 'The room is blocked: :reason',
        'teacher_blocked' => 'The teacher is unavailable: :reason',
        'hours_exceeded' => 'The teacher exceeds their weekly hour quota',
        'auto_resolved' => 'Conflict resolved automatically',
        'cannot_auto_resolve' => 'Unable to resolve automatically',
        'none_detected' => 'No conflicts detected',
        'detected_with_proposals' => 'Conflicts detected. Resolution proposals available.',
    ],

    'proposals' => [
        'available' => 'Resolution proposals',
        'retrieved' => 'Proposals retrieved',
        'applied' => 'Proposal applied',
        'change_room' => 'Change room to :room (:building)',
        'change_teacher' => 'Change teacher to :teacher',
        'change_time' => 'Move the course to :slot',
    ],

    'recurrence' => [
        'created' => 'Recurring series created',
        'updated' => 'Recurring series updated',
        'deleted' => 'Recurring series deleted',
        'expanded' => 'Recurring series expanded',
        'frequency_required' => 'The frequency is required',
        'frequency_invalid' => 'Invalid frequency',
        'days_must_be_array' => 'The days must be an array',
    ],

    'doubleur' => [
        'created' => 'Shared session created',
        'opportunities_retrieved' => 'Shared session opportunities retrieved',
        'simultaneous_retrieved' => 'Simultaneous courses retrieved',
        'optimized' => ':count shared sessions created',
        'no_room_available' => 'No room available',
        'conflicts_detected' => 'Conflicts detected',
    ],
];
