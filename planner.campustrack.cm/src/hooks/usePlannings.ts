import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  planningService,
  PlanningParams,
  CreatePlanningData,
  UpdatePlanningData,
  GeneratePlanningData,
  DetectConflictsData,
} from '@/src/services/planningService';
import { Planning } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import { useTranslation } from '@/src/utils/i18n';
import type { AxiosError } from 'axios';

export function usePlannings(params?: PlanningParams) {
  return useQuery<Planning[]>({
    queryKey: ['plannings', params],
    queryFn: () => planningService.getAll(params),
  });
}

export function usePlanning(id: number) {
  return useQuery<Planning>({
    queryKey: ['planning', id],
    queryFn: () => planningService.getById(id),
    enabled: !!id,
  });
}

export function useCreatePlanning() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (data: CreatePlanningData) => planningService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      toast.success(t('toast.planningCreated'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useUpdatePlanning() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdatePlanningData }) =>
      planningService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      toast.success(t('toast.planningUpdated'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeletePlanning() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (id: number) => planningService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      toast.success(t('toast.planningDeleted'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useGeneratePlanning() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (data: GeneratePlanningData) => planningService.generate(data),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      queryClient.invalidateQueries({ queryKey: ['planning'] });
      toast.success(t('toast.shiftsGenerated', { count: data.total_generated }));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDetectConflicts() {
  return useMutation({
    mutationFn: (data: DetectConflictsData) => planningService.detectConflicts(data),
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useOptimizePlanning() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (planningId: number) => planningService.optimize(planningId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      queryClient.invalidateQueries({ queryKey: ['planning'] });
      toast.success(t('toast.timetableOptimized'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
