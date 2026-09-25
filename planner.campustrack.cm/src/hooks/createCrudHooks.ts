import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';
import type { PaginatedResponse } from '@/src/lib/types';

type CrudService<T, Params, CreateData, UpdateData> = {
  getAll: (params?: Params) => Promise<PaginatedResponse<T>>;
  getById?: (id: number) => Promise<T>;
  create: (data: CreateData) => Promise<T>;
  update: (id: number, data: UpdateData) => Promise<T>;
  delete: (id: number) => Promise<void>;
};

type CrudHooksConfig<T, Params, CreateData, UpdateData> = {
  /** Instance du service axios de l'entité */
  service: CrudService<T, Params, CreateData, UpdateData>;
  /** Clé de cache TanStack Query (liste) */
  queryKey: string;
  /** Clé de cache pour l'entité seule */
  singleKey: string;
  /** Libellé pour les toasts (ex. "Salle") */
  label: string;
  /** Invalider aussi ces clés après mutation (ex. ["planning"]) */
  alsoInvalidate?: string[];
};

/**
 * Factory des hooks CRUD TanStack Query.
 * Remplace ~6 fichiers quasi identiques (list / get / create / update / delete).
 */
export function createCrudHooks<T, Params, CreateData, UpdateData>(
  config: CrudHooksConfig<T, Params, CreateData, UpdateData>,
) {
  const { service, queryKey, singleKey, label, alsoInvalidate = [] } = config;

  const invalidateAll = (queryClient: ReturnType<typeof useQueryClient>) => {
    queryClient.invalidateQueries({ queryKey: [queryKey] });
    alsoInvalidate.forEach((key) => queryClient.invalidateQueries({ queryKey: [key] }));
  };

  const useList = (params?: Params) =>
    useQuery<PaginatedResponse<T>>({
      queryKey: [queryKey, params],
      queryFn: () => service.getAll(params),
    });

  const useGet = (id: number) =>
    useQuery<T>({
      queryKey: [singleKey, id],
      queryFn: () => service.getById!(id),
      enabled: !!id && !!service.getById,
    });

  const useCreate = () => {
    const queryClient = useQueryClient();
    return useMutation({
      mutationFn: (data: CreateData) => service.create(data),
      onSuccess: () => {
        invalidateAll(queryClient);
        toast.success(`${label} créé(e) avec succès`);
      },
      onError: (error) => {
        toast.error(getErrorMessage(error as unknown as AxiosError));
      },
    });
  };

  const useUpdate = () => {
    const queryClient = useQueryClient();
    return useMutation({
      mutationFn: ({ id, data }: { id: number; data: UpdateData }) => service.update(id, data),
      onSuccess: () => {
        invalidateAll(queryClient);
        toast.success(`${label} mis(e) à jour avec succès`);
      },
      onError: (error) => {
        toast.error(getErrorMessage(error as unknown as AxiosError));
      },
    });
  };

  const useDelete = () => {
    const queryClient = useQueryClient();
    return useMutation({
      mutationFn: (id: number) => service.delete(id),
      onSuccess: () => {
        invalidateAll(queryClient);
        toast.success(`${label} supprimé(e) avec succès`);
      },
      onError: (error) => {
        toast.error(getErrorMessage(error as unknown as AxiosError));
      },
    });
  };

  return { useList, useGet, useCreate, useUpdate, useDelete };
}
