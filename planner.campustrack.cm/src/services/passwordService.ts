import { apiClient } from './api';
import { ApiResponse } from '@/src/lib/types';

export type ResetPasswordPayload = {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
};

export const passwordService = {
  // Réponse identique que le compte existe ou non (aucune adresse révélée).
  forgot: async (email: string): Promise<string> => {
    const res = await apiClient.post<ApiResponse<null>>('/api/forgot-password', { email });
    return res.data.message;
  },

  reset: async (payload: ResetPasswordPayload): Promise<string> => {
    const res = await apiClient.post<ApiResponse<null>>('/api/reset-password', payload);
    return res.data.message;
  },
};
