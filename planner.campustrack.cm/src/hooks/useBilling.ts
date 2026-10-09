import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
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

  return useMutation({
    mutationFn: ({ payload, idempotencyKey }: { payload: CheckoutPayload; idempotencyKey?: string }) =>
      paymentService.checkout(payload, idempotencyKey),
    onSuccess: (payment) => {
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });

      if (payment.status === 'in_progress') {
        toast.success('Paiement initié. Validez la transaction sur votre téléphone.');
      } else {
        toast.error("Le paiement n'a pas pu être initié.");
      }
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
