import { apiClient } from './api';
import { ApiResponse } from '@/src/lib/types';

export type SchoolSignupPayload = {
  name: string;
  admin_name: string;
  admin_email: string;
  admin_password: string;
  admin_password_confirmation: string;
  phone?: string;
  pack_id?: number;
};

export type SchoolSignupResult = {
  // Utilisateur authentifié (même forme que /api/auth-user).
  user: Record<string, unknown>;
  intended_pack_id: number | null;
};

export const onboardingService = {
  // Inscription libre-service : crée l'école et ouvre la session de l'admin.
  signup: async (payload: SchoolSignupPayload): Promise<SchoolSignupResult> => {
    const res = await apiClient.post<ApiResponse<SchoolSignupResult>>('/api/onboarding', payload);
    return res.data.data;
  },
};
