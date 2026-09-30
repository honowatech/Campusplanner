import React, { createContext, useState, useContext, ReactNode } from 'react';

type Language = 'en' | 'fr';

const translations = {
  en: {
    // General
    appName: (import.meta as any).env.VITE_APP_NAME,
    dashboard: 'Dashboard',
    departments: 'Departments',
    classes: 'Classes',
    teachers: 'Teachers',
    users: 'Users',
    courses: 'UE (Courses)',
    courseClassCode: 'Course Class Code',
    rooms: 'Rooms',
    timetable: 'Timetable',
    timetables: 'Timetables',
    profile: 'My Profile',
    settings: 'Settings',
    search: 'Search',
    add: 'Add',
    edit: 'Edit',
    delete: 'Delete',
    save: 'Save',
    cancel: 'Cancel',
    actions: 'Actions',
    unknown: 'Unknown',
    logout: 'Logout',
    export: 'Export',
    exportPDF: 'Export PDF',
    exportExcel: 'Export Excel',
    confirmDelete: 'Confirm Deletion',
    confirmDeleteShift: 'Are you sure you want to delete this shift?',
    confirmDeleteDepartment:
      'Are you sure you want to delete this department? All associated classes and schedules may be affected.',
    confirmDeleteClass:
      'Are you sure you want to delete this Filière? All associated schedules may be affected.',
    confirmDeleteTeacher:
      'Are you sure you want to delete this teacher? All associated schedules will be affected.',
    deleteCourseMessage:
      'Are you sure you want to delete this UE? This will free up the time slot and room.',

    // Login
    welcomeBack: 'Welcome Back',
    loginSubtitle: 'Sign in to access your university dashboard',
    email: 'Email Address',
    password: 'Password',
    signIn: 'Sign In',
    loginAsAdmin: 'Login as Admin',
    loginAsHod: 'Login as Head of Dept.',
    adminRole: 'Administrator',
    hodRole: 'Head of Department',

    // Profile
    profileDetails: 'Profile Details',
    role: 'Role',
    managedDept: 'Managed Department',
    accountSettings: 'Account Settings',
    personalInfo: 'Personal Information',

    // Settings
    managementRules: 'Management Rules',
    generalSettings: 'General Settings',
    apiKeys: 'API Key',
    rulesDescription: 'Define the constraints and limits for scheduling and workload.',
    constraintSettings: 'Conflict Constraints',
    constraintDescription: 'Enable automated checks to prevent scheduling conflicts.',
    maxWeeklyHours: 'Max Hours per Teacher (Weekly)',
    maxConsecutiveHours: 'Max Consecutive Hours',
    maxDailyHoursTeacher: 'Max Hours per Teacher (Daily)',
    maxDailyHoursClass: 'Max Hours per Filière (Daily)',
    settingsSaved: 'Settings saved successfully.',
    enableRoomConflict: 'Prevent Double Booking Rooms',
    enableTeacherConflict: 'Prevent Teacher Conflicts',
    enableGroupConflict: 'Prevent Filière Conflicts',
    smsNexah: 'SMS Nexah API',
    senderId: 'Sender ID',
    smsBalance: 'SMS Balance',
    apiKey: 'API Key',

    // Conflicts
    conflictError: 'Scheduling Conflict',
    conflictRoomMsg: 'The selected room is already occupied at this time.',
    conflictTeacherMsg: 'The selected teacher is already teaching at this time.',
    conflictGroupMsg: 'The selected Filière already has a UE at this time.',
    shiftMaxDuration: 'Maximum shift duration is 4 hours',
    shiftLunchBreak: 'No shift allowed between 12:00 and 13:00',
    shiftInvalidMinutes: 'Minutes must always be 00',

    // Dashboard
    totalTeachers: 'Total Teachers',
    activeCourses: 'Active UEs',
    conflictsDetected: 'Conflicts Detected',
    aiInsights: 'AI Insights',
    analyzing: 'Analyzing...',
    generateReport: 'Generate Report',
    deptDistribution: 'Department Distribution',
    coursesPerDept: 'UEs per Department',
    smartAnalysis: 'Gemini Smart Analysis',
    execSummary: 'Executive Summary',
    suggestedOptimizations: 'Suggested Optimizations',

    // Teachers
    addTeacher: 'Add Teacher',
    editTeacher: 'Edit Teacher',
    newTeacher: 'New Teacher',
    searchTeachers: 'Search teachers...',
    fullName: 'Full Name',
    department: 'Department',
    colorTag: 'Color Tag',
    speciality: 'speciality',
    teacherDetails: 'Teacher Details',
    weeklyHours: 'Weekly Workload',
    assignedClasses: 'Assigned Filières',
    totalSessions: 'Total Sessions',
    teachingSchedule: 'Teaching Schedule',
    noClassesAssigned: 'No UEs assigned yet.',
    phoneNumber: 'Phone Number',
    address: 'Address',
    bio: 'Biography / Brief Presentation',
    aboutTeacher: 'About',

    // Users
    addUser: 'Add User',
    createAccount: 'Create Account',
    noAccount: 'No account yet?',
    haveAccount: 'Already have an account?',
    confirmPassword: 'Confirm password',
    requestedRole: 'Requested role',
    selectRequestedRole: 'Select a role...',
    roleProfesseur: 'Teacher',
    roleEtudiant: 'Student',
    rolePersonnel: 'Administrative staff',
    roleResponsable: 'Department head',
    registering: 'Creating account...',
    accountPending: 'Account created. An administrator must validate it before you can sign in.',
    pendingApprovalsEmpty: 'No pending accounts.',
    requested: 'Requested role',
    rejectAccount: 'Reject',
    deactivateUser: 'Suspend',
    reactivateUser: 'Reactivate',
    deactivated: 'Suspended',
    accountPendingLogin: 'Your account is pending validation by an administrator.',
    editUser: 'Edit User',
    newUser: 'New User',
    searchUsers: 'Search users...',
    selectRole: 'Select Role',
    selectDept: 'Select Department',

    // Departments
    addDepartment: 'Add Department',
    editDepartment: 'Edit Department',
    newDepartment: 'New Department',
    searchDepartments: 'Search departments...',
    deptName: 'Department Name',
    deptCode: 'Department Code',
    headOfDept: 'Head of Department',
    selectTeacher: 'Select a teacher...',

    // Classes (Student Groups -> Filières)
    addClass: 'Add Filière',
    editClass: 'Edit Filière',
    newClass: 'New Filière',
    searchClasses: 'Search Filières...',
    classDisplayName: 'Nom de la Filière',
    classCode: 'Code Filière (ex: GL1)',
    major: 'Spécialité',
    level: 'Niveau',
    numStudents: "Nombre d'Étudiants",
    students: 'Étudiants',
    // selectDept removed to fix duplicate key error

    // Courses -> UEs
    addCourse: 'Add UE',
    editCourse: 'Modifier UE',
    newCourse: 'Nouvelle UE',
    searchCourses: 'Rechercher UE...',
    courseName: 'Intitulé UE',
    courseCode: 'Code UE',
    courseSubject: 'Sujet UE',
    selectClass: 'Sélectionner une Filière',
    schedule: 'Horaire',
    day: 'Jour',
    timeSlot: 'Créneau',

    // Rooms
    addRoom: 'Ajouter Salle',
    editRoom: 'Modifier Salle',
    newRoom: 'Nouvelle Salle',
    searchRooms: 'Rechercher salles...',
    roomName: 'Nom de la Salle',
    capacity: 'Capacité',
    type: 'Type',
    building: 'Bâtiment / Lieu',
    selectRoom: 'Sélectionner une salle',

    // Timetable Periods
    history: 'Historique',
    createPeriod: 'Créer Emploi du Temps',
    newPeriod: 'Nouveau Emploi du Temps',
    periodName: 'Nom de la Période',
    description: 'Description',
    startDate: 'Date de Début',
    endDate: 'Date de Fin',
    activePeriod: 'Période Active',
    viewTimetable: "Voir l'emploi du temps",
    selectPeriod: 'Sélectionner une période',
    periodCreated: 'Période créée avec succès.',
    noPeriods: 'Aucune période créée pour le moment.',

    // Timetable
    activeSchedule: 'Active Schedule',
    academicYear: 'Academic Year',
    allDepts: 'All Departments',
    allMajors: 'All Majors',
    allClasses: 'All Classes',
    addSchedule: 'Schedule a class',
    timeDay: 'Time / Day',
    viewStandard: 'Standard View',
    viewGlobal: 'Global View',
    shifts: 'Shifts',

    // Students
    addStudent: 'Add Student',
    editStudent: 'Edit Student',
    newStudent: 'New Student',
    searchStudents: 'Search students...',
    firstName: 'First Name',
    lastName: 'Last Name',
    matricule: 'Matricule',
    gender: 'Gender',
    male: 'Male',
    female: 'Female',
    other: 'Other',
    class: 'Class',
    targetClass: 'Target Class',
    status: 'Status',
    active: 'Active',
    inactive: 'Inactive',
    noStudentsFound: 'No students found',
    moveStudentToClass: 'Move student to class',
    movingStudent: 'Moving student',
    move: 'Move',

    // Blockings
    addBlocking: 'Add Blocking',
    editBlocking: 'Edit Blocking',
    newBlocking: 'New Blocking',
    searchBlockings: 'Search blockings...',
    blockingType: 'Blocking Type',
    maintenance: 'Maintenance',
    event: 'Event',
    holiday: 'Holiday',
    absence: 'Absence',
    vacation: 'Vacation',
    training: 'Training',
    medical: 'Medical',
    reason: 'Reason',
    startDateTime: 'Start Date/Time',
    endDateTime: 'End Date/Time',
    isRecurring: 'Recurring',
    noBlockingsFound: 'No blockings found',
    allRooms: 'All Rooms',
    allTeachers: 'All Teachers',
    allStatuses: 'All Statuses',
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected',
    approve: 'Approve',
    reject: 'Reject',
    submit: 'Submit',
    rejectionReason: 'Rejection Reason',
    optional: 'Optional',
    rejectingBlockingFor: 'Rejecting blocking for',
    pendingApprovals: 'Pending Approvals',
    more: 'more',

    // RBAC
    rolesAndPermissions: 'Roles & Permissions',
    manageRolesDescription: 'Manage roles and their associated permissions',
    addRole: 'Add Role',
    editRole: 'Edit Role',
    newRole: 'New Role',
    roleName: 'Role Name',
    guard: 'Guard',
    system: 'System',
    permissions: 'Permissions',
    noRolesFound: 'No roles found',
    managePermissions: 'Manage Permissions',
    savePermissions: 'Save Permissions',
    create: 'Create',

    // Dashboard additional
    lastUpdate: 'Last update',
    occupied: 'occupied',
    planningStats: 'Planning Statistics',
    completed: 'Completed',
    ongoing: 'Ongoing',
    canceled: 'Canceled',
  },
  fr: {
    // General
    appName: (import.meta as any).env.VITE_APP_NAME,
    dashboard: 'Tableau de Bord',
    departments: 'Départements',
    classes: 'Filières',
    teachers: 'Enseignants',
    users: 'Utilisateurs',
    courses: 'UE (Cours)',
    courseClassCode: 'Code de la Classe',
    rooms: 'Salles',
    timetable: 'Emploi du Temps',
    timetables: 'Emplois du Temps',
    profile: 'Mon Profil',
    settings: 'Paramètres',
    search: 'Rechercher',
    add: 'Ajouter',
    edit: 'Modifier',
    delete: 'Supprimer',
    save: 'Enregistrer',
    cancel: 'Annuler',
    actions: 'Actions',
    unknown: 'Inconnu',
    logout: 'Déconnexion',
    export: 'Exporter',
    exportPDF: 'Exporter PDF',
    exportExcel: 'Exporter Excel',
    confirmDelete: 'Confirmer la suppression',
    confirmDeleteShift: 'Êtes-vous sûr de vouloir supprimer ce créneau ?',
    confirmDeleteDepartment:
      'Êtes-vous sûr de vouloir supprimer ce département ? Toutes les filières et tous les horaires associés pourraient être affectés.',
    confirmDeleteClass:
      'Êtes-vous sûr de vouloir supprimer cette Filière ? Tous les horaires associés pourraient être affectés.',
    confirmDeleteTeacher:
      'Êtes-vous sûr de vouloir supprimer cet enseignant ? Tous les horaires associés seront affectés.',
    deleteCourseMessage:
      'Êtes-vous sûr de vouloir supprimer cette UE ? Cela libérera le créneau horaire et la salle.',

    // Login
    welcomeBack: 'Bon retour',
    loginSubtitle: 'Connectez-vous pour accéder à votre tableau de bord',
    email: 'Adresse Email',
    password: 'Mot de passe',
    signIn: 'Se Connecter',
    loginAsAdmin: 'Admin',
    loginAsHod: 'Chef de Dépt.',
    adminRole: 'Administrateur',
    hodRole: 'Chef de Département',

    // Profile
    profileDetails: 'Détails du Profil',
    role: 'Rôle',
    managedDept: 'Département Géré',
    accountSettings: 'Paramètres du Compte',
    personalInfo: 'Informations Personnelles',

    // Settings
    managementRules: 'Règles de Gestion',
    generalSettings: 'Paramètres Généraux',
    apiKeys: 'Clé API',
    rulesDescription:
      'Définissez les contraintes et limites pour la planification et la charge de travail.',
    constraintSettings: 'Contraintes de Conflit',
    constraintDescription:
      'Activez les vérifications automatiques pour éviter les conflits de planning.',
    maxWeeklyHours: 'Heures Max par Enseignant (Hebdo)',
    maxConsecutiveHours: 'Heures Consécutives Max',
    maxDailyHoursTeacher: 'Heures Max par Enseignant (Jour)',
    maxDailyHoursClass: 'Heures Max par Filière (Jour)',
    settingsSaved: 'Paramètres enregistrés avec succès.',
    enableRoomConflict: 'Éviter les doubles réservations de salle',
    enableTeacherConflict: "Éviter les conflits d'enseignants",
    enableGroupConflict: 'Éviter les conflits de filières',
    smsNexah: 'API SMS Nexah',
    senderId: 'Sender ID',
    smsBalance: 'Solde SMS',
    apiKey: 'Clé API',

    // Conflicts
    conflictError: 'Conflit de Planification',
    conflictRoomMsg: 'La salle sélectionnée est déjà occupée à cette heure.',
    conflictTeacherMsg: "L'enseignant sélectionné a déjà cours à cette heure.",
    conflictGroupMsg: 'La filière sélectionnée a déjà une UE à cette heure.',
    shiftMaxDuration: "La durée maximale d'un créneau est de 4 heures",
    shiftLunchBreak: 'Aucun créneau entre 12h00 et 13h00',
    shiftInvalidMinutes: 'Les minutes doivent être à 00',

    // Dashboard
    totalTeachers: 'Total Enseignants',
    activeCourses: 'UEs Actives',
    conflictsDetected: 'Conflits Détectés',
    aiInsights: 'Analyses IA',
    analyzing: 'Analyse en cours...',
    generateReport: 'Générer Rapport',
    deptDistribution: 'Répartition Départements',
    coursesPerDept: 'UEs par Département',
    smartAnalysis: 'Analyse Intelligente Gemini',
    execSummary: 'Résumé Exécutif',
    suggestedOptimizations: 'Optimisations Suggérées',

    // Teachers
    addTeacher: 'Ajouter Enseignant',
    editTeacher: 'Modifier Enseignant',
    newTeacher: 'Nouvel Enseignant',
    searchTeachers: 'Rechercher enseignants...',
    fullName: 'Nom Complet',
    department: 'Département',
    colorTag: 'Étiquette Couleur',
    speciality: 'Spécialité',
    teacherDetails: "Détails de l'Enseignant",
    weeklyHours: 'Charge Hebdomadaire',
    assignedClasses: 'Filières Attribuées',
    totalSessions: 'Total Séances',
    teachingSchedule: 'Emploi du Temps',
    noClassesAssigned: 'Aucune UE attribuée pour le moment.',
    phoneNumber: 'Numéro de Téléphone',
    address: 'Adresse',
    bio: 'Biographie / Brève Présentation',
    aboutTeacher: 'À Propos',

    // Users
    addUser: 'Ajouter Utilisateur',
    createAccount: 'Créer un compte',
    noAccount: 'Pas encore de compte ?',
    haveAccount: 'Vous avez déjà un compte ?',
    confirmPassword: 'Confirmer le mot de passe',
    requestedRole: 'Rôle demandé',
    selectRequestedRole: 'Sélectionner un rôle...',
    roleProfesseur: 'Enseignant',
    roleEtudiant: 'Étudiant',
    rolePersonnel: 'Personnel administratif',
    roleResponsable: 'Responsable de département',
    registering: 'Création du compte...',
    accountPending: 'Compte créé. Un administrateur doit le valider avant connexion.',
    pendingApprovalsEmpty: 'Aucun compte en attente.',
    requested: 'Rôle demandé',
    rejectAccount: 'Rejeter',
    deactivateUser: 'Suspendre',
    reactivateUser: 'Réactiver',
    deactivated: 'Suspendu',
    accountPendingLogin: 'Votre compte est en attente de validation par un administrateur.',
    editUser: 'Modifier Utilisateur',
    newUser: 'Nouvel Utilisateur',
    searchUsers: 'Rechercher utilisateurs...',
    selectRole: 'Sélectionner Rôle',
    selectDept: 'Sélectionner Département',

    // Departments
    addDepartment: 'Ajouter Département',
    editDepartment: 'Modifier Département',
    newDepartment: 'Nouveau Département',
    searchDepartments: 'Rechercher départements...',
    deptName: 'Nom du Département',
    deptCode: 'Code Département',
    headOfDept: 'Chef de Département',
    selectTeacher: 'Sélectionner un enseignant...',

    // Classes
    addClass: 'Ajouter Filière',
    editClass: 'Modifier Filière',
    newClass: 'Nouvelle Filière',
    searchClasses: 'Rechercher Filières...',
    classDisplayName: 'Nom de la Filière',
    classCode: 'Code Filière (ex: GL1)',
    major: 'Spécialité',
    level: 'Niveau',
    numStudents: "Nombre d'Étudiants",
    students: 'Étudiants',

    // Courses -> UEs
    addCourse: 'Ajouter UE',
    editCourse: 'Modifier UE',
    newCourse: 'Nouvelle UE',
    searchCourses: 'Rechercher UE...',
    courseName: 'Intitulé UE',
    courseCode: 'Code UE',
    courseSubject: 'Sujet UE',
    selectClass: 'Sélectionner une Filière',
    schedule: 'Horaire',
    day: 'Jour',
    timeSlot: 'Créneau',

    // Rooms
    addRoom: 'Ajouter Salle',
    editRoom: 'Modifier Salle',
    newRoom: 'Nouvelle Salle',
    searchRooms: 'Rechercher salles...',
    roomName: 'Nom de la Salle',
    capacity: 'Capacité',
    type: 'Type',
    building: 'Bâtiment / Lieu',
    selectRoom: 'Sélectionner une salle',

    // Timetable Periods
    history: 'Historique',
    createPeriod: 'Créer Emploi du Temps',
    newPeriod: 'Nouveau Emploi du Temps',
    periodName: 'Nom de la Période',
    description: 'Description',
    startDate: 'Date de Début',
    endDate: 'Date de Fin',
    activePeriod: 'Période Active',
    viewTimetable: "Voir l'emploi du temps",
    selectPeriod: 'Sélectionner une période',
    periodCreated: 'Période créée avec succès.',
    noPeriods: 'Aucune période créée pour le moment.',

    // Timetable
    activeSchedule: 'Emploi du Temps Actif',
    academicYear: 'Année Académique',
    allDepts: 'Tous Départements',
    allMajors: 'Toutes Spécialités',
    allClasses: 'Toutes Filières',
    addSchedule: 'Planifier un cours',
    timeDay: 'Heure / Jour',
    viewStandard: 'Vue Standard',
    viewGlobal: 'Vue Globale (Filières)',
    shifts: 'Créneaux',

    // Days & Times
    Morning_1: '08:00 - 10:00',
    Morning_2: '10:15 - 12:15',
    Lunch: '12:15 - 13:15',
    Afternoon_1: '13:15 - 15:15',
    Afternoon_2: '15:30 - 17:30',

    // Dashboard API
    alerts: 'Alertes',
    critical: 'Critique',
    warnings: 'Avertissements',
    viewAllAlerts: 'Voir toutes les alertes',
    noAlerts: 'Aucune alerte',
    recentActivity: 'Activité Récente',
    noRecentActivity: 'Aucune activité récente',
    daysAgo: 'j',
    hoursAgo: 'h',
    minutesAgo: 'm',
    justNow: "À l'instant",
    last24h: 'Dernières 24h',
    last7d: 'Derniers 7 jours',
    last30d: 'Derniers 30 jours',
    refresh: 'Rafraîchir',
    total: 'Total',
    utilization: 'Utilisation',
    activeNow: 'Actif maintenant',
    occupiedRooms: 'Salles occupées',
    pendingBlockings: 'Blocages en attente',
    requests: 'demandes',
    newUsers: 'Nouveaux utilisateurs',
    thisPeriod: 'cette période',
    failedToLoadDashboard: 'Impossible de charger les données',

    // Students
    addStudent: 'Ajouter Étudiant',
    editStudent: 'Modifier Étudiant',
    newStudent: 'Nouvel Étudiant',
    searchStudents: 'Rechercher étudiants...',
    firstName: 'Prénom',
    lastName: 'Nom',
    matricule: 'Matricule',
    gender: 'Genre',
    male: 'Masculin',
    female: 'Féminin',
    other: 'Autre',
    class: 'Classe',
    targetClass: 'Classe cible',
    status: 'Statut',
    active: 'Actif',
    inactive: 'Inactif',
    noStudentsFound: 'Aucun étudiant trouvé',
    moveStudentToClass: "Déplacer l'étudiant vers une classe",
    movingStudent: "Déplacement de l'étudiant",
    move: 'Déplacer',

    // Blockings
    addBlocking: 'Ajouter Blocage',
    editBlocking: 'Modifier Blocage',
    newBlocking: 'Nouveau Blocage',
    searchBlockings: 'Rechercher blocages...',
    blockingType: 'Type de Blocage',
    maintenance: 'Maintenance',
    event: 'Événement',
    holiday: 'Vacances',
    absence: 'Absence',
    vacation: 'Congé',
    training: 'Formation',
    medical: 'Médical',
    reason: 'Raison',
    startDateTime: 'Date/Heure de début',
    endDateTime: 'Date/Heure de fin',
    isRecurring: 'Récurrent',
    noBlockingsFound: 'Aucun blocage trouvé',
    allRooms: 'Toutes les salles',
    allTeachers: 'Tous les enseignants',
    allStatuses: 'Tous les statuts',
    pending: 'En attente',
    approved: 'Approuvé',
    rejected: 'Rejeté',
    approve: 'Approuver',
    reject: 'Rejeter',
    submit: 'Soumettre',
    rejectionReason: 'Raison du rejet',
    optional: 'Optionnel',
    rejectingBlockingFor: 'Rejet du blocage pour',
    pendingApprovals: 'Approbations en attente',
    more: 'plus',

    // RBAC
    rolesAndPermissions: 'Rôles et Permissions',
    manageRolesDescription: 'Gérez les rôles et leurs permissions associées',
    addRole: 'Ajouter Rôle',
    editRole: 'Modifier Rôle',
    newRole: 'Nouveau Rôle',
    roleName: 'Nom du Rôle',
    guard: 'Garde',
    system: 'Système',
    permissions: 'Permissions',
    noRolesFound: 'Aucun rôle trouvé',
    managePermissions: 'Gérer les Permissions',
    savePermissions: 'Enregistrer les Permissions',
    create: 'Créer',

    // Dashboard additional
    lastUpdate: 'Dernière mise à jour',
    occupied: 'occupée(s)',
    planningStats: 'Statistiques Planning',
    completed: 'Terminées',
    ongoing: 'En cours',
    canceled: 'Annulées',
  },
};

interface LanguageContextType {
  language: Language;
  setLanguage: (lang: Language) => void;
  t: (key: string) => string;
}

const LanguageContext = createContext<LanguageContextType | undefined>(undefined);

export const LanguageProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [language, setLanguage] = useState<Language>('en');

  const t = (key: string): string => {
    const keys = key.split('.');

    const lookup = (dict: any): string | undefined => {
      let value: any = dict;
      for (const k of keys) {
        if (value && typeof value === 'object' && value[k] !== undefined) {
          value = value[k];
        } else {
          return undefined;
        }
      }
      return typeof value === 'string' ? value : undefined;
    };

    const value = lookup(translations[language]);
    if (value !== undefined) return value;

    if (language !== 'en') {
      const enValue = lookup(translations.en);
      if (enValue !== undefined) return enValue;
    }

    return key;
  };

  return (
    <LanguageContext.Provider value={{ language, setLanguage, t }}>
      {children}
    </LanguageContext.Provider>
  );
};

export const useTranslation = () => {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useTranslation must be used within a LanguageProvider');
  }
  return context;
};
