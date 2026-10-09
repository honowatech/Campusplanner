// ============================================================
// ENUMS
// ============================================================

export enum DayOfWeek {
  Monday = 'Monday',
  Tuesday = 'Tuesday',
  Wednesday = 'Wednesday',
  Thursday = 'Thursday',
  Friday = 'Friday',
}

export enum TimeSlot {
  Morning_1 = '08:00 - 10:00',
  Morning_2 = '10:15 - 12:15',
  Lunch = '12:15 - 13:15',
  Afternoon_1 = '13:15 - 15:15',
  Afternoon_2 = '15:30 - 17:30',
}

export type Period = '24h' | '7d' | '30d';

export type BlockingStatus = 'pending' | 'approved' | 'rejected';

export type RoomBlockingType = 'maintenance' | 'event' | 'holiday' | 'other';

export type TeacherBlockingType = 'absence' | 'vacation' | 'training' | 'medical' | 'other';

export type Gender = 'male' | 'female' | 'other';

export type RoomType = 'classroom' | 'lab' | 'amphitheater' | 'conference' | 'study_room';

export type PlanningType = 'weekly' | 'monthly';

export type ShiftStatus = 'pending' | 'completed' | 'canceled' | 'ongoing';

// ============================================================
// CORE TYPES (fidèles à la DB)
// ============================================================

export type User = {
  id: number;
  name: string;
  email: string;
  department_id?: number;
  email_verified_at?: string;
  requested_role?: string;
  is_approved?: boolean;
  approved_at?: string;
  roles?: Role[];
  permissions?: string[];
  features?: string[];
  subscription?: SubscriptionSummary | null;
  created_at?: string;
  updated_at?: string;
  role?: UserRole;
  avatar?: string;
};

export type Department = {
  id: number;
  name: string;
  code: string;
  description?: string;
  color: string;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
  head_of_department_id?: number;
  courses?: Course[];
  course_classes?: CourseClass[];
};

export type Teacher = {
  id: number;
  user_id?: number;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
  phone?: string;
  department_id: number;
  max_hours_per_week: number;
  is_active: boolean;
  hired_at: string;
  created_at?: string;
  updated_at?: string;
  department?: Department;
  color?: string;
  speciality?: string;
};

export type Student = {
  id: number;
  user_id: number;
  course_class_id?: number;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
  phone?: string;
  date_of_birth?: string;
  gender?: Gender;
  address?: string;
  parent_name?: string;
  parent_phone?: string;
  matricule: string;
  admission_date: string;
  is_active: boolean;
  photo_url?: string | null;
  created_at?: string;
  updated_at?: string;
};

export type Course = {
  id: number;
  name: string;
  code: string;
  description?: string;
  department_id?: number;
  teacher_id?: number;
  coefficient: number;
  hours_per_week?: number;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
  teachers?: Teacher[];
};

export type CourseClass = {
  id: number;
  name: string;
  code: string;
  department_id: number;
  level: string;
  capacity: number;
  room_id?: number;
  academic_year: string;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
};

export type CourseClassWithRelations = CourseClass & {
  students_count: number;
  department: Department;
  students: Student[];
};

export type Room = {
  id: number;
  name: string;
  code: string;
  department_id?: number;
  type: RoomType;
  capacity: number;
  floor?: number;
  building?: string;
  has_projector: boolean;
  has_computers: boolean;
  has_whiteboard: boolean;
  is_active: boolean;
  description?: string;
  created_at?: string;
  updated_at?: string;
};

// ============================================================
// PLANNING TYPES
// ============================================================

export type Planning = {
  id: number;
  type: PlanningType;
  starting_date: string;
  ending_date: string;
  description?: string;
  created_at?: string;
  updated_at?: string;
  shift_plannings?: ShiftPlanning[];
};

export type ShiftPlanning = {
  id: number;
  planning_id: number;
  course_class_id: number;
  room_id?: number;
  course_id: number;
  teacher_id?: number;
  date: string;
  starting_hour: string;
  ending_hour: string;
  number_teachers: number;
  status: ShiftStatus;
  notes?: string;
  created_at?: string;
  updated_at?: string;
  room?: Room;
  course?: Course;
  course_class?: CourseClass;
  teacher?: Teacher;
};

// ============================================================
// BLOCKING TYPES
// ============================================================

export type RoomBlocking = {
  id: number;
  room_id: number;
  start_datetime: string;
  end_datetime: string;
  reason: string;
  blocking_type: RoomBlockingType;
  created_by: number;
  is_recurring: boolean;
  recurrence_pattern?: Record<string, unknown>;
  created_at?: string;
  updated_at?: string;
};

export type TeacherBlocking = {
  id: number;
  teacher_id: number;
  start_datetime: string;
  end_datetime: string;
  reason: string;
  blocking_type: TeacherBlockingType;
  status: BlockingStatus;
  approved_by?: number;
  approved_at?: string;
  rejection_reason?: string;
  created_at?: string;
  updated_at?: string;
};

// ============================================================
// RBAC TYPES
// ============================================================

export type Role = {
  id: number;
  name: string;
  guard_name: string;
  created_at?: string;
  updated_at?: string;
};

export type Permission = {
  id: number;
  name: string;
  guard_name: string;
  created_at?: string;
  updated_at?: string;
};

// ============================================================
// PIVOT TYPES
// ============================================================

export type TeacherCourse = {
  teacher_id: number;
  course_id: number;
};

// ============================================================
// DASHBOARD TYPES
// ============================================================

export type DashboardRealtimeStats = {
  active_teachers: number;
  occupied_rooms: number;
  active_classes: number;
  pending_blockings: number;
  schedule_conflicts?: number;
  updated_at: string;
};

export type DashboardHeavyStats = {
  users: { total: number; new_this_period: number };
  teachers: { total: number; active: number; new_this_period: number };
  students: { total: number; new_this_period: number };
  resources: { courses: number; rooms: number; classes: number };
  planning: {
    total_sessions: number;
    completed: number;
    ongoing: number;
    pending: number;
    canceled: number;
    utilization_rate: number;
  };
};

export type DashboardOverview = {
  realtime: DashboardRealtimeStats;
  heavy: DashboardHeavyStats;
  period: string;
  department_id: number | null;
  generated_at: string;
};

export type DashboardAlert = {
  type: string;
  severity: 'critical' | 'warning' | 'info';
  message: string;
  entity_id: number;
  entity_type: string;
  created_at: string;
};

export type DashboardAlerts = {
  critical: DashboardAlert[];
  warnings: DashboardAlert[];
  info: DashboardAlert[];
};

export type DashboardActivity = {
  type: string;
  description: string;
  status?: string;
  created_at: string;
};

export type DashboardCharts = {
  evolution: {
    labels: string[];
    datasets: { label: string; data: number[] }[];
  };
  comparison: {
    current_period: Record<string, number>;
    previous_period: Record<string, number>;
    change_percentage: Record<string, number>;
  };
};

export type ResourceUtilization = {
  rooms: {
    data: {
      id: number;
      name: string;
      type: string;
      utilization_rate: number;
      booked_hours: number;
    }[];
    average_utilization: number;
  };
  teachers: {
    data: {
      id: number;
      name: string;
      current_hours: number;
      max_hours: number;
      utilization_rate: number;
    }[];
    average_utilization: number;
  };
};

// ============================================================
// AVAILABILITY TYPES
// ============================================================

export type RoomAvailabilityFilters = {
  start_datetime: string;
  end_datetime: string;
  department_id?: number;
  type?: RoomType;
  min_capacity?: number;
  has_projector?: boolean;
  has_computers?: boolean;
  has_whiteboard?: boolean;
};

export type TeacherAvailabilityFilters = {
  start_datetime: string;
  end_datetime: string;
  department_id?: number;
  course_id?: number;
  speciality?: string;
};

export type AvailableRoom = {
  id: number;
  name: string;
  code: string;
  type: RoomType;
  capacity: number;
  building: string;
  floor: number;
  has_projector: boolean;
  has_computers: boolean;
  has_whiteboard: boolean;
};

export type AvailableTeacher = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  speciality: string;
  current_hours: number;
  max_hours: number;
  utilization_rate: number;
};

// ============================================================
// API RESPONSE TYPES
// ============================================================

export type PaginationLink = {
  url: string | null;
  label: string;
  active: boolean;
};

export type PaginatedResponse<T> = {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: PaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
};

export type ApiResponse<T> = {
  status: 'success' | 'failed';
  message: string;
  data: T;
};

export type ApiErrorResponse = {
  status: 'failed';
  message: string;
  data: Record<string, string[]> | null;
};

// ============================================================
// LEGACY TYPES (pour transition)
// ============================================================

export type UserRole =
  | 'super-admin'
  | 'professeur'
  | 'etudiant'
  | 'administrateur'
  | 'responsable-departement'
  | 'personnel-administratif';

export type DashboardStats = {
  totalTeachers: number;
  totalCourses: number;
  conflicts: number;
  departmentDistribution: { name: string; value: number }[];
};

export type AppSettings = {
  maxWeeklyHoursPerTeacher: number;
  maxConsecutiveHours: number;
  maxDailyHoursPerTeacher: number;
  maxDailyHoursPerClass: number;
  enableRoomConflict: boolean;
  enableTeacherConflict: boolean;
  enableGroupConflict: boolean;
  smsConfig: {
    apiKey: string;
    sender_id: string;
    balance: number;
  };
};

export type DemoAccount = {
  role: string;
  name: string;
  email: string;
};

export type DemoModeStatus = {
  enabled: boolean;
  accounts: DemoAccount[];
};

// ============================================================
// SUBSCRIPTION / FEATURE GATING TYPES
// ============================================================

export type Feature = {
  key: string;
  label: string;
  group: string;
  description?: string;
  enabled: boolean;
};

export type FeaturesPayload = {
  features: Feature[];
  groups: Record<string, string>;
};

export type Pack = {
  id: number;
  name: string;
  tier: string;
  slug: string;
  price: number;
  currency: string;
  billing_period: 'monthly' | 'annual';
  description?: string;
  features: string[];
};

export type SubscriptionSummary = {
  id: number;
  status: string;
  billing_period: string;
  starts_at?: string;
  ends_at?: string;
  trial_ends_at?: string;
  auto_renew: boolean;
  pack: {
    id: number;
    name: string;
    tier: string;
    slug: string;
    price: number;
    currency: string;
  } | null;
};

// ============================================================
// BILLING / PAYMENT TYPES
// ============================================================

export type Payment = {
  id: number;
  tenant_id: number;
  subscription_id?: number;
  amount: number;
  currency: string;
  status: string;
  gateway_reference?: string;
  external_reference?: string;
  payment_method?: string;
  paid_at?: string;
  created_at?: string;
  updated_at?: string;
};

// ============================================================
// SMS TYPES
// ============================================================

export type SmsCredential = {
  id: number;
  provider: string;
  user?: string;
  sender_id?: string;
  is_active: boolean;
  balance_cached?: number;
};

export type SmsLog = {
  id: number;
  to: string;
  message: string;
  status: string;
  provider_ref?: string;
  error?: string;
  created_at?: string;
};

// ============================================================
// ADMIN / SUPER-ADMIN TYPES
// ============================================================

export type Tenant = {
  id: number;
  name: string;
  slug: string;
  status: string;
  is_demo: boolean;
  demo_mode: boolean;
  billing_phone?: string;
  users_count?: number;
  created_at?: string;
  subscriptions?: {
    id: number;
    status: string;
    pack?: { id: number; name: string; tier: string } | null;
  }[];
};

export type PaymentGatewayConfig = {
  id: number;
  provider: string;
  user_name?: string;
  app_id?: string;
  endpoint?: string;
  pay_type_id?: string;
  client_fees_rate: number;
  mode: 'sandbox' | 'live';
  is_active: boolean;
};

export type AdminFeature = {
  id: number;
  key: string;
  label: string;
  group: string;
  description?: string;
  is_active: boolean;
};

export type AdminPack = {
  id: number;
  name: string;
  slug: string;
  tier?: string;
  description?: string;
  price: number;
  currency: string;
  billing_period: 'monthly' | 'annual';
  is_active: boolean;
  sort: number;
  features: { id: number; key: string; label: string }[];
};
