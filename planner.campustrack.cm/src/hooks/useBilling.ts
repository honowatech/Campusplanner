import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from '@/src/utils/i18n';
import { paymentService, CheckoutPayload } from '@/src/services/paymentService';
import { Payment, PaginatedResponse } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

export function usePayments() {
  return useQuery<PaginatedResponse<Payment>>({
    queryKey: ['payments'],
    queryFn: paymentService.list,
    staleTime: 30_000,
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
      } else if (payment.status === 'failed') {
        toast.error(payment.metadata?.error || t('toast.paymentInitFailed'));
      }
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

const AWAITING_STATUSES = ['pending', 'in_progress'];

export function isAwaitingPayment(payment?: Payment | null): boolean {
  return Boolean(payment && AWAITING_STATUSES.includes(payment.status));
}

/**
 * Suit un paiement jusqu'à sa conclusion : PayMe n'envoie aucune notification,
 * l'API revérifie la transaction à chaque appel (toutes les 4 s ici).
 */
export function usePaymentStatus(id: number | null) {
  return useQuery<Payment>({
    queryKey: ['payment', id],
    queryFn: () => paymentService.get(id as number),
    enabled: id !== null,
    refetchInterval: (query) => (isAwaitingPayment(query.state.data) || !query.state.data ? 4_000 : false),
    refetchIntervalInBackground: true,
  });
}

export function useCancelPayment() {
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  return useMutation({
    mutationFn: (id: number) => paymentService.cancel(id),
    onSuccess: (payment) => {
      queryClient.setQueryData(['payment', payment.id], payment);
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      if (payment.status !== 'paid') {
        toast.info(t('toast.paymentCancelled'));
      }
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
