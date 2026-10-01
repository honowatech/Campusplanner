import { apiClient } from '@/src/services/api';
import { Student, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const BASE = '/api/students';

export type StudentParams = {
  page?: number;
  per_page?: number;
  class_id?: number;
  department_id?: number;
  search?: string;
  is_active?: boolean;
};

export type CreateStudentData = {
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  date_of_birth?: string;
  gender?: 'male' | 'female' | 'other';
  address?: string;
  parent_name?: string;
  parent_phone?: string;
  matricule: string;
  admission_date?: string;
  course_class_id: number;
  is_active?: boolean;
};

export type UpdateStudentData = Partial<CreateStudentData>;

export const studentService = {
  getAll: async (params?: StudentParams): Promise<PaginatedResponse<Student>> => {
    const res = await apiClient.get(BASE, { params });
    return res.data.data.students as PaginatedResponse<Student>;
  },

  getById: async (id: number): Promise<Student> => {
    const res = await apiClient.get<ApiResponse<Student>>(`${BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateStudentData): Promise<Student> => {
    const res = await apiClient.post<ApiResponse<Student>>(BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateStudentData): Promise<Student> => {
    const res = await apiClient.put<ApiResponse<Student>>(`${BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${BASE}/${id}`);
  },

  getSchedule: async (id: number) => {
    const res = await apiClient.get<
      ApiResponse<{
        sessions: {
          id: number;
          course: { id: number; name: string; code: string };
          teacher: { id: number; name: string };
          room: { id: number; name: string };
          date: string;
          starting_hour: string;
          ending_hour: string;
        }[];
      }>
    >(`${BASE}/${id}/schedule`);
    return res.data.data;
  },

  moveToClass: async (id: number, classId: number): Promise<Student> => {
    const res = await apiClient.put<ApiResponse<Student>>(`${BASE}/${id}/move-to-class`, {
      class_id: classId,
    });
    return res.data.data;
  },
};
