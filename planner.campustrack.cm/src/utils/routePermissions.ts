import type { User } from '@/src/lib/types';

/**
 * Garde d'accès côté client, miroir des permissions `viewAny` des policies API.
 * La vraie autorisation reste côté API (403) ; ce mapping améliore l'UX en
 * évitant de naviguer vers une page inaccessible.
 */
type RoutePermissionRule = {
  match: (pathname: string) => boolean;
  permissions: string[];
};

export const routePermissions: RoutePermissionRule[] = [
  { match: (p) => p === '/users', permissions: ['users.view.all', 'users.view.department'] },
  { match: (p) => p === '/settings', permissions: ['settings.view'] },
  { match: (p) => p === '/demo-mode', permissions: ['demo-mode.manage'] },
  { match: (p) => p === '/departments', permissions: ['departments.view'] },
  {
    match: (p) => p === '/teachers',
    permissions: ['teachers.view.all', 'teachers.view.department'],
  },
  {
    match: (p) => p === '/students',
    permissions: ['students.view.all', 'students.view.department', 'students.view.class'],
  },
  { match: (p) => p === '/rooms', permissions: ['rooms.view'] },
  { match: (p) => p === '/classes', permissions: ['classes.view'] },
  { match: (p) => p === '/courses', permissions: ['courses.view'] },
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
