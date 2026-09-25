import { apiClient } from '@/src/services/api';
import { RoomBlocking, TeacherBlocking, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const ROOM_BLOCKING_BASE = '/api/room-blockings';
const TEACHER_BLOCKING_BASE = '/api/teacher-blockings';

export type RoomBlockingParams = {
  page?: number;
  room_id?: number;
  blocking_type?: string;
};

export type CreateRoomBlockingData = {
  room_id: number;
  start_datetime: string;
  end_datetime: string;
  reason: string;
  blocking_type: 'maintenance' | 'event' | 'holiday' | 'other';
  is_recurring?: boolean;
};

export type UpdateRoomBlockingData = Partial<CreateRoomBlockingData>;

export const roomBlockingService = {
  getAll: async (params?: RoomBlockingParams): Promise<RoomBlocking[]> => {
    const res = await apiClient.get<PaginatedResponse<RoomBlocking>>(ROOM_BLOCKING_BASE, {
      params,
    });
    return res.data.data;
  },

  getById: async (id: number): Promise<RoomBlocking> => {
    const res = await apiClient.get<ApiResponse<RoomBlocking>>(`${ROOM_BLOCKING_BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateRoomBlockingData): Promise<RoomBlocking> => {
    const res = await apiClient.post<ApiResponse<RoomBlocking>>(ROOM_BLOCKING_BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateRoomBlockingData): Promise<RoomBlocking> => {
    const res = await apiClient.put<ApiResponse<RoomBlocking>>(`${ROOM_BLOCKING_BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${ROOM_BLOCKING_BASE}/${id}`);
  },

  getByRoom: async (roomId: number): Promise<RoomBlocking[]> => {
    const res = await apiClient.get<ApiResponse<RoomBlocking[]>>(
      `${ROOM_BLOCKING_BASE}/room/${roomId}`,
    );
    return res.data.data;
  },

  checkConflicts: async (data: {
    room_id: number;
    start_datetime: string;
    end_datetime: string;
  }): Promise<{ has_conflict: boolean; conflicts: RoomBlocking[] }> => {
    const res = await apiClient.post<
      ApiResponse<{ has_conflict: boolean; conflicts: RoomBlocking[] }>
    >(`${ROOM_BLOCKING_BASE}/check-conflicts`, data);
    return res.data.data;
  },
};

export type TeacherBlockingParams = {
  page?: number;
  teacher_id?: number;
  status?: 'pending' | 'approved' | 'rejected';
  blocking_type?: string;
};

export type CreateTeacherBlockingData = {
  teacher_id: number;
  start_datetime: string;
  end_datetime: string;
  reason: string;
  blocking_type: 'absence' | 'vacation' | 'training' | 'medical' | 'other';
};

export type UpdateTeacherBlockingData = Partial<CreateTeacherBlockingData>;

export const teacherBlockingService = {
  getAll: async (params?: TeacherBlockingParams): Promise<TeacherBlocking[]> => {
    const res = await apiClient.get<PaginatedResponse<TeacherBlocking>>(TEACHER_BLOCKING_BASE, {
      params,
    });
    return res.data.data;
  },

  getById: async (id: number): Promise<TeacherBlocking> => {
    const res = await apiClient.get<ApiResponse<TeacherBlocking>>(`${TEACHER_BLOCKING_BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateTeacherBlockingData): Promise<TeacherBlocking> => {
    const res = await apiClient.post<ApiResponse<TeacherBlocking>>(TEACHER_BLOCKING_BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateTeacherBlockingData): Promise<TeacherBlocking> => {
    const res = await apiClient.put<ApiResponse<TeacherBlocking>>(
      `${TEACHER_BLOCKING_BASE}/${id}`,
      data,
    );
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${TEACHER_BLOCKING_BASE}/${id}`);
  },

  getPending: async (params?: { page?: number }): Promise<TeacherBlocking[]> => {
    const res = await apiClient.get<PaginatedResponse<TeacherBlocking>>(
      `${TEACHER_BLOCKING_BASE}/pending`,
      { params },
    );
    return res.data.data;
  },

  approve: async (id: number): Promise<TeacherBlocking> => {
    const res = await apiClient.put<ApiResponse<TeacherBlocking>>(
      `${TEACHER_BLOCKING_BASE}/${id}/approve`,
    );
    return res.data.data;
  },

  reject: async (id: number, reason?: string): Promise<TeacherBlocking> => {
    const res = await apiClient.put<ApiResponse<TeacherBlocking>>(
      `${TEACHER_BLOCKING_BASE}/${id}/reject`,
      {
        rejection_reason: reason,
      },
    );
    return res.data.data;
  },

  getByTeacher: async (teacherId: number): Promise<TeacherBlocking[]> => {
    const res = await apiClient.get<ApiResponse<TeacherBlocking[]>>(
      `${TEACHER_BLOCKING_BASE}/teacher/${teacherId}`,
    );
    return res.data.data;
  },

  getMyBlockings: async (params?: { page?: number }): Promise<TeacherBlocking[]> => {
    const res = await apiClient.get<PaginatedResponse<TeacherBlocking>>(
      `${TEACHER_BLOCKING_BASE}/my-blockings`,
      { params },
    );
    return res.data.data;
  },

  checkAvailability: async (data: {
    teacher_id: number;
    start_datetime: string;
    end_datetime: string;
  }): Promise<{ available: boolean; blocking?: TeacherBlocking }> => {
    const res = await apiClient.post<
      ApiResponse<{ available: boolean; blocking?: TeacherBlocking }>
    >(`${TEACHER_BLOCKING_BASE}/check-availability`, data);
    return res.data.data;
  },
};
