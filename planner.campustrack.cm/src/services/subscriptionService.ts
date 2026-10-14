import { apiClient } from './api';
import { Pack, SubscriptionSummary, ApiResponse } from '@/src/lib/types';

export const subscriptionService = {
  getCurrent: async (): Promise<SubscriptionSummary | null> => {
    const res = await apiClient.get<ApiResponse<{ subscription: SubscriptionSummary | null }>>(
      '/api/billing/subscription',
    );
    return res.data.data.subscription;
  },

  listPacks: async (): Promise<Pack[]> => {
    const res = await apiClient.get<ApiResponse<{ packs: Pack[] }>>('/api/packs');
    return res.data.data.packs;
  },
};
