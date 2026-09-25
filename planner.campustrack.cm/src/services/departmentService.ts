import { apiClient } from '@/src/services/api';
import { Department, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const BASE = '/api/departments';

export type CreateDepartmentData = {
  name: string;
  code: string;
  description?: string;
  color?: string;
  is_active?: boolean;
};

export type UpdateDepartmentData = Partial<CreateDepartmentData>;

export type DepartmentParams = {
  page?: number;
  per_page?: number;
  class_id?: number;
  teacher_id?: number;
  is_active?: boolean;
  search?: string;
};

export type DepartmentUser = {
  id: number;
  name: string;
  email: string;
  roles?: string[];
};

export type DepartmentStats = {
  teachers: number;
  students: number;
  courses: number;
  classes: number;
  rooms: number;
  room_utilization: number;
  avg_teacher_hours: number;
};

export const departmentService = {
  getAll: async (params?: DepartmentParams): Promise<PaginatedResponse<Department>> => {
    const res = await apiClient.get(BASE, {
      params,
    });

    return res.data.data.departments as PaginatedResponse<Department>;
  },

  getById: async (id: number): Promise<Department> => {
    const res = await apiClient.get<ApiResponse<Department>>(`${BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateDepartmentData): Promise<Department> => {
    const res = await apiClient.post<ApiResponse<Department>>(BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateDepartmentData): Promise<Department> => {
    const res = await apiClient.put<ApiResponse<Department>>(`${BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${BASE}/${id}`);
  },

  getUsers: async (id: number): Promise<DepartmentUser[]> => {
    const res = await apiClient.get<ApiResponse<DepartmentUser[]>>(`${BASE}/${id}/users`);
    return res.data.data;
  },

  getStats: async (id: number, period?: '24h' | '7d' | '30d'): Promise<DepartmentStats> => {
    const res = await apiClient.get<ApiResponse<DepartmentStats>>(`${BASE}/${id}/stats`, {
      params: { period },
    });
    return res.data.data;
  },
};
