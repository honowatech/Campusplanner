import { apiClient } from './api';
import {
  Tenant,
  AdminPack,
  AdminFeature,
  PaymentGatewayConfig,
  Payment,
  ApiResponse,
  PaginatedResponse,
} from '@/src/lib/types';

export type CreateTenantPayload = {
  name: string;
  slug: string;
  admin_name: string;
  admin_email: string;
  admin_password: string;
  trial_days?: number;
};

export type PackPayload = {
  name: string;
  slug: string;
  tier?: string;
  description?: string;
  price: number;
  currency?: string;
  billing_period: 'monthly' | 'annual';
  is_active?: boolean;
  sort?: number;
  features?: string[];
};

export type GatewayPayload = {
  user_name: string;
  password?: string;
  app_id?: string;
  endpoint?: string;
  pay_type_id?: string;
  client_fees_rate?: number;
  mode?: 'sandbox' | 'live';
  is_active?: boolean;
};

export const adminService = {
  // Tenants
  listTenants: async (): Promise<PaginatedResponse<Tenant>> => {
    const res = await apiClient.get<ApiResponse<{ tenants: PaginatedResponse<Tenant> }>>('/api/admin/tenants');
    return res.data.data.tenants;
  },

  createTenant: async (data: CreateTenantPayload): Promise<Tenant> => {
    const res = await apiClient.post<ApiResponse<{ tenant: Tenant }>>('/api/admin/tenants', data);
    return res.data.data.tenant;
  },

  suspendTenant: async (id: number): Promise<Tenant> => {
    const res = await apiClient.post<ApiResponse<{ tenant: Tenant }>>(`/api/admin/tenants/${id}/suspend`);
    return res.data.data.tenant;
  },

  reactivateTenant: async (id: number): Promise<Tenant> => {
    const res = await apiClient.post<ApiResponse<{ tenant: Tenant }>>(`/api/admin/tenants/${id}/reactivate`);
    return res.data.data.tenant;
  },

  // Packs
  listPacks: async (): Promise<AdminPack[]> => {
    const res = await apiClient.get<ApiResponse<{ packs: AdminPack[] }>>('/api/admin/packs');
    return res.data.data.packs;
  },

  createPack: async (data: PackPayload): Promise<AdminPack> => {
    const res = await apiClient.post<ApiResponse<{ pack: AdminPack }>>('/api/admin/packs', data);
    return res.data.data.pack;
  },

  updatePack: async (id: number, data: PackPayload): Promise<AdminPack> => {
    const res = await apiClient.put<ApiResponse<{ pack: AdminPack }>>(`/api/admin/packs/${id}`, data);
    return res.data.data.pack;
  },

  deletePack: async (id: number): Promise<void> => {
    await apiClient.delete(`/api/admin/packs/${id}`);
  },

  // Features
  listFeatures: async (): Promise<AdminFeature[]> => {
    const res = await apiClient.get<ApiResponse<{ features: AdminFeature[] }>>('/api/admin/features');
    return res.data.data.features;
  },

  updateFeature: async (id: number, data: { label?: string; is_active?: boolean }): Promise<AdminFeature> => {
    const res = await apiClient.put<ApiResponse<{ feature: AdminFeature }>>(`/api/admin/features/${id}`, data);
    return res.data.data.feature;
  },

  // Payment gateway
  getGateway: async (): Promise<PaymentGatewayConfig | null> => {
    const res = await apiClient.get<ApiResponse<{ gateway: PaymentGatewayConfig | null }>>(
      '/api/admin/payment-gateway',
    );
    return res.data.data.gateway;
  },

  updateGateway: async (data: GatewayPayload): Promise<PaymentGatewayConfig> => {
    const res = await apiClient.put<ApiResponse<{ gateway: PaymentGatewayConfig }>>('/api/admin/payment-gateway', data);
    return res.data.data.gateway;
  },

  // Payments (super-admin aggregate)
  listPayments: async (): Promise<PaginatedResponse<Payment>> => {
    const res = await apiClient.get<ApiResponse<{ payments: PaginatedResponse<Payment> }>>('/api/admin/payments');
    return res.data.data.payments;
  },

  // Impersonation
  impersonate: async (userId: number): Promise<unknown> => {
    const res = await apiClient.post('/api/admin/impersonate', { user_id: userId });
    return res.data;
  },

  stopImpersonation: async (): Promise<unknown> => {
    const res = await apiClient.post('/api/admin/impersonate/stop');
    return res.data;
  },
};
