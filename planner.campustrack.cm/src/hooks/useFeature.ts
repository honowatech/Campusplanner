import { useAuth } from '@/src/auth';
import { hasFeature } from '@/src/utils/featureGating';

/**
 * Hook de gating : vrai si le tenant courant (ou le super-admin) dispose de la
 * fonctionnalité. S'appuie sur `user.features` (fourni par `/auth-user`).
 */
export function useFeature(feature: string): boolean {
  const { user } = useAuth();
  return hasFeature(user, feature);
}
