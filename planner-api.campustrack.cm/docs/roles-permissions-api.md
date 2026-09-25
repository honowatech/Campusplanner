# Documentation API - Gestion des Rôles et Permissions

## Vue d'ensemble

Cette API fournit une gestion complète des rôles et permissions basée sur **Spatie Laravel Permission** avec support de la hiérarchie et des permissions granulaires (global, département, classe, matière).

**Base URL:** `http://your-domain/api`

**Authentication:** session cookie Sanctum (SPA, aucun token)

---

## Hiérarchie des Rôles

```
Super Admin (niveau 1)
├── Administrateur (niveau 2)
│   ├── Responsable de Département (niveau 3)
│   └── Personnel Administratif (niveau 3)
├── Professeur (niveau 2)
└── Étudiant (niveau 2)
```

### Rôles disponibles

| Rôle | Description | Niveau |
|------|-------------|--------|
| `super-admin` | Accès complet au système | 1 |
| `administrateur` | Gestion administrative globale | 2 |
| `responsable-departement` | Gestion d'un département spécifique | 3 |
| `personnel-administratif` | Support administratif | 3 |
| `professeur` | Enseignant avec accès à ses classes | 2 |
| `etudiant` | Consultation uniquement | 2 |

---

## Permissions

### Structure des permissions

Format: `{resource}.{action}.{scope}`

**Scopes disponibles:**
- `all` - Global (tous les départements)
- `department` - Par département
- `class` - Par classe
- `subject` - Par matière

### Liste des permissions

#### Gestion des utilisateurs
- `users.view.all`, `users.view.department`
- `users.create`, `users.edit`, `users.delete`

#### Gestion des rôles et permissions
- `roles.manage`
- `permissions.manage`

#### Gestion des départements
- `departments.view`, `departments.create`, `departments.edit`, `departments.delete`

#### Gestion des plannings
- `plannings.view.all`, `plannings.create.all`, `plannings.edit.all`, `plannings.delete.all`
- `plannings.view.department`, `plannings.create.department`, `plannings.edit.department`, `plannings.delete.department`
- `plannings.view.class`, `plannings.create.class`, `plannings.edit.class`
- `plannings.view.subject`, `plannings.create.subject`, `plannings.edit.subject`
- `plannings.generate.auto`, `plannings.detect.conflicts`

#### Gestion des enseignants
- `teachers.view.all`, `teachers.view.department`, `teachers.manage.department`, `teachers.block`

#### Gestion des étudiants
- `students.view.all`, `students.view.department`, `students.view.class`, `students.create`, `students.edit`, `students.delete`

#### Gestion des classes et salles
- `classes.view`, `classes.manage`
- `rooms.view`, `rooms.block`, `rooms.search`

#### Gestion des notes et bulletins
- `grades.view.all`, `grades.view.department`, `grades.view.class`, `grades.view.subject`, `grades.edit`
- `bulletins.view`, `bulletins.create`, `bulletins.edit`, `bulletins.delete`

#### Événements et calendrier
- `events.view`, `events.create`, `events.edit`, `events.delete`, `events.cancel`, `events.postpone`
- `calendar.view`, `calendar.manage`

#### Export/Import et rapports
- `export.pdf`, `export.excel`, `import.csv`
- `reports.view`, `reports.generate`

#### Autres
- `notifications.send`, `notifications.configure`
- `settings.view`, `settings.edit`
- `history.view`, `dashboard.view`, `sms.send`

---

## Endpoints API

### 1. Rôles

#### Liste des rôles
```http
GET /api/roles
```

**Query Parameters:**
- `search` (optional) - Recherche par nom
- `level` (optional) - Filtrer par niveau
- `per_page` (optional) - Nombre d'éléments par page (défaut: 15)

**Response:**
```json
{
  "status": "success",
  "message": "Liste des rôles récupérée",
  "data": {
    "roles": {
      "data": [
        {
          "id": 1,
          "name": "super-admin",
          "guard_name": "web",
          "users_count": 1,
          "created_at": "2025-02-16T12:00:00Z"
        }
      ],
      "current_page": 1,
      "total": 6
    }
  }
}
```

#### Créer un rôle
```http
POST /api/roles
```

**Request Body:**
```json
{
  "name": "nouveau-role",
  "description": "Description du rôle",
  "level": 3,
  "permissions": ["users.view.department", "plannings.view.department"]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Rôle créé avec succès",
  "data": {
    "role": {
      "id": 7,
      "name": "nouveau-role",
      "permissions": [...]
    }
  }
}
```

#### Voir un rôle
```http
GET /api/roles/{id}
```

#### Modifier un rôle
```http
PUT /api/roles/{id}
```

#### Supprimer un rôle
```http
DELETE /api/roles/{id}
```

#### Obtenir les permissions d'un rôle
```http
GET /api/roles/{id}/permissions
```

#### Synchroniser les permissions d'un rôle
```http
POST /api/roles/{id}/permissions
```

**Request Body:**
```json
{
  "permissions": ["users.view.all", "plannings.view.all", "plannings.create.all"]
}
```

#### Obtenir les utilisateurs d'un rôle
```http
GET /api/roles/{id}/users
```

#### Assigner un rôle à des utilisateurs
```http
POST /api/roles/{id}/users
```

**Request Body:**
```json
{
  "user_ids": [1, 2, 3]
}
```

---

### 2. Permissions

#### Liste des permissions
```http
GET /api/permissions
```

**Query Parameters:**
- `search` (optional) - Recherche par nom
- `resource` (optional) - Filtrer par ressource (users, plannings, etc.)

#### Créer une permission
```http
POST /api/permissions
```

**Request Body:**
```json
{
  "name": "resource.action.scope",
  "resource": "resource",
  "action": "action",
  "scope": "scope"
}
```

#### Groupes de permissions par ressource
```http
GET /api/permissions/by-resource
```

**Response:**
```json
{
  "status": "success",
  "message": "Permissions groupées par ressource",
  "data": {
    "permissions_by_resource": {
      "users": [...],
      "plannings": [...],
      "grades": [...]
    }
  }
}
```

---

### 3. Gestion des rôles utilisateur

#### Obtenir les rôles d'un utilisateur
```http
GET /api/users/{id}/roles
```

#### Assigner des rôles à un utilisateur
```http
POST /api/users/{id}/roles
```

**Request Body:**
```json
{
  "roles": ["administrateur", "professeur"],
  "mode": "add"  // ou "sync" pour remplacer tous les rôles
}
```

#### Retirer des rôles d'un utilisateur
```http
DELETE /api/users/{id}/roles
```

**Request Body:**
```json
{
  "roles": ["professeur"]
}
```

#### Synchroniser les rôles (remplace tous)
```http
PUT /api/users/{id}/roles/sync
```

---

### 4. Gestion des permissions utilisateur

#### Obtenir les permissions directes
```http
GET /api/users/{id}/permissions
```

#### Obtenir toutes les permissions (héritées + directes)
```http
GET /api/users/{id}/all-permissions
```

#### Donner des permissions directes
```http
POST /api/users/{id}/permissions
```

**Request Body:**
```json
{
  "permissions": ["reports.generate", "export.pdf"]
}
```

#### Révoquer des permissions
```http
DELETE /api/users/{id}/permissions
```

#### Vérifier une permission
```http
POST /api/users/{id}/check-permission
```

**Request Body:**
```json
{
  "permission": "plannings.view.all"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Vérification de permission effectuée",
  "data": {
    "has_permission": true,
    "permission": "plannings.view.all"
  }
}
```

---

### 5. Départements

#### Liste des départements
```http
GET /api/departments
```

**Query Parameters:**
- `search` (optional) - Recherche par nom ou code
- `is_active` (optional) - Filtrer par statut actif

#### Créer un département
```http
POST /api/departments
```

**Request Body:**
```json
{
  "name": "Informatique",
  "code": "INFO",
  "description": "Département d'informatique",
  "color": "#3B82F6",
  "is_active": true
}
```

#### Obtenir les utilisateurs d'un département
```http
GET /api/departments/{id}/users
```

#### Obtenir les statistiques d'un département
```http
GET /api/departments/{id}/stats
```

---

## Codes d'erreur

| Code | Description |
|------|-------------|
| `401` | Non authentifié - session cookie absente ou expirée |
| `403` | Non autorisé - Rôle ou permission insuffisant, ou compte suspendu |
| `404` | Ressource non trouvée |
| `422` | Validation échouée |
| `400` | Requête invalide (ex: suppression impossible car rôle utilisé) |

---

## Exemples d'utilisation

> Authentification par session cookie : `curl -c cookies.txt -b cookies.txt` conserve le cookie de session, et chaque écriture doit renvoyer l'en-tête `X-XSRF-TOKEN` (valeur du cookie `XSRF-TOKEN` obtenu via `GET /api/csrf-cookie`). Voir `api-docs.md` pour le détail du flux.

### Assigner un rôle à un utilisateur

```bash
curl -X POST http://your-domain/api/users/5/roles \
  -b cookies.txt \
  -H "X-XSRF-TOKEN: {xsrf}" \
  -H "Content-Type: application/json" \
  -d '{
    "roles": ["professeur"],
    "mode": "add"
  }'
```

### Vérifier si un utilisateur a une permission

```bash
curl -X POST http://your-domain/api/users/5/check-permission \
  -b cookies.txt \
  -H "X-XSRF-TOKEN: {xsrf}" \
  -H "Content-Type: application/json" \
  -d '{
    "permission": "plannings.create.department"
  }'
```

### Créer un rôle avec permissions

```bash
curl -X POST http://your-domain/api/roles \
  -b cookies.txt \
  -H "X-XSRF-TOKEN: {xsrf}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "responsable-classe",
    "description": "Responsable de classe",
    "level": 4,
    "permissions": [
      "students.view.class",
      "grades.view.class",
      "grades.edit"
    ]
  }'
```

---

## Super Admin par défaut

**Email:** `superadmin@campustrack.com`
**Password:** `password`

Ce compte possède toutes les permissions et peut gérer tous les rôles.

---

## Bonnes pratiques

1. **Ne modifiez jamais le rôle `super-admin`** - Il est protégé
2. **Utilisez des permissions granulaires** - Préférez `plannings.view.department` à `plannings.view.all` quand possible
3. **Vérifiez la hiérarchie** - Un administrateur ne peut pas gérer un super-admin
4. **Nettoyez les rôles inutilisés** - Avant de supprimer un rôle, retirez-le de tous les utilisateurs
5. **Utilisez le cache** - Les permissions sont mises en cache automatiquement

---

## Départements par défaut

| Code | Nom | Couleur |
|------|-----|---------|
| INFO | Informatique | #3B82F6 (Bleu) |
| MATH | Mathématiques | #EF4444 (Rouge) |
| PHYS | Physique | #10B981 (Vert) |
| LANG | Langues | #F59E0B (Jaune) |
| ECO | Sciences Économiques | #8B5CF6 (Violet) |

---

**Dernière mise à jour:** 2025-02-16
**Version:** 1.0.0
