<?php

return [
    'permission' => [
        'department_only' => 'Accès non autorisé. Vous ne pouvez voir que les données de votre département.',
    ],

    'alerts' => [
        'class_over_capacity' => "La classe ':class' dépasse sa capacité (:count/:capacity)",
        'teacher_over_hours' => "L'enseignant :teacher approche du maximum d'heures (:current/:max)",
        'pending_blocking_old' => 'Demande de blocage en attente depuis plus de :days jours',
        'teacher_no_courses' => "L'enseignant :teacher n'a aucun cours assigné",
    ],

    'activity' => [
        'blocking_created' => 'Demande de blocage créée par :name',
        'student_admission' => 'Nouvel étudiant inscrit : :name',
        'teacher_hired' => 'Nouvel enseignant embauché : :name',
    ],
];
