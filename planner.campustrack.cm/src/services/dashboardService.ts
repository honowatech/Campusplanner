import { apiClient } from '@/src/services/api';
import {
  DashboardOverview,
  DashboardAlerts,
  DashboardActivity,
  DashboardCharts,
  ResourceUtilization,
  ApiResponse,
} from '@/src/lib/types';

const BASE = '/api/dashboard';

export type DashboardParams = {
  period?: '24h' | '7d' | '30d';
  department_id?: number;
  page?: number;
};

export const dashboardService = {
  getOverview: async (params?: DashboardParams): Promise<DashboardOverview> => {
    const res = await apiClient.get<ApiResponse<DashboardOverview>>(`${BASE}/overview`, { params });
    return res.data.data;
  },

  getCharts: async (params?: DashboardParams): Promise<DashboardCharts> => {
    const res = await apiClient.get<ApiResponse<DashboardCharts>>(`${BASE}/charts`, { params });
    return res.data.data;
  },

  getAlerts: async (
    params?: DashboardParams,
  ): Promise<{
    alerts: DashboardAlerts;
    pagination: { current_page: number; per_page: number; total: number };
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        alerts: DashboardAlerts;
        pagination: { current_page: number; per_page: number; total: number };
      }>
    >(`${BASE}/alerts`, { params });
    return res.data.data;
  },

  getActivity: async (
    params?: DashboardParams,
  ): Promise<{
    activity: DashboardActivity[];
    pagination: { current_page: number; per_page: number; total: number };
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        activity: DashboardActivity[];
        pagination: { current_page: number; per_page: number; total: number };
      }>
    >(`${BASE}/activity`, { params });
    return res.data.data;
  },

  getResourceUtilization: async (params?: DashboardParams): Promise<ResourceUtilization> => {
    const res = await apiClient.get<ApiResponse<ResourceUtilization>>(`${BASE}/resources`, {
      params,
    });
    return res.data.data;
  },

  getDepartments: async (
    params?: DashboardParams,
  ): Promise<{
    departments: {
      id: number;
      name: string;
      code: string;
      stats: {
        teachers: number;
        students: number;
        courses: number;
        classes: number;
        rooms: number;
        room_utilization: number;
        avg_teacher_hours: number;
      };
    }[];
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        departments: {
          id: number;
          name: string;
          code: string;
          stats: {
            teachers: number;
            students: number;
            courses: number;
            classes: number;
            rooms: number;
            room_utilization: number;
            avg_teacher_hours: number;
          };
        }[];
      }>
    >(`${BASE}/departments`, { params });
    return res.data.data;
  },

  refreshCache: async (pattern?: string): Promise<{ message: string; cleared_at: string }> => {
    const res = await apiClient.post<ApiResponse<{ message: string; cleared_at: string }>>(
      `${BASE}/refresh-cache`,
      null,
      { params: { pattern } },
    );
    return res.data.data;
  },
};
