import { apiClient } from '@/src/services/api';
import { User, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const BASE = '/api/users';

export type UserParams = {
  page?: number;
  per_page?: number;
  department_id?: number;
  role?: string;
  search?: string;
};

export type RegisterUserData = {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  requested_role: string;
  department_id?: number;
};

export type CreateUserData = {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  department_id?: number;
  roles?: string[];
};

export type UpdateUserData = Partial<Omit<CreateUserData, 'password' | 'password_confirmation'>> & {
  password?: string;
  password_confirmation?: string;
};

export const userService = {
  register: async (data: RegisterUserData): Promise<User> => {
    const res = await apiClient.post<ApiResponse<User>>('/api/register', data);
    return res.data.data;
  },

  getPending: async (params?: UserParams): Promise<PaginatedResponse<User>> => {
    const res = await apiClient.get(`${BASE}/pending`, { params });
    return res.data.data.users as PaginatedResponse<User>;
  },

  approve: async (id: number, role?: string): Promise<User> => {
    const res = await apiClient.post<ApiResponse<User>>(
      `${BASE}/${id}/approve`,
      role ? { role } : {},
    );
    return res.data.data;
  },

  reject: async (id: number): Promise<void> => {
    await apiClient.post(`${BASE}/${id}/reject`);
  },

  deactivate: async (id: number): Promise<User> => {
    const res = await apiClient.post<ApiResponse<User>>(`${BASE}/${id}/deactivate`);
    return res.data.data;
  },

  getAll: async (params?: UserParams): Promise<PaginatedResponse<User>> => {
    const res = await apiClient.get(BASE, { params });
    return res.data.data.users as PaginatedResponse<User>;
  },

  getById: async (id: number): Promise<User> => {
    const res = await apiClient.get<ApiResponse<User>>(`${BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateUserData): Promise<User> => {
    const res = await apiClient.post<ApiResponse<User>>(BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateUserData): Promise<User> => {
    const res = await apiClient.put<ApiResponse<User>>(`${BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${BASE}/${id}`);
  },

  getByDepartment: async (departmentId: number): Promise<User[]> => {
    const res = await apiClient.get<PaginatedResponse<User>>(`${BASE}`, {
      params: { department_id: departmentId },
    });
    return res.data.data;
  },
};
