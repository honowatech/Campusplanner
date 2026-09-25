import { apiClient } from '@/src/services/api';
import { Course, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const BASE = '/api/courses';

export type CourseParams = {
  page?: number;
  per_page?: number;
  department_id?: number;
  teacher_id?: number;
  is_active?: boolean;
  search?: string;
};

export type CreateCourseData = {
  name: string;
  code: string;
  description?: string;
  department_id?: number;
  coefficient?: number;
  hours_per_week?: number;
  teacher_id?: number;
  class_id?: number;
  is_active?: boolean;
};

export type UpdateCourseData = Partial<CreateCourseData>;

export const courseService = {
  getAll: async (params?: CourseParams): Promise<PaginatedResponse<Course>> => {
    const res = await apiClient(BASE, { params });
    return res.data.data.courses as PaginatedResponse<Course>;
  },

  getById: async (id: number): Promise<Course> => {
    const res = await apiClient.get<ApiResponse<Course>>(`${BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateCourseData): Promise<Course> => {
    const res = await apiClient.post<ApiResponse<Course>>(BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateCourseData): Promise<Course> => {
    const res = await apiClient.put<ApiResponse<Course>>(`${BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${BASE}/${id}`);
  },
};
