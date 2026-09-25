import { apiClient } from '@/src/services/api';
import { Teacher, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const BASE = '/api/teachers';

export type TeacherParams = {
  page?: number;
  per_page?: number;
  department_id?: number;
  search?: string;
  is_active?: boolean;
};

export type CreateTeacherData = {
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  speciality: string;
  department_id: number;
  max_hours_per_week?: number;
  is_active?: boolean;
  hired_at?: string;
};

export type UpdateTeacherData = Partial<CreateTeacherData>;

export const teacherService = {
  getAll: async (params?: TeacherParams): Promise<PaginatedResponse<Teacher>> => {
    const res = await apiClient.get(BASE, { params });

    return res.data.data.teachers as PaginatedResponse<Teacher>;
  },

  getById: async (id: number): Promise<Teacher> => {
    const res = await apiClient.get<ApiResponse<Teacher>>(`${BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateTeacherData): Promise<Teacher> => {
    const res = await apiClient.post<ApiResponse<Teacher>>(BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateTeacherData): Promise<Teacher> => {
    const res = await apiClient.put<ApiResponse<Teacher>>(`${BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${BASE}/${id}`);
  },
};
