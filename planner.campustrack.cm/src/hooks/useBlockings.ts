import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  roomBlockingService,
  teacherBlockingService,
  RoomBlockingParams,
  CreateRoomBlockingData,
  UpdateRoomBlockingData,
  TeacherBlockingParams,
  CreateTeacherBlockingData,
  UpdateTeacherBlockingData,
} from '@/src/services/blockingService';
import { RoomBlocking, TeacherBlocking } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import { useTranslation } from '@/src/utils/i18n';
import type { AxiosError } from 'axios';

export function useRoomBlockings(params?: RoomBlockingParams) {
  return useQuery<RoomBlocking[]>({
    queryKey: ['roomBlockings', params],
    queryFn: () => roomBlockingService.getAll(params),
  });
}

export function useRoomBlocking(id: number) {
  return useQuery<RoomBlocking>({
    queryKey: ['roomBlocking', id],
    queryFn: () => roomBlockingService.getById(id),
    enabled: !!id,
  });
}

export function useCreateRoomBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (data: CreateRoomBlockingData) => roomBlockingService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['roomBlockings'] });
      toast.success(t('toast.roomBlockingCreated'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useUpdateRoomBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateRoomBlockingData }) =>
      roomBlockingService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['roomBlockings'] });
      toast.success(t('toast.roomBlockingUpdated'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeleteRoomBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (id: number) => roomBlockingService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['roomBlockings'] });
      toast.success(t('toast.roomBlockingDeleted'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useTeacherBlockings(params?: TeacherBlockingParams) {
  return useQuery<TeacherBlocking[]>({
    queryKey: ['teacherBlockings', params],
    queryFn: () => teacherBlockingService.getAll(params),
  });
}

export function useTeacherBlocking(id: number) {
  return useQuery<TeacherBlocking>({
    queryKey: ['teacherBlocking', id],
    queryFn: () => teacherBlockingService.getById(id),
    enabled: !!id,
  });
}

export function useCreateTeacherBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (data: CreateTeacherBlockingData) => teacherBlockingService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacherBlockings'] });
      toast.success(t('toast.teacherBlockingCreated'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useUpdateTeacherBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateTeacherBlockingData }) =>
      teacherBlockingService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacherBlockings'] });
      toast.success(t('toast.teacherBlockingUpdated'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeleteTeacherBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (id: number) => teacherBlockingService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacherBlockings'] });
      toast.success(t('toast.teacherBlockingDeleted'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useApproveTeacherBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (id: number) => teacherBlockingService.approve(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacherBlockings'] });
      toast.success(t('toast.teacherBlockingApproved'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useRejectTeacherBlocking() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason?: string }) =>
      teacherBlockingService.reject(id, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teacherBlockings'] });
      toast.success(t('toast.teacherBlockingRejected'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
