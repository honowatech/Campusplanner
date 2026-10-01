# Backlog priorisé — CampusTrack Planner

> Issu de l'audit complet des deux projets (frontend React + API Laravel) et des corrections déjà appliquées.
> Effort : XS < 1 h · S < 1 jour · M = quelques jours.
> Critères de priorisation : risque sécurité réel → bugs fonctionnels actuels → intégrité des données → dette technique.

## Audit n°2 — 2026-10-01 (nouvelle passe approfondie)

Nouveaux constats (non couverts par le premier audit), vérifiés dans le code puis corrigés en session.

### P0 — Critique / sécurité

- [x] **Fuite de secrets** — `planner-api.campustrack.cm/session-ses_3953.md` (transcript d'agent, ~5 800 lignes, commité) contenait `APP_KEY` + `.env` complet (`DB_USERNAME=root`, `DB_PASSWORD=`). ✅ Fichier supprimé + `session-*.md` ajouté au `.gitignore`. ⚠️ **Reste** : purger l'historique git (filter-branch/filter-repo) et **faire tourner `APP_KEY`** si elle a pu être réutilisée en prod.
- [x] **Élévation de privilèges** — `UserRoleController` (assign/sync/remove roles) et `RoleController::assignUsers` laissaient un `administrateur` s'attribuer `super-admin`. ✅ Garde ajoutée : seul un super-admin gère `super-admin`/`administrateur`.
- [x] **XSS réfléchi** — `echo <script>…alert(input)…</script>` dans `Planning::createPlanning` (entrée utilisateur `shift_plannings`). ✅ Echo supprimé.
- [x] **Importmap CDN tiers** — `index.html` référençait `react`/`recharts`/`lucide-react`/`@google/genai`/`jspdf`/`xlsx` depuis des CDN (`aistudiocdn.com`, `esm.sh`) + `/index.css` inexistant. ✅ Importmap et lien mort supprimés.

### P1 — Bugs actifs corrigés (API)

- [x] **CRUD `room-blockings` → 500** : relation `createdBy` absente du modèle. ✅ `RoomBlocking::createdBy()` ajoutée.
- [x] **`students/{id}/move-to-class` cassé** : validation `class_id` mais lecture `course_class_id`. ✅ Clé unifiée.
- [x] **Route `enseignants/shift-plannings`** pointait vers `enseignantsSelect` (méthode absente). ✅ Renommée `teachersSelect`.
- [x] **`RoleController` colonnes `level`/`description` inexistantes** → 500 sur store/update/index?level=. ✅ Colonnes retirées du code/validation.
- [x] **Génération auto : enseignant occupé sélectionné** (négation `!` sur `findAvailableTeachers`). ✅ Logique corrigée.
- [x] **`teacher-blockings/check-availability` → 500** : `with(['class','subject'])` sur `ShiftPlanning`. ✅ Remplacé par `courseClass`/`course`.
- [x] **Scoping planning inopérant** : `department_id` inexistant sur `planning_plannings`. ✅ Accesseur `getDepartmentIdAttribute()` déduit le département via `shiftPlannings.courseClass`. ⚠️ **Reste** : `plannings.edit.class`/`.subject` retournent encore `true` inconditionnellement — exige un modèle de périmètre classe/matière (décision produit).

### P1 — Bugs actifs corrigés (Frontend)

- [x] **Mapping de rôles** — `convertApiUserToUser` ne produisait jamais `'admin'`/`'hod'` et ignorait `responsable-departement`/`personnel-administratif` ; `defaultRole='admin'` dangereux. ✅ Normalisé (`administrateur`→`admin`, `responsable-departement`→`hod`) et défaut passé à `'etudiant'`.
- [x] **`RoleManager` sélection aléatoire destructive** (`Math.random()`). ✅ Remplacée par sélection vide + TODO (charger les permissions réelles).
- [x] **Clé SMS affichée en clair** (`type="text"` + label « AES-256 » trompeur). ✅ `type="password"`, label supprimé.
- [x] **`checkRoomAvailability` sans préfixe `/api`**. ✅ URL corrigée.
- [x] **Sidebar sans garde de rôle** — `users`/`settings` visibles par tous. ✅ Masqués pour les non-admins. ⚠️ **Reste** : gardes de routes (`__root.tsx`) et filtrage fin par rôle (un étudiant voit encore `departments`/`courses`/`rooms`…).

### P2 — traités en session (2e passe)

- [x] `DepartmentController::destroy` : `authorize('delete')` ajouté.
- [x] `CourseClassController` : `room_id` validé `exists:rooms,id`.
- [x] `SchedulingController::applyProposal` : validation des cibles (`room_id`/`teacher_id` `exists`, créneaux).
- [x] `DashboardPermission` : réponse normalisée (`status`) + contrôle de rôle redondant retiré.
- [x] `UpdateUserRequest` (mort) supprimé.
- [x] Règles `unique` : virgule finale retirée (`UpdateTeacherRequest`, `UpdateStudentRequest`, `UpdateRoomRequest`).
- [x] FormRequests module Planning : `authorize()` avec `can()` (Store/Update Planning + ShiftPlanning).
- [x] Frontend : `courseService.getAll` via `apiClient.get`, typo `' id'` corrigée, `teacher_id`/`room_id` optionnels (plus de `|| 0`).

### P1 — traités en session (2e passe)

- [x] **P1-7 (périmètre classe/matière)** — `PlanningPolicy`/`ShiftPlanningPolicy` : `plannings.edit.class`/`.subject` (et `view.class`/`.subject`) ne donnent plus un accès global (`return true`). Scope réel : classes de l'étudiant + classes enseignées par le professeur (via `ShiftPlanning`), matières via `Teacher.courses()`. Relations `User::teacher()`/`User::student()` ajoutées, helpers `userClassIds()`/`userCourseIds()` dans `Policy`. Test `PlanningScopeTest`.
- [x] **P1-13 (gardes de routes + rôles auth)** — cause racine corrigée : `login`/`authUser` renvoyaient le user **sans rôles/permissions** (et `login` avec un shape `{user}` incohérent) → le front ne pouvait pas déterminer le rôle. Désormais `authUserPayload()` renvoie rôles + permissions effectives (`getAllPermissions()`), shape unifié. Front : `routePermissions.ts` (miroir des permissions `viewAny`) + garde dans `__root.tsx` + Sidebar filtrée. Tests `CookieAuthTest`/`RegistrationApprovalTest` alignés.

### P3 — traités en session (dette module Planning)

- [x] **Dette module Planning** — supprimé : vues Blade (`Resources/views`), traductions (`Resources/lang`), assets (`Resources/assets`), `Routes/web.php` (routes web mortes + middleware `TestConnexion` inexistant), `helper.php`, contrôleur `DashboardController` (mort + `doesntHave('employees')` cassé), `webpack.mix.js` + `package.json` (build Blade). Contrôleurs simplifiés (plus de retour de vue) et méthodes mortes retirées (`getForm`, `getList`, `roomsSelect`, `getSelectOptions`) ; façade `Planning` nettoyée (méthodes Workstation + helpers de vue + `Log::debug`). `RouteServiceProvider`/`PlanningServiceProvider` nettoyés.

### P2 — traités en session (2e passe)

- [x] `clearCache` : suppression ciblée par préfixe (store « database ») + repli `flush`.
- [x] `TIMESTAMPDIFF` remplacé par `hours_diff_expr()` (portable MySQL/SQLite) dans `ResourceAvailabilityService`.
- [x] Devtools router : import corrigé (`@tanstack/router-devtools`) + rendu conditionnel (`import.meta.env.DEV`).
- [x] `ToggleSwitch` sorti du rendu (`SettingsManager`) + `type="button"`.
- [x] Gestion d'erreur : `Login` (erreurs de champs 422), `Dashboard` (`try`/`finally`), `index` (toast).
- [x] Dead code front supprimé : `RoleManager`, `useRbac`, `usePermissions`, `availabilityService`, `rbacService`, `Workstation`, `routeTree.gen.ts` racine, ré-exports `useRbac`.
- [x] `params: any` typé ; méthodes mortes `getByDepartment`/`getUnassigned` supprimées.

### P3 — traités en session

- [x] **Graphes Dashboard** — `departmentDistribution` affiche le nom du département (plus l'ID) ; « coursesPerDept » utilise le vrai comptage de cours par département (`coursesPerDepartment`) ; `displayedTeachers` filtre réellement les enseignants du HOD.
- [x] **Accessibilité** — `alt` sur le logo (Sidebar) ; `div onClick` accessibles au clavier (Dashboard, TeacherManager) via `role="button"` + `onKeyDown`.
- [x] **Accessibilité fine** — `type="button"` sur 73 boutons d'action (`type="submit"` conservés), 81 paires `htmlFor`/`id` ajoutées (18 fichiers).

### Reste à traiter (décisions / infra / profilage)

- **P2 restant** : casts `any[]` restants dans `planningService` (shapes de réponse à typer).

Validation : `tsc --noEmit` OK ; suite API **82 tests / 272 assertions** verte.

---

## Déjà résolu dans les commits précédents

- Bugs 500 des relations renommées (`matieres`, `classe`, `subject`…), `POST /users` restauré
- Chevauchements horaires incomplets, double préfixe `/api/plannings/plannings`, SQL non portable (SQLite)
- Throttle login/register, expiration des tokens Sanctum, mots de passe par défaut côté API
- Policies sur tous les CRUD + permissions `courses.*` / `rooms.manage`
- Inscription avec choix du rôle + validation par un administrateur
- Suspension/réactivation de compte avec révocation immédiate des tokens
- Login démo sans mot de passe supprimé
- Frontend : filtre TeacherManager, gestion 401, `onError` sur les mutations, TS strict (117 erreurs corrigées), dépendances mortes supprimées

---

## P0 — À corriger maintenant (bugs actifs, utilisateurs impactés aujourd'hui)

- [x] **1. Création d'utilisateurs cassée** — ✅ Corrigé : `POST /api/users` accepte et assigne `roles` (garde : seuls les rôles non-admin, sauf super-admin), création dans une transaction ; le modal frontend collecte mot de passe + confirmation et propose les rôles backend réels ; `password123` en dur supprimé. Tests : `UserCreationWithRolesTest` (4 cas).
- [x] **2. `.env` frontend malformé** — ✅ **Non-issue (erreur de diagnostic)** : la « ligne fusionnée » était un artefact de `cat .env*` concaténant `.env` et `.env.local` sans séparateur. Le fichier est sain ; `GEMINI_API_KEY` vit dans `.env.local` — le vrai sujet reste le point P1-4 (clé exposée côté client).
- [x] **3. Toasts inversés** — ✅ Corrigé : messages corrects et francisés dans `useCreateUser`/`useUpdateUser`.

## P1 — Avant toute mise en production

- [x] **4. Clé API Gemini côté client** — ✅ Corrigé : proxy serveur `POST /api/ai/generate` (auth Sanctum, throttle 10/min, liste blanche de modèles, bornes de taille) ; la clé vit dans le `.env` de l'API via `config/services.php`. Frontend : `geminiService` passe par le proxy, `@google/genai` retiré, `define` du `vite.config.ts` supprimé, clé retirée du `.env.local`. Vérifié : aucune trace de clé dans `dist/`. **Action restante pour vous** : renseigner `GEMINI_API_KEY` dans le `.env` de l'API (vide pour l'instant → 503 propre).
- [ ] **5. Configuration d'environnement** — `APP_ENV=production`, `APP_DEBUG=false`, MySQL root sans mot de passe, mots de passe dev du `UserSeeder`. *(Effort : S)*
- [x] **6. Transactions DB** — ✅ Corrigé : `TeacherController::store`, `StudentController::store` (user + rôle + étudiant) et `SchedulingService::generateAutomatic` (génération tout-ou-rien) dans `DB::transaction` ; déjà fait pour `POST /users`. Test de rollback atomique (échec d'assignation de rôle ne laisse aucun utilisateur orphelin).
- [x] **7. Calcul de durée faux** — ✅ Corrigé : différence horaire réelle (`strtotime`) au lieu de l'arithmétique décimale HHMM, sémantique ceil/min 1h conservée. Précision : `calculated_hours` sert au découpage en blocs d'1 h, et avec la validation actuelle (départ à l'heure pile) l'ancien calcul donnait par accident le même résultat — le piège est levé, pas de changement de données stockées.
- [x] **8. Dump SQL avec données dans l'historique git** — ✅ Corrigé : `campus_track_planner.sql` retiré de tout l'historique (filter-branch + gc), untracké et gitignoré ; le fichier reste sur disque (non versionné). Historique API réécrit : `d3b5a46` → `21a1ddc` → `12136c0`.

## P2 — Court terme (qualité, robustesse, prévention)

- [x] **9. ESLint + Prettier + CI** — ✅ Frontend : ESLint (flat config, 28 erreurs corrigées : imports morts, helpers inutilisés, TDZ `fetchDashboardData`), Prettier appliqué, scripts `lint`/`format`. API : Laravel Pint (preset laravel, codebase formaté). Workflows GitHub Actions dans les deux dépôts (lint+tsc+build / pint+phpunit). ⚠️ Sans remote, les workflows ne s'exécutent nulle part — créer le remote et pousser pour les activer.
- [x] **10. Tests des CRUD principaux** — ✅ `CrudEndpointsTest` (10 tests) : CRUD complet teachers/rooms/students/plannings, suppressions gardées (400 si plannings), matrice 401/403, régression des relations renommées, comptage des conflits réels. Suite : 74 tests / 230 assertions.
- [x] **11. Index et FK manquants** — ✅ Précision d'audit : l'index composite `teacher_blockings` et la FK/index `students.course_class_id` existaient déjà. Réellement manquant : FK `classes.room_id` → ajoutée (MySQL, orphelins neutralisés, `nullOnDelete`), appliquée à la base dev.
- [x] **12. Dashboard : détection de conflits fausse** — ✅ Nouveau `schedule_conflicts` calculé côté API (chevauchements réels enseignant/salle/classe, SQL portable, scoping département) ; le Dashboard affiche cette valeur. Un bug de sous-comptage dans la première version de la requête a été corrigé par le test de régression.
- [ ] **13. Jeton en localStorage** — 🟡 **Code fait, validation navigateur restante**. Migration SPA Sanctum livrée : `statefulApi()` + `EnsureUserIsApproved` côté API, `withCredentials`/`withXSRF-TOKEN` + bootstrap CSRF et suppression du Bearer/`api-user` côté front, docs et tests réécrits (`CookieAuthTest` : 8 tests, session cookie rejouée comme un navigateur). `SANCTUM_STATEFUL_DOMAINS`, CORS `supports_credentials` et `SESSION_SECURE_COOKIE` sont alignés ; `.env` de prod à compléter. **Reste** : passer l'app en local puis en prod (`planner.campustrack.cm` → `planner-api.campustrack.cm`) pour valider login / rechargement dur / mutation sans 419 / logout — les cookies cross-origine et le CSRF ne se prouvent qu'au navigateur. Mitigations conservées : expiration de session (`SESSION_LIFETIME`), approbation admin, suspension immédiate, throttle.
- [x] **14. `max_iterations` non validé** — ✅ Borné `1..1000` dans la validation de `POST /plannings/generate`.

## P3 — Backlog (endurance, ergonomie, confort)

- [x] **15. Dette frontend** — ✅ `SmartPagination` remplace la pagination dupliquée dans 7 managers (−741 lignes, y compris la 2ᵉ pagination de TeacherManager) ; `createCrudHooks` remplace 5 fichiers de hooks quasi identiques (~370 → ~30 lignes chacun, exports inchangés). Restent en P3-backlog : extraction du dictionnaire `i18n.tsx` et modal dupliqué de TeacherManager (couvert par le composant si besoin).
- [x] **16. Performance frontend** — ✅ partiel : `manualChunks` Vite (recharts 379 ko, react-query 50 ko isolés ; plus aucun chunk > 500 ko). Non fait à dessein : `React.memo`/`useCallback` et le sur-fetch de la page Teachers — sans profilage réel, le coût/risque dépasse le gain ; à rouvrir avec des données de perf.
- [x] **17. FormRequests + API Resources** — ✅ FormRequests (Store/Update) pour teachers/rooms/students avec autorisation dans `authorize()` (le contrôle d'accès précède la validation, plus de fuite de 422 avant 403). API Resources : non fait — un passthrough pur serait de la cérémonie ; à faire si un versionnement d'API devient nécessaire.
- [x] **18. UX login** — ✅ état `isAuthenticating` dédié (plus d'application entière remplacée par un spinner pendant la connexion) ; titre de page avec repli lisible quand la clé i18n manque.
- [x] **19. Code mort du module Planning** — ✅ chaîne Workstation supprimée (entities, contrôleur, FormRequests, vues, lang, helper, routes commentées, entrée sidebar — le lien pointait vers une route inexistante), entity `Role` (0 référence) supprimée, blocs commentés nettoyés. Migrations conservées (bases déployées).
- [ ] **20. Vérification d'email** — ⏸ reporté : exige une config SMTP (identifiants, expediteur) et un envoi réel testable. L'approbation admin bloque déjà les comptes inconnus ; plan : colonne déjà présente (`email_verified_at`), Notification Laravel après approbation, route signée de vérification.
- [x] **21. Décision rooms.block** — ✅ statué : **non**, conforme à l'intention du seeder d'origine (seuls administrateur/super-admin ont `rooms.block`). Aucun changement de code requis.
- [ ] **22. Confort (temps réel / SMS)** — ⏸ reporté : la déconnexion temps réel exige un choix d'infra (Laravel Reverb/Pusher) et la clé SMS dépend du maintien de la fonctionnalité settings. À rouvrir si besoin.

---

## Ordre d'exécution recommandé

1. ~~**P0 (1-3)** en une session~~ ✅ fait.
2. ~~**Point 8** (retrait du dump SQL)~~ ✅ fait (historique réécrit, dump untracké).
3. ~~**P1-4 / P1-6 / P1-7**~~ ✅ faits. **P1-5 restant** : au moment du déploiement (config prod, MySQL, seeder).
4. ~~**P2 (9-12, 14)**~~ ✅ faits. **P2-13** (httpOnly) : code livré, reste la passe navigateur (local puis prod).
5. ~~**P3 (15-19, 21)**~~ ✅ faits. **P3-20/22** : selon infra (SMTP / Reverb).

Restent donc : **P1-5**, **P2-13** (passe navigateur), **P3-20**, **P3-22** — les quatre attendent des décisions d'infrastructure, un contexte de déploiement ou une session avec l'app lancée.
