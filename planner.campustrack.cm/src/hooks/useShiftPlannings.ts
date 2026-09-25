import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  planningService,
  ShiftPlanningParams,
  CreateShiftPlanningData,
  UpdateShiftPlanningData,
} from '@/src/services/planningService';
import { ShiftPlanning, PaginatedResponse } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

export function useShiftPlannings(params?: ShiftPlanningParams) {
  return useQuery<PaginatedResponse<ShiftPlanning>>({
    queryKey: ['shiftPlannings', params],
    queryFn: () => planningService.getAllShifts(params),
  });
}

export function useShiftPlanning(id: number) {
  return useQuery<ShiftPlanning>({
    queryKey: ['shiftPlanning', id],
    queryFn: () => planningService.getShiftById(id),
    enabled: !!id,
  });
}

export function useCreateShiftPlanning() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateShiftPlanningData) => planningService.createShift(data),
    onSuccess: (response) => {
      const created = response as ShiftPlanning & {
        shift_planning?: ShiftPlanning;
      };
      queryClient.invalidateQueries({ queryKey: ['shiftPlannings'] });
      queryClient.invalidateQueries({
        queryKey: ['planning', created.shift_planning?.planning_id ?? created.planning_id],
      });
      toast.success('Shift created successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useUpdateShiftPlanning() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateShiftPlanningData }) =>
      planningService.updateShift(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shiftPlannings'] });
      toast.success('Shift updated successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeleteShiftPlanning() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => planningService.deleteShift(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shiftPlannings'] });
      queryClient.invalidateQueries({
        queryKey: ['planning'],
      });
      toast.success('Shift deleted successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
