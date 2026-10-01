import { apiClient } from '@/src/services/api';
import {
  Planning,
  ShiftPlanning,
  ApiResponse,
  PaginatedResponse,
  PlanningType,
} from '@/src/lib/types';

const PLANNING_BASE = '/api/plannings';
const SHIFT_BASE = '/api/plannings/shift-plannings';

export type PlanningParams = {
  page?: number;
  per_page?: number;
  type?: PlanningType;
  starting_date?: string;
  ending_date?: string;
};

export type CreatePlanningData = {
  type: PlanningType;
  starting_date: string;
  ending_date: string;
  description?: string;
};

export type UpdatePlanningData = Partial<CreatePlanningData>;

export type ShiftPlanningParams = {
  page?: number;
  per_page?: number;
  planning_id?: number;
  course_id?: number;
  teacher_id?: number;
  room_id?: number;
  class_id?: number;
};

export type CreateShiftPlanningData = {
  planning_id: number;
  course_id: number;
  teacher_id?: number;
  room_id?: number;
  course_class_id: number;
  date: string;
  starting_hour: string;
  ending_hour: string;
  number_teachers?: number;
};

export type UpdateShiftPlanningData = Partial<CreateShiftPlanningData>;

export type GeneratePlanningData = {
  planning_id: number;
  courses: number[];
  classes: number[];
  daily_hours?: number;
  prefer_same_room?: boolean;
  max_iterations?: number;
};

export type DetectConflictsData = {
  planning_id: number;
};

export type ResolveConflictData = {
  shift_planning_id: number;
  auto_resolve?: boolean;
};

export type CreateRecurrenceData = {
  pattern: {
    frequency: string;
    days: number[];
    interval?: number;
  };
  end_date: string;
};

export type UpdateSeriesData = {
  room_id?: number;
  teacher_id?: number;
  starting_hour?: string;
  ending_hour?: string;
};

export type CreateDoubleurData = {
  target_class_ids: number[];
  same_room?: boolean;
  same_time?: boolean;
  check_conflicts?: boolean;
};

export type ApplyProposalData = {
  proposal: {
    type: string;
    room_id?: number;
    teacher_id?: number;
    starting_hour?: string;
    ending_hour?: string;
  };
};

export const planningService = {
  // ============ PLANNING (Périodes) ============

  getAll: async (params?: PlanningParams): Promise<Planning[]> => {
    const res = await apiClient.get(PLANNING_BASE, { params });
    return res.data.data.plannings as Planning[];
  },

  getById: async (id: number): Promise<Planning> => {
    const res = await apiClient.get(`${PLANNING_BASE}/${id}`);
    return res.data.data.planning as Planning;
  },

  create: async (data: CreatePlanningData): Promise<Planning> => {
    const res = await apiClient.post(PLANNING_BASE, data);
    return res.data.data.planning as Planning;
  },

  update: async (id: number, data: UpdatePlanningData): Promise<Planning> => {
    const res = await apiClient.put<ApiResponse<Planning>>(`${PLANNING_BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${PLANNING_BASE}/${id}`);
  },

  // ============ SHIFT PLANNING (Cours planifiés) ============

  getAllShifts: async (params?: ShiftPlanningParams): Promise<PaginatedResponse<ShiftPlanning>> => {
    const res = await apiClient.get<ApiResponse<PaginatedResponse<ShiftPlanning>>>(SHIFT_BASE, {
      params,
    });
    return res.data.data;
  },

  getShiftById: async (id: number): Promise<ShiftPlanning> => {
    const res = await apiClient.get<ApiResponse<ShiftPlanning>>(`${SHIFT_BASE}/${id}`);
    return res.data.data;
  },

  createShift: async (data: CreateShiftPlanningData): Promise<ShiftPlanning> => {
    const res = await apiClient.post<ApiResponse<ShiftPlanning>>(SHIFT_BASE, data);
    return res.data.data;
  },

  updateShift: async (id: number, data: UpdateShiftPlanningData): Promise<ShiftPlanning> => {
    const res = await apiClient.put<ApiResponse<ShiftPlanning>>(`${SHIFT_BASE}/${id}`, data);
    return res.data.data;
  },

  deleteShift: async (id: number): Promise<void> => {
    await apiClient.delete(`${SHIFT_BASE}/${id}`);
  },

  // ============ GÉNÉRATION AUTOMATIQUE ============

  generate: async (
    data: GeneratePlanningData,
  ): Promise<{
    generated: ShiftPlanning[];
    failed: any[];
    warnings: string[];
    total_generated: number;
    total_failed: number;
  }> => {
    const res = await apiClient.post<
      ApiResponse<{
        generated: ShiftPlanning[];
        failed: any[];
        warnings: string[];
        total_generated: number;
        total_failed: number;
      }>
    >(`${PLANNING_BASE}/generate`, data);
    return res.data.data;
  },

  // ============ CONFLITS ============

  detectConflicts: async (
    data: DetectConflictsData,
  ): Promise<{
    conflicts: any[];
    total_conflicts: number;
  }> => {
    const res = await apiClient.post<
      ApiResponse<{
        conflicts: any[];
        total_conflicts: number;
      }>
    >(`${PLANNING_BASE}/detect-conflicts`, data);
    return res.data.data;
  },

  resolveConflict: async (
    data: ResolveConflictData,
  ): Promise<{
    shift_planning: ShiftPlanning;
    resolved: boolean;
  }> => {
    const res = await apiClient.post<
      ApiResponse<{
        shift_planning: ShiftPlanning;
        resolved: boolean;
      }>
    >(`${PLANNING_BASE}/resolve-conflicts`, data);
    return res.data.data;
  },

  // ============ RÉCURRENCE ============

  makeRecurring: async (id: number, data: CreateRecurrenceData): Promise<ShiftPlanning> => {
    const res = await apiClient.post<ApiResponse<ShiftPlanning>>(
      `${SHIFT_BASE}/${id}/make-recurring`,
      data,
    );
    return res.data.data;
  },

  updateSeries: async (id: number, data: UpdateSeriesData): Promise<ShiftPlanning> => {
    const res = await apiClient.post<ApiResponse<ShiftPlanning>>(
      `${SHIFT_BASE}/${id}/update-series`,
      data,
    );
    return res.data.data;
  },

  deleteSeries: async (id: number, deleteChildren: boolean = true): Promise<void> => {
    await apiClient.delete(`${SHIFT_BASE}/${id}/delete-series`, {
      data: { delete_children: deleteChildren },
    });
  },

  expandSeries: async (
    id: number,
  ): Promise<{
    shifts: ShiftPlanning[];
    total: number;
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        shifts: ShiftPlanning[];
        total: number;
      }>
    >(`${SHIFT_BASE}/${id}/expand`);
    return res.data.data;
  },

  // ============ DOUBLEURS ============

  createDoubleur: async (
    id: number,
    data: CreateDoubleurData,
  ): Promise<{
    original: ShiftPlanning;
    created: ShiftPlanning[];
    total_created: number;
  }> => {
    const res = await apiClient.post<
      ApiResponse<{
        original: ShiftPlanning;
        created: ShiftPlanning[];
        total_created: number;
      }>
    >(`${SHIFT_BASE}/${id}/create-doubleur`, data);
    return res.data.data;
  },

  getDoubleurOpportunities: async (
    planningId: number,
    minSameTimeSlot?: number,
  ): Promise<any[]> => {
    const params: any = { planning_id: planningId };
    if (minSameTimeSlot) params.min_same_time_slot = minSameTimeSlot;
    const res = await apiClient.get<ApiResponse<any[]>>(`${PLANNING_BASE}/doubleur-opportunities`, {
      params,
    });
    return res.data.data;
  },

  getSimultaneousCourses: async (planningId: number): Promise<any[]> => {
    const res = await apiClient.get<ApiResponse<any[]>>(`${PLANNING_BASE}/simultaneous-courses`, {
      params: { planning_id: planningId },
    });
    return res.data.data;
  },

  // ============ PROPOSITIONS ============

  getProposals: async (
    id: number,
  ): Promise<{
    conflicts: any[];
    proposals: {
      room_alternatives: any[];
      teacher_alternatives: any[];
      time_alternatives: any[];
    };
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        conflicts: any[];
        proposals: {
          room_alternatives: any[];
          teacher_alternatives: any[];
          time_alternatives: any[];
        };
      }>
    >(`${SHIFT_BASE}/${id}/proposals`);
    return res.data.data;
  },

  applyProposal: async (id: number, data: ApplyProposalData): Promise<ShiftPlanning> => {
    const res = await apiClient.post<ApiResponse<ShiftPlanning>>(
      `${SHIFT_BASE}/${id}/apply-proposal`,
      data,
    );
    return res.data.data;
  },

  // ============ STATISTIQUES ============

  getStatistics: async (
    planningId: number,
  ): Promise<{
    total_sessions: number;
    total_hours: number;
    by_teacher: Record<number, number>;
    by_room: Record<number, number>;
    by_class: Record<number, number>;
    conflicts_count: number;
    conflicts: any[];
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        total_sessions: number;
        total_hours: number;
        by_teacher: Record<number, number>;
        by_room: Record<number, number>;
        by_class: Record<number, number>;
        conflicts_count: number;
        conflicts: any[];
      }>
    >(`${PLANNING_BASE}/statistics/${planningId}`);
    return res.data.data;
  },

  optimize: async (
    planningId: number,
  ): Promise<{
    optimized: number;
    skipped: number;
  }> => {
    const res = await apiClient.post<
      ApiResponse<{
        optimized: number;
        skipped: number;
      }>
    >(`${PLANNING_BASE}/optimize/${planningId}`);
    return res.data.data;
  },
};
