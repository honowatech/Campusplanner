import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  planningService,
  PlanningParams,
  CreatePlanningData,
  UpdatePlanningData,
} from '@/src/services/planningService';
import { Planning } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
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

  return useMutation({
    mutationFn: (data: CreatePlanningData) => planningService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      toast.success('Planning created successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useUpdatePlanning() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdatePlanningData }) =>
      planningService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      toast.success('Planning updated successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeletePlanning() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => planningService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plannings'] });
      toast.success('Planning deleted successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
