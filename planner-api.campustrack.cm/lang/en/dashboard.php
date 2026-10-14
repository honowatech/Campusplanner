<?php

return [
    'permission' => [
        'department_only' => 'Unauthorized access. You can only view data for your own department.',
    ],

    'alerts' => [
        'class_over_capacity' => "The class ':class' exceeds its capacity (:count/:capacity)",
        'teacher_over_hours' => 'The teacher :teacher is approaching the maximum hours (:current/:max)',
        'pending_blocking_old' => 'Blocking request pending for over :days days',
        'teacher_no_courses' => 'The teacher :teacher has no assigned courses',
    ],

    'activity' => [
        'blocking_created' => 'Blocking request created by :name',
        'student_admission' => 'New student enrolled: :name',
        'teacher_hired' => 'New teacher hired: :name',
    ],
];
