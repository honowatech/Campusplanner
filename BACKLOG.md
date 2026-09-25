# Backlog priorisé — CampusTrack Planner

> Issu de l'audit complet des deux projets (frontend React + API Laravel) et des corrections déjà appliquées.
> Effort : XS < 1 h · S < 1 jour · M = quelques jours.
> Critères de priorisation : risque sécurité réel → bugs fonctionnels actuels → intégrité des données → dette technique.

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
- [ ] **13. Jeton en localStorage** — ⏸ **Reporté volontairement** : migration vers le mode SPA Sanctum (cookie httpOnly). Étapes : `SANCTUM_STATEFUL_DOMAINS=localhost:3000`, CORS `supports_credentials`, `SESSION_DOMAIN`, route `/sanctum/csrf-cookie`, axios `withCredentials` + en-tête `X-XSRF-TOKEN`, retrait du Bearer/localStorage (`api.ts`, `auth.tsx`), adaptation des tests. **Pourquoi attendre** : les cookies cross-origine (3000 → 8000) et le flot CSRF ne se valident que dans un vrai navigateur — à faire en session dédiée avec l'app lancée. Mitigations déjà en place : expiration 3 j, révocation immédiate, approbation admin, throttle.
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
4. ~~**P2 (9-12, 14)**~~ ✅ faits. **P2-13** (httpOnly) : session navigateur dédiée.
5. ~~**P3 (15-19, 21)**~~ ✅ faits. **P3-20/22** : selon infra (SMTP / Reverb).

Restent donc : **P1-5**, **P2-13**, **P3-20**, **P3-22** — les quatre attendent des décisions d'infrastructure ou un contexte de déploiement.
