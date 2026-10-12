import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from '@/src/utils/i18n';
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
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (enabled: boolean) => demoModeService.toggle(enabled),
    onSuccess: (data) => {
      queryClient.setQueryData(['demo-mode'], data);
      toast.success(data.enabled ? t('toast.demoEnabled') : t('toast.demoDisabled'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
