import { apiClient } from './api';
import { Payment, ApiResponse, PaginatedResponse } from '@/src/lib/types';

export type CheckoutPayload = {
  pack_id: number;
  phone: string;
  billing_period?: string;
};

export const paymentService = {
  checkout: async (payload: CheckoutPayload, idempotencyKey?: string): Promise<Payment> => {
    const res = await apiClient.post<ApiResponse<{ payment: Payment }>>(
      '/api/billing/checkout',
      payload,
      {
        headers: idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : undefined,
      },
    );
    return res.data.data.payment;
  },

  // État vérifié en direct auprès de PayMe (aucune notification n'existe).
  get: async (id: number): Promise<Payment> => {
    const res = await apiClient.get<ApiResponse<{ payment: Payment }>>(
      `/api/billing/payments/${id}`,
    );
    return res.data.data.payment;
  },

  cancel: async (id: number): Promise<Payment> => {
    const res = await apiClient.post<ApiResponse<{ payment: Payment }>>(
      `/api/billing/payments/${id}/cancel`,
    );
    return res.data.data.payment;
  },

  list: async (): Promise<PaginatedResponse<Payment>> => {
    const res =
      await apiClient.get<ApiResponse<{ payments: PaginatedResponse<Payment> }>>(
        '/api/billing/payments',
      );
    return res.data.data.payments;
  },
};
