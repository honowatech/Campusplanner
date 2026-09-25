import { apiClient } from '@/src/services/api';
import { Room, ApiResponse, PaginatedResponse, RoomType } from '@/src/lib/types';

const BASE = '/api/rooms';

export type RoomParams = {
  page?: number;
  per_page?: number;
  department_id?: number;
  type?: RoomType;
  is_active?: boolean;
  search?: string;
};

export type CreateRoomData = {
  name: string;
  code: string;
  type: RoomType;
  capacity: number;
  department_id?: number;
  floor?: number;
  building?: string;
  has_projector?: boolean;
  has_computers?: boolean;
  has_whiteboard?: boolean;
  is_active?: boolean;
  description?: string;
};

export type UpdateRoomData = Partial<CreateRoomData>;

export const roomService = {
  getAll: async (params?: RoomParams): Promise<PaginatedResponse<Room>> => {
    const res = await apiClient.get(BASE, { params });
    return res.data.data.rooms as PaginatedResponse<Room>;
  },

  getById: async (id: number): Promise<Room> => {
    const res = await apiClient.get<ApiResponse<Room>>(`${BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateRoomData): Promise<Room> => {
    const res = await apiClient.post<ApiResponse<Room>>(BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateRoomData): Promise<Room> => {
    const res = await apiClient.put<ApiResponse<Room>>(`${BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${BASE}/${id}`);
  },
};
