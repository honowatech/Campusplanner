import { apiClient } from '@/src/services/api';
import { AppSettings, ApiResponse } from '@/src/lib/types';

const BASE = '/api/settings';

export type UpdateSettingsData = Partial<AppSettings>;

export const settingsService = {
  get: async (): Promise<AppSettings> => {
    const res = await apiClient.get<ApiResponse<AppSettings>>(BASE);
    return res.data.data;
  },

  update: async (data: UpdateSettingsData): Promise<AppSettings> => {
    const res = await apiClient.put<ApiResponse<AppSettings>>(BASE, data);
    return res.data.data;
  },
};
