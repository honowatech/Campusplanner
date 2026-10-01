import { apiClient } from '@/src/services/api';
import {
  Planning,
  ShiftPlanning,
  ApiResponse,
  PaginatedResponse,
  PlanningType,
  Room,
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

// ============ TYPES DES RÉPONSES (miroir des services du module Planning) ============

export type PlanningConflictType =
  | 'room_conflict'
  | 'teacher_conflict'
  | 'class_conflict'
  | 'room_blocking'
  | 'teacher_blocking'
  | 'hours_exceeded';

export type PlanningConflict = {
  type: PlanningConflictType;
  message: string;
  room_id?: number;
  teacher_id?: number;
  course_class_id?: number;
  conflicting_plannings?: number[];
  blocking_id?: number;
  blocking_reason?: string;
  current_hours?: number;
  hours_needed?: number;
  max_hours?: number;
};

export type GenerationFailure = {
  class_id: number;
  class_name: string;
  course_id: number;
  course_name: string;
  hours_scheduled: number;
  hours_needed: number;
  reason: string;
};

export type RoomAlternative = {
  type: 'change_room';
  room_id: number;
  room_name: string;
  room_building: string | null;
  room_floor: number | null;
  room_capacity: number;
  description: string;
};

export type TeacherAlternative = {
  type: 'change_teacher';
  teacher_id: number;
  teacher_name: string;
  teacher_speciality: string | null;
  description: string;
};

export type TimeAlternative = {
  type: 'change_time';
  date: string;
  starting_hour: string;
  ending_hour: string;
  description: string;
};

export type ResolutionProposals = {
  room_alternatives?: RoomAlternative[];
  teacher_alternatives?: TeacherAlternative[];
  time_alternatives?: TimeAlternative[];
};

export type DoubleurOpportunity = {
  time_slot: string;
  course_id: number;
  course_name: string | null;
  classes_involved: number[];
  suggested_room: Room;
  potential_savings: number;
};

export type SimultaneousCourse = {
  date: string;
  starting_hour: string;
  ending_hour: string;
  courses: {
    id: number;
    class: string | null;
    course: string | null;
    teacher: string | null;
    room: string | null;
  }[];
  total_classes: number;
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
    failed: GenerationFailure[];
    warnings: string[];
    total_generated: number;
    total_failed: number;
  }> => {
    const res = await apiClient.post<
      ApiResponse<{
        generated: ShiftPlanning[];
        failed: GenerationFailure[];
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
    conflicts: PlanningConflict[];
    total_conflicts: number;
  }> => {
    const res = await apiClient.post<
      ApiResponse<{
        conflicts: PlanningConflict[];
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
  ): Promise<{ opportunities: DoubleurOpportunity[]; total: number }> => {
    const params: { planning_id: number; min_same_time_slot?: number } = { planning_id: planningId };
    if (minSameTimeSlot) params.min_same_time_slot = minSameTimeSlot;
    const res = await apiClient.get<
      ApiResponse<{ opportunities: DoubleurOpportunity[]; total: number }>
    >(`${PLANNING_BASE}/doubleur-opportunities`, {
      params,
    });
    return res.data.data;
  },

  getSimultaneousCourses: async (
    planningId: number,
  ): Promise<{ simultaneous: SimultaneousCourse[]; total: number }> => {
    const res = await apiClient.get<
      ApiResponse<{ simultaneous: SimultaneousCourse[]; total: number }>
    >(`${PLANNING_BASE}/simultaneous-courses`, {
      params: { planning_id: planningId },
    });
    return res.data.data;
  },

  // ============ PROPOSITIONS ============

  getProposals: async (
    id: number,
  ): Promise<{
    conflicts: PlanningConflict[];
    proposals: ResolutionProposals;
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        conflicts: PlanningConflict[];
        proposals: ResolutionProposals;
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
    conflicts: PlanningConflict[];
  }> => {
    const res = await apiClient.get<
      ApiResponse<{
        total_sessions: number;
        total_hours: number;
        by_teacher: Record<number, number>;
        by_room: Record<number, number>;
        by_class: Record<number, number>;
        conflicts_count: number;
        conflicts: PlanningConflict[];
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
