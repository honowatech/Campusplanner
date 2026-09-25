import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  studentService,
  StudentParams,
  CreateStudentData,
  UpdateStudentData,
} from '@/src/services/studentService';
import { PaginatedResponse, Student } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

export function useStudents(params?: StudentParams) {
  return useQuery<PaginatedResponse<Student>>({
    queryKey: ['students', params],
    queryFn: () => studentService.getAll(params),
  });
}

export function useStudent(id: number) {
  return useQuery<Student>({
    queryKey: ['student', id],
    queryFn: () => studentService.getById(id),
    enabled: !!id,
  });
}

export function useCreateStudent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: CreateStudentData) => studentService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] });
      toast.success('Student created successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useUpdateStudent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateStudentData }) =>
      studentService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] });
      toast.success('Student updated successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useDeleteStudent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (id: number) => studentService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] });
      toast.success('Student deleted successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}

export function useMoveStudent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ id, classId }: { id: number; classId: number }) =>
      studentService.moveToClass(id, classId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['students'] });
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
