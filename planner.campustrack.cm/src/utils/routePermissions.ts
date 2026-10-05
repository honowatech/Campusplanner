import type { User } from '@/src/lib/types';

/**
 * Garde d'accès côté client. Une page est visible si l'utilisateur possède
 * soit une permission de consultation (`*.view`, mode lecture seule), soit une
 * permission de gestion (`*.manage`/`*.create`/etc., mode CRUD). Les pages de
 * gestion purement administratives (users, departments, settings, demo-mode)
 * restent réservées aux administrateurs.
 * La vraie autorisation reste côté API (403) ; ce mapping améliore l'UX.
 */
type RoutePermissionRule = {
  match: (pathname: string) => boolean;
  permissions: string[];
};

export const routePermissions: RoutePermissionRule[] = [
  { match: (p) => p === '/users', permissions: ['users.create', 'users.edit', 'users.delete'] },
  { match: (p) => p === '/settings', permissions: ['settings.edit'] },
  { match: (p) => p === '/demo-mode', permissions: ['demo-mode.manage'] },
  {
    match: (p) => p === '/departments',
    permissions: ['departments.create', 'departments.edit', 'departments.delete'],
  },
  {
    match: (p) => p === '/teachers',
    permissions: ['teachers.view.all', 'teachers.view.department', 'teachers.manage.department'],
  },
  {
    match: (p) => p === '/students',
    permissions: [
      'students.view.all',
      'students.view.department',
      'students.view.class',
      'students.create',
      'students.edit',
      'students.delete',
    ],
  },
  { match: (p) => p === '/rooms', permissions: ['rooms.view', 'rooms.manage'] },
  { match: (p) => p === '/classes', permissions: ['classes.view', 'classes.manage'] },
  { match: (p) => p === '/courses', permissions: ['courses.view', 'courses.manage'] },
];

export function hasAnyPermission(
  userPermissions: string[] | undefined,
  required: string[],
): boolean {
  if (!userPermissions || userPermissions.length === 0) return false;
  return required.some((permission) => userPermissions.includes(permission));
}

export function canAccessRoute(user: User | null, pathname: string): boolean {
  const rule = routePermissions.find((r) => r.match(pathname));
  if (!rule) return true; // route non protégée
  return hasAnyPermission(user?.permissions, rule.permissions);
}

/**
 * Garde spécifique à l'aperçu du tableau de bord. Côté API, les routes
 * `/dashboard/*` sont réservées aux rôles `super-admin` et `administrateur`
 * (middleware `role:super-admin|administrateur`). On reflète ce périmètre côté
 * client pour masquer les composants d'aperçu lorsque l'utilisateur n'y est
 * pas autorisé.
 */
export function canViewDashboardOverview(user: User | null | undefined): boolean {
  return user?.role === 'super-admin' || user?.role === 'admin';
}
