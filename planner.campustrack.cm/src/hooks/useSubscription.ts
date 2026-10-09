import { useQuery } from '@tanstack/react-query';
import { subscriptionService } from '@/src/services/subscriptionService';
import { SubscriptionSummary } from '@/src/lib/types';

export function useSubscription() {
  return useQuery<SubscriptionSummary | null>({
    queryKey: ['subscription'],
    queryFn: subscriptionService.getCurrent,
    staleTime: 60_000,
  });
}

export function usePacks() {
  return useQuery({
    queryKey: ['packs'],
    queryFn: subscriptionService.listPacks,
    staleTime: 60_000,
  });
}
