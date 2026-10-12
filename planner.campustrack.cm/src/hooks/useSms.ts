import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from '@/src/utils/i18n';
import { smsService, SmsSettingsData } from '@/src/services/smsService';
import { SmsCredential, SmsLog, PaginatedResponse } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

export function useSmsSettings() {
  return useQuery<SmsCredential | null>({
    queryKey: ['sms-settings'],
    queryFn: smsService.getSettings,
    staleTime: 60_000,
  });
}

export function useUpdateSmsSettings() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (data: SmsSettingsData) => smsService.updateSettings(data),
    onSuccess: (credential) => {
      queryClient.setQueryData(['sms-settings'], credential);
      toast.success(t('toast.smsConfigSaved'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useSendSms() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: ({ to, message }: { to: string; message: string }) => smsService.send(to, message),
    onSuccess: (log) => {
      queryClient.invalidateQueries({ queryKey: ['sms-logs'] });
      toast.success(log.status === 'sent' ? t('toast.smsSent') : t('toast.smsSendFailed'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useTestSms() {
  const { t } = useTranslation();

  return useMutation({
    mutationFn: ({ to, message }: { to: string; message?: string }) => smsService.test(to, message),
    onSuccess: () => toast.success(t('toast.smsTestSent')),
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useSmsLogs() {
  return useQuery<PaginatedResponse<SmsLog>>({
    queryKey: ['sms-logs'],
    queryFn: smsService.listLogs,
    staleTime: 30_000,
  });
}
