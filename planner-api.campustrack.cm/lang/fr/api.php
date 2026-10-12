<?php

return [
    'auth' => [
        'registered' => 'Compte créé avec succès. En attente de validation par un administrateur.',
        'account_pending' => 'Votre compte est en attente de validation par un administrateur.',
        'signed_in' => 'Connexion réussie',
        'invalid_credentials' => 'Identifiants invalides',
        'signed_out' => 'Déconnexion réussie',
        'not_authenticated' => 'Utilisateur non authentifié',
        'authenticated' => 'Utilisateur authentifié',
    ],

    'settings' => [
        'retrieved' => 'Réglages récupérés',
        'updated' => 'Réglages mis à jour',
    ],

    'room_blockings' => [
        'list' => 'Liste des blocages de salles récupérée',
        'created' => 'Blocage de salle créé avec succès',
        'conflicts_warning' => 'Ce blocage entre en conflit avec des plannings existants',
        'retrieved' => 'Blocage de salle récupéré',
        'updated' => 'Blocage de salle mis à jour avec succès',
        'deleted' => 'Blocage de salle supprimé avec succès',
        'conflict_check' => 'Vérification des conflits terminée',
    ],

    'teacher_blockings' => [
        'list' => 'Liste des blocages d\'enseignants récupérée',
        'already_approved' => 'Vous avez déjà un blocage approuvé pour cette période.',
        'submitted' => 'Demande de blocage soumise avec succès et en attente d\'approbation',
        'retrieved' => 'Blocage d\'enseignant récupéré',
        'cannot_update' => 'Impossible de modifier un blocage qui a déjà été :status',
        'updated' => 'Demande de blocage mise à jour avec succès',
        'cannot_delete' => 'Impossible de supprimer un blocage qui a déjà été :status',
        'deleted' => 'Demande de blocage supprimée avec succès',
        'already_processed' => 'Ce blocage a déjà été :status',
        'approved' => 'Demande de blocage approuvée avec succès',
        'rejected' => 'Demande de blocage rejetée',
        'pending' => 'Blocages en attente récupérés',
        'no_profile' => 'Aucun profil d\'enseignant trouvé pour cet utilisateur',
        'my_list' => 'Vos blocages récupérés',
        'availability_checked' => 'Disponibilité de l\'enseignant vérifiée',
    ],

    'errors' => [
        'unauthenticated' => 'Non authentifié.',
        'tenant_suspended' => 'Votre compte est suspendu. Contactez votre administrateur.',
        'demo_delete_disabled' => 'Les suppressions sont désactivées sur le tenant démo.',
        'feature_not_included' => 'Cette fonctionnalité n\'est pas incluse dans votre pack d\'abonnement.',
    ],
];
