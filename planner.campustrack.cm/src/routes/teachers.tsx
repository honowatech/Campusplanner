import { createFileRoute } from '@tanstack/react-router';
import { TeacherManager } from '@/src/components/TeacherManager';
import {
  useTeachers,
  useCreateTeacher,
  useUpdateTeacher,
  useDeleteTeacher,
} from '@/src/hooks/useTeachers';
import { useDepartments } from '@/src/hooks/useDepartments';
import { useClasses } from '@/src/hooks/useClasses';
import { usePlannings } from '@/src/hooks/usePlannings';
import { useAuth } from '@/src/auth';
import { hasAnyPermission } from '@/src/utils/routePermissions';
import { Teacher, ShiftPlanning } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { useState } from 'react';

export const Route = createFileRoute('/teachers')({
  component: TeachersPage,
});

function TeachersPage() {
  const [page, setPage] = useState(1);
  const auth = useAuth();
  const readOnly = !hasAnyPermission(auth.user?.permissions, [
    'teachers.view.all',
    'teachers.manage.department',
  ]);
  const { data: teachers, isLoading: teachersLoading } = useTeachers({ page });
  const { data: departments, isLoading: departmentsLoading } = useDepartments();
  const { data: classes } = useClasses();
  const { data: plannings } = usePlannings();

  const createTeacher = useCreateTeacher();
  const updateTeacher = useUpdateTeacher();
  const deleteTeacher = useDeleteTeacher();

  const shiftPlannings: ShiftPlanning[] =
    plannings?.flatMap((period) => period.shift_plannings || []) || [];

  const handleAddTeacher = (teacher: Teacher) => {
    createTeacher.mutate({
      first_name: teacher.first_name || '',
      last_name: teacher.last_name || '',
      email: teacher.email,
      phone: teacher.phone,
      speciality: teacher.speciality || '',
      department_id: teacher.department_id ?? undefined,
      max_hours_per_week: teacher.max_hours_per_week || 16,
      is_active: teacher.is_active ?? true,
    });
  };

  const handleUpdateTeacher = (teacher: Teacher) => {
    updateTeacher.mutate({
      id: teacher.id,
      data: {
        first_name: teacher.first_name,
        last_name: teacher.last_name,
        email: teacher.email,
        phone: teacher.phone,
        speciality: teacher.speciality || '',
        department_id: teacher.department_id,
        max_hours_per_week: teacher.max_hours_per_week,
        is_active: teacher.is_active,
      },
    });
  };

  const handleDeleteTeacher = (id: number) => {
    deleteTeacher.mutate(id);
  };

  if (teachersLoading || departmentsLoading) {
    return <LoadingSpinner />;
  }

  return (
    <TeacherManager
      teachers={teachers?.data ?? []}
      currentPage={teachers?.current_page || 1}
      totalPages={teachers?.last_page || 1}
      onPageChange={setPage}
      departments={departments?.data ?? []}
      classes={classes?.data ?? []}
      shiftPlannings={shiftPlannings}
      user={auth.user!}
      readOnly={readOnly}
      onAddTeacher={handleAddTeacher}
      onUpdateTeacher={handleUpdateTeacher}
      onDeleteTeacher={handleDeleteTeacher}
    />
  );
}
