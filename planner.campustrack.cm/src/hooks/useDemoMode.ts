import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { demoModeService } from '@/src/services/demoModeService';
import { DemoModeStatus } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

export function useDemoMode() {
  return useQuery<DemoModeStatus>({
    queryKey: ['demo-mode'],
    queryFn: demoModeService.get,
    staleTime: 60_000,
  });
}

export function useToggleDemoMode() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (enabled: boolean) => demoModeService.toggle(enabled),
    onSuccess: (data) => {
      queryClient.setQueryData(['demo-mode'], data);
      toast.success(data.enabled ? 'Mode démo activé' : 'Mode démo désactivé');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
