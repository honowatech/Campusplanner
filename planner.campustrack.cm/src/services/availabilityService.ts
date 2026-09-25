import { apiClient } from './api';
import {
  RoomAvailabilityFilters,
  TeacherAvailabilityFilters,
  AvailableRoom,
  AvailableTeacher,
  ApiResponse,
} from '@/src/lib/types';

export const availabilityService = {
  searchAvailableRooms: (filters: RoomAvailabilityFilters) =>
    apiClient.post<ApiResponse<AvailableRoom[]>>('/api/rooms/search-available', filters),

  searchAvailableTeachers: (filters: TeacherAvailabilityFilters) =>
    apiClient.post<ApiResponse<AvailableTeacher[]>>('/api/teachers/search-available', filters),

  checkRoomAvailability: (roomId: number, data: { start_datetime: string; end_datetime: string }) =>
    apiClient.post<ApiResponse<{ available: boolean; reason?: string }>>(
      `/rooms/${roomId}/check-availability`,
      data,
    ),

  checkTeacherAvailability: (
    teacherId: number,
    data: { start_datetime: string; end_datetime: string },
  ) =>
    apiClient.post<ApiResponse<{ available: boolean; reason?: string }>>(
      `/api/teachers/${teacherId}/check-availability`,
      data,
    ),
};
