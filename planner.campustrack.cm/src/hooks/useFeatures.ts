import { useQuery } from '@tanstack/react-query';
import { featureService } from '@/src/services/featureService';
import { FeaturesPayload } from '@/src/lib/types';

/**
 * Catalogue des fonctionnalités avec leur état (`enabled`) pour le tenant
 * courant. Source de vérité pour le composant <Feature> et les gardes d'UI.
 */
export function useFeatures() {
  return useQuery<FeaturesPayload>({
    queryKey: ['features'],
    queryFn: featureService.list,
    staleTime: 60_000,
  });
}
