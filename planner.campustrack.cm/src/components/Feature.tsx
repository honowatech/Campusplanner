import { ReactNode } from 'react';
import { useFeature } from '@/src/hooks/useFeature';

interface FeatureProps {
  /** Clé de la fonctionnalité (ex. `sms`, `ai-scheduling`). */
  feature: string;
  children: ReactNode;
  /** Contenu affiché lorsque la fonctionnalité est indisponible. */
  fallback?: ReactNode;
}

/**
 * Rendu conditionnel par fonctionnalité : affiche `children` si la
 * fonctionnalité est active pour le tenant, sinon `fallback`.
 *
 * La vraie autorisation reste côté API (middleware `feature:*` → 403) ;
 * ce composant améliore l'UX en masquant les accès non inclus dans le pack.
 */
export function Feature({ feature, children, fallback = null }: FeatureProps) {
  const enabled = useFeature(feature);
  return <>{enabled ? children : fallback}</>;
}
