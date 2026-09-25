import { apiClient } from '@/src/services/api';
import { CourseClass, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const BASE = '/api/classes';

export type ClassParams = {
  page?: number;
  per_page?: number;
  department_id?: number;
  level?: string;
  is_active?: boolean;
  search?: string;
};

export type CreateClassData = {
  name: string;
  code: string;
  department_id: number;
  level: string;
  capacity: number;
  room_id?: number;
  academic_year?: string;
  is_active?: boolean;
};

export type UpdateClassData = Partial<CreateClassData>;

export const classService = {
  getAll: async (params?: ClassParams): Promise<PaginatedResponse<CourseClass>> => {
    const res = await apiClient.get(BASE, { params });
    return res.data.data.classes as PaginatedResponse<CourseClass>;
  },

  getById: async (id: number): Promise<CourseClass> => {
    const res = await apiClient.get<ApiResponse<CourseClass>>(`${BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateClassData): Promise<CourseClass> => {
    const res = await apiClient.post<ApiResponse<CourseClass>>(BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateClassData): Promise<CourseClass> => {
    const res = await apiClient.put<ApiResponse<CourseClass>>(`${BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${BASE}/${id}`);
  },

  getStudents: async (id: number) => {
    const res = await apiClient.get<ApiResponse<{ students: unknown[] }>>(`${BASE}/${id}/students`);
    return res.data.data.students;
  },
};
