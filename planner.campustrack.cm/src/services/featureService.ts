import { apiClient } from './api';
import { FeaturesPayload, ApiResponse } from '@/src/lib/types';

export const featureService = {
  list: async (): Promise<FeaturesPayload> => {
    const res = await apiClient.get<ApiResponse<FeaturesPayload>>('/api/features');
    return res.data.data;
  },
};
