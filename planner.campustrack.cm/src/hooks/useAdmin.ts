import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  adminService,
  CreateTenantPayload,
  PackPayload,
  GatewayPayload,
} from '@/src/services/adminService';
import { Tenant, AdminPack, AdminFeature, PaymentGatewayConfig, Payment, PaginatedResponse } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

const ADMIN_QUERY_KEYS = {
  tenants: ['administrateur', 'tenants'],
  packs: ['administrateur', 'packs'],
  features: ['administrateur', 'features'],
  gateway: ['administrateur', 'payment-gateway'],
  payments: ['administrateur', 'payments'],
};

export function useTenants() {
  return useQuery<PaginatedResponse<Tenant>>({
    queryKey: ADMIN_QUERY_KEYS.tenants,
    queryFn: adminService.listTenants,
  });
}

export function useCreateTenant() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateTenantPayload) => adminService.createTenant(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ADMIN_QUERY_KEYS.tenants });
      toast.success('Tenant créé.');
    },
    onError: (error) => toast.error(getErrorMessage(error as unknown as AxiosError)),
  });
}

export function useSuspendTenant() {
  return useTenantStatusMutation('suspendTenant', 'Tenant suspendu.');
}

export function useReactivateTenant() {
  return useTenantStatusMutation('reactivateTenant', 'Tenant réactivé.');
}

function useTenantStatusMutation(
  method: 'suspendTenant' | 'reactivateTenant',
  successMessage: string,
) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => adminService[method](id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ADMIN_QUERY_KEYS.tenants });
      toast.success(successMessage);
    },
    onError: (error) => toast.error(getErrorMessage(error as unknown as AxiosError)),
  });
}

export function useAdminPacks() {
  return useQuery<AdminPack[]>({
    queryKey: ADMIN_QUERY_KEYS.packs,
    queryFn: adminService.listPacks,
  });
}

export function useSavePack() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id?: number; data: PackPayload }) =>
      id ? adminService.updatePack(id, data) : adminService.createPack(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ADMIN_QUERY_KEYS.packs });
      toast.success('Pack enregistré.');
    },
    onError: (error) => toast.error(getErrorMessage(error as unknown as AxiosError)),
  });
}

export function useDeletePack() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => adminService.deletePack(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ADMIN_QUERY_KEYS.packs });
      toast.success('Pack supprimé.');
    },
    onError: (error) => toast.error(getErrorMessage(error as unknown as AxiosError)),
  });
}

export function useAdminFeatures() {
  return useQuery<AdminFeature[]>({
    queryKey: ADMIN_QUERY_KEYS.features,
    queryFn: adminService.listFeatures,
  });
}

export function useUpdateFeature() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: { label?: string; is_active?: boolean } }) =>
      adminService.updateFeature(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ADMIN_QUERY_KEYS.features });
      queryClient.invalidateQueries({ queryKey: ['features'] });
    },
    onError: (error) => toast.error(getErrorMessage(error as unknown as AxiosError)),
  });
}

export function usePaymentGateway() {
  return useQuery<PaymentGatewayConfig | null>({
    queryKey: ADMIN_QUERY_KEYS.gateway,
    queryFn: adminService.getGateway,
  });
}

export function useUpdatePaymentGateway() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: GatewayPayload) => adminService.updateGateway(data),
    onSuccess: (gateway) => {
      queryClient.setQueryData(ADMIN_QUERY_KEYS.gateway, gateway);
      toast.success('Passerelle PayMe configurée.');
    },
    onError: (error) => toast.error(getErrorMessage(error as unknown as AxiosError)),
  });
}

export function useAdminPayments() {
  return useQuery<PaginatedResponse<Payment>>({
    queryKey: ADMIN_QUERY_KEYS.payments,
    queryFn: adminService.listPayments,
  });
}
