import type { User } from '@/src/lib/types';

/**
 * Gating de fonctionnalités côté client (miroir du FeatureRegistry backend).
 *
 * Les fonctionnalités actives du tenant sont fournies par `/auth-user`
 * (`user.features`). Le super-admin (global) a accès à tout.
 */

const PATH_FEATURE_MAP: Record<string, string> = {
  '/': 'dashboard',
  '/departments': 'departments',
  '/classes': 'classes',
  '/teachers': 'teachers',
  '/students': 'students',
  '/courses': 'courses',
  '/rooms': 'rooms',
  '/timetables': 'scheduling',
  '/sms': 'sms',
};

/**
 * Fonctionnalité associée à un chemin de page (si applicable).
 */
export function featureForPath(pathname: string): string | undefined {
  if (pathname === '/') return 'dashboard';
  return PATH_FEATURE_MAP[pathname];
}

/**
 * Indique si l'utilisateur (ou son tenant) dispose de la fonctionnalité.
 */
export function hasFeature(user: User | null | undefined, feature: string): boolean {
  if (!user) return false;
  if (user.role === 'super-admin') return true;
  return (user.features ?? []).includes(feature);
}
