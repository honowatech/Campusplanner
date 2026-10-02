import { apiClient } from '@/src/services/api';
import { DemoModeStatus, ApiResponse } from '@/src/lib/types';

const BASE = '/api/demo-mode';

export const demoModeService = {
  get: async (): Promise<DemoModeStatus> => {
    const res = await apiClient.get<ApiResponse<DemoModeStatus>>(BASE);
    return res.data.data;
  },

  toggle: async (enabled: boolean): Promise<DemoModeStatus> => {
    const res = await apiClient.put<ApiResponse<DemoModeStatus>>(BASE, { demo_mode: enabled });
    return res.data.data;
  },

  login: async (role: string): Promise<ApiResponse<unknown>> => {
    const res = await apiClient.post<ApiResponse<unknown>>('/api/demo-login', { role });
    return res.data;
  },
};
