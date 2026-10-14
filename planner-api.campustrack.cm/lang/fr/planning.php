<?php

return [
    'planning' => [
        'list' => 'Plannings récupérés',
        'created' => 'Planning créé avec succès',
        'retrieved' => 'Planning récupéré',
        'updated' => 'Planning mis à jour avec succès',
        'deleted' => 'Planning supprimé avec succès',
        'not_owned_by_department' => 'Ce planning n\'appartient pas à votre département.',
        'classes_not_owned_by_department' => 'Ces classes n\'appartiennent pas à votre département.',
    ],

    'shift_planning' => [
        'list' => 'Cours planifiés récupérés',
        'created' => 'Cours planifié créé avec succès',
        'retrieved' => 'Cours planifié récupéré',
        'updated' => 'Cours planifié mis à jour avec succès',
        'deleted' => 'Cours planifié supprimé avec succès',
        'teachers_retrieved' => 'Enseignants récupérés',
    ],

    'scheduling' => [
        'generated' => 'Génération terminée',
        'conflicts_detected' => 'Détection de conflits terminée',
        'statistics_retrieved' => 'Statistiques récupérées',
        'optimization_completed' => 'Optimisation terminée',
        'optimized' => ':count conflits résolus automatiquement',
        'course_scheduled' => 'Cours planifié avec succès',
        'warning_class_not_found' => 'Classe ID :class non trouvée',
        'warning_course_not_found' => 'Cours ID :course non trouvé',
        'warning_no_teacher' => 'Aucun professeur assigné au cours : :course',
        'no_slots_available' => 'Impossible de trouver des créneaux disponibles',
    ],

    'conflicts' => [
        'room_occupied' => 'La salle est déjà occupée pendant ce créneau',
        'teacher_busy' => 'Le professeur est déjà assigné à un autre cours pendant ce créneau',
        'class_busy' => 'La classe a déjà un cours pendant ce créneau',
        'room_blocked' => 'La salle est bloquée : :reason',
        'teacher_blocked' => 'Le professeur est indisponible : :reason',
        'hours_exceeded' => 'Le professeur dépasse son quota d\'heures hebdomadaire',
        'auto_resolved' => 'Conflit résolu automatiquement',
        'cannot_auto_resolve' => 'Impossible de résoudre automatiquement',
        'none_detected' => 'Aucun conflit détecté',
        'detected_with_proposals' => 'Conflits détectés. Propositions de résolution disponibles.',
    ],

    'proposals' => [
        'available' => 'Propositions de résolution',
        'retrieved' => 'Propositions récupérées',
        'applied' => 'Proposition appliquée',
        'change_room' => 'Changer la salle pour :room (:building)',
        'change_teacher' => 'Changer le professeur pour :teacher',
        'change_time' => 'Déplacer le cours au :slot',
    ],

    'recurrence' => [
        'created' => 'Série récurrente créée',
        'updated' => 'Série mise à jour',
        'deleted' => 'Série supprimée',
        'expanded' => 'Série développée',
        'frequency_required' => 'La fréquence est requise',
        'frequency_invalid' => 'Fréquence invalide',
        'days_must_be_array' => 'Les jours doivent être un tableau',
    ],

    'doubleur' => [
        'created' => 'Doubleur créé',
        'opportunities_retrieved' => 'Opportunités de doubleur récupérées',
        'simultaneous_retrieved' => 'Cours simultanés récupérés',
        'optimized' => ':count doubleurs créés',
        'no_room_available' => 'Aucune salle disponible',
        'conflicts_detected' => 'Conflits détectés',
    ],
];
