import type { User, UserRole } from '@/src/lib/types';

/**
 * Garde d'accès côté client. La vraie autorisation reste côté API (403) ;
 * ce mapping améliore l'UX (masquage du menu + redirection).
 *
 * Règles d'accès par rôle (pages) :
 * - Student : UE (courses), timetables, profile (+ dashboard).
 * - Teacher : students, UE, timetables, profile (+ dashboard).
 * - Admin   : classes, teachers, students, UE, rooms, timetables, profile,
 *             + gestion tenant (users, settings, departments, billing, sms).
 * - HOD     : classes, teachers, students, UE, rooms, timetables, profile (+ dashboard).
 * - super-admin (global) : accès total.
 * - Les pages admin/système (users, settings, departments, billing, sms) sont
 *   réservées à l'admin de tenant et au super-admin (périmètre tenant).
 *   L'Admin Console et le mode démo restent exclusifs au super-admin.
 *
 * `personnel-administratif` n'est pas dans le cahier des charges ci-dessus :
 * il conserve son accès en lecture seule (départements inclus).
 */

/**
 * Pages accessibles par rôle. Le super-admin est traité à part (accès total).
 */
const ROLE_PAGES: Record<UserRole, string[]> = {
  'super-admin': [],
  etudiant: ['/', '/courses', '/timetables', '/plannings/*', '/profile'],
  professeur: ['/', '/students', '/courses', '/timetables', '/plannings/*', '/profile'],
  administrateur: [
    '/',
    '/classes',
    '/teachers',
    '/students',
    '/courses',
    '/rooms',
    '/timetables',
    '/plannings/*',
    '/profile',
    '/users',
    '/settings',
    '/departments',
    '/billing',
    '/sms',
  ],
  'responsable-departement': [
    '/',
    '/classes',
    '/teachers',
    '/students',
    '/courses',
    '/rooms',
    '/timetables',
    '/plannings/*',
    '/profile',
  ],
  'personnel-administratif': [
    '/',
    '/departments',
    '/classes',
    '/teachers',
    '/students',
    '/courses',
    '/rooms',
    '/timetables',
    '/plannings/*',
    '/profile',
  ],
};

export function hasAnyPermission(
  userPermissions: string[] | undefined,
  required: string[],
): boolean {
  if (!userPermissions || userPermissions.length === 0) return false;
  return required.some((permission) => userPermissions.includes(permission));
}

/**
 * Indique si un chemin est couvert par une entrée de `ROLE_PAGES`.
 *
 * Deux formes sont acceptées :
 * - `/classes`      : correspondance exacte ;
 * - `/plannings/*`  : préfixe (toute route enfant, ex. `/plannings/42`).
 */
function matchesRoute(pattern: string, pathname: string): boolean {
  if (pattern.endsWith('/*')) {
    const prefix = pattern.slice(0, -1); // '/plannings/'
    return pathname.startsWith(prefix);
  }

  return pathname === pattern;
}

export function canAccessRoute(user: User | null, pathname: string): boolean {
  if (!user) return false;

  const role = user.role;
  if (!role) return false;

  // Le super-admin (global) accède à toutes les pages.
  if (role === 'super-admin') return true;

  // Pages explicitement accessibles au rôle de l'utilisateur (correspondance
  // exacte ou par préfixe pour les routes dynamiques comme `/plannings/$id`).
  const allowed = ROLE_PAGES[role];
  if (!allowed.some((pattern) => matchesRoute(pattern, pathname))) return false;

  // Garde de fonctionnalité (miroir du middleware `feature:*` côté API).
  // const feature = featureForPath(pathname);
  // return !feature || hasFeature(user, feature);

  return true;
}

/**
 * Garde spécifique à l'aperçu du tableau de bord. Côté API, les routes
 * `/dashboard/*` sont réservées aux rôles `super-admin` et `administrateur`
 * (middleware `role:super-admin|administrateur`). On reflète ce périmètre côté
 * client pour masquer les composants d'aperçu lorsque l'utilisateur n'y est
 * pas autorisé.
 */
export function canViewDashboardOverview(user: User | null | undefined): boolean {
  return user?.role === 'super-admin' || user?.role === 'administrateur';
}
