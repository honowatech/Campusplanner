import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from '@/src/utils/i18n';
import { paymentService, CheckoutPayload } from '@/src/services/paymentService';
import { Payment, PaginatedResponse } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

export const OPEN_PAYMENT_STATUSES = ['pending', 'in_progress'];

/**
 * PayMe n'envoie aucune notification : tant qu'un paiement est en cours, la
 * liste est rafraîchie toutes les 5 s (le planificateur côté API vérifie
 * chaque minute auprès de PayMe).
 */
export function usePayments() {
  return useQuery<PaginatedResponse<Payment>>({
    queryKey: ['payments'],
    queryFn: paymentService.list,
    staleTime: 30_000,
    refetchInterval: (query) => {
      const data = query.state.data;
      const hasOpen = data?.data.some((p) => OPEN_PAYMENT_STATUSES.includes(p.status));
      return hasOpen ? 5_000 : false;
    },
  });
}

export function useCancelPayment() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (paymentId: number) => paymentService.cancel(paymentId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      toast.success(t('toast.paymentCancelled'));
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useCheckout() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: ({ payload, idempotencyKey }: { payload: CheckoutPayload; idempotencyKey?: string }) =>
      paymentService.checkout(payload, idempotencyKey),
    onSuccess: (payment) => {
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });

      if (payment.status === 'in_progress') {
        toast.success(t('toast.paymentInitiated'));
      } else {
        const reason = payment.metadata?.error;
        toast.error(reason ? `${t('toast.paymentInitFailed')} ${reason}` : t('toast.paymentInitFailed'));
      }
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
