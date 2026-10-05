import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import {
  userService,
  UserParams,
  CreateUserData,
  UpdateUserData,
  RegisterUserData,
} from '@/src/services/userService';
import { PaginatedResponse, User } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

export function useUsers(params?: UserParams) {
  return useQuery<PaginatedResponse<User>>({
    queryKey: ['users', params],
    queryFn: () => userService.getAll(params),
    placeholderData: keepPreviousData,
  });
}

export function useUser(id: number) {
  return useQuery<User>({
    queryKey: ['user', id],
    queryFn: () => userService.getById(id),
    enabled: !!id,
  });
}

export function useCreateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateUserData) => userService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('Utilisateur créé avec succès');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useUpdateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateUserData }) =>
      userService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('Utilisateur mis à jour avec succès');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeleteUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => userService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('User deleted successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useRegister() {
  return useMutation({
    mutationFn: (data: RegisterUserData) => userService.register(data),
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function usePendingUsers(params?: UserParams) {
  return useQuery<PaginatedResponse<User>>({
    queryKey: ['users', 'pending', params],
    queryFn: () => userService.getPending(params),
  });
}

export function useApproveUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, role }: { id: number; role?: string }) => userService.approve(id, role),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('Compte validé avec succès');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useRejectUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => userService.reject(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('Demande de compte rejetée');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeactivateUser() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => userService.deactivate(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      toast.success('Compte suspendu : sessions révoquées');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
