import { apiClient } from './api';
import { SmsCredential, SmsLog, ApiResponse, PaginatedResponse } from '@/src/lib/types';

export type SmsSettingsData = {
  user?: string;
  password?: string;
  sender_id?: string;
  is_active?: boolean;
};

export const smsService = {
  getSettings: async (): Promise<SmsCredential | null> => {
    const res = await apiClient.get<ApiResponse<{ credential: SmsCredential | null }>>('/api/sms/settings');
    return res.data.data.credential;
  },

  updateSettings: async (data: SmsSettingsData): Promise<SmsCredential> => {
    const res = await apiClient.put<ApiResponse<{ credential: SmsCredential }>>('/api/sms/settings', data);
    return res.data.data.credential;
  },

  send: async (to: string, message: string, idempotencyKey?: string): Promise<SmsLog> => {
    const res = await apiClient.post<ApiResponse<{ log: SmsLog }>>(
      '/api/sms/send',
      { to, message },
      { headers: idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : undefined },
    );
    return res.data.data.log;
  },

  test: async (to: string, message?: string): Promise<SmsLog> => {
    const res = await apiClient.post<ApiResponse<{ log: SmsLog }>>('/api/sms/test', { to, message });
    return res.data.data.log;
  },

  listLogs: async (): Promise<PaginatedResponse<SmsLog>> => {
    const res = await apiClient.get<ApiResponse<{ logs: PaginatedResponse<SmsLog> }>>('/api/sms/logs');
    return res.data.data.logs;
  },
};
