import { createFileRoute } from '@tanstack/react-router';
import { DepartmentManager } from '@/src/components/DepartmentManager';
import {
  useDepartments,
  useCreateDepartment,
  useUpdateDepartment,
  useDeleteDepartment,
} from '@/src/hooks/useDepartments';
import { useTeachers } from '@/src/hooks/useTeachers';
import { useClasses } from '@/src/hooks/useClasses';
import { usePlannings } from '@/src/hooks/usePlannings';
import { useDeleteCourse } from '@/src/hooks/useCourses';
import { useAuth } from '@/src/auth';
import { hasAnyPermission } from '@/src/utils/routePermissions';
import { Department, ShiftPlanning } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { ErrorState } from '@/src/components/ErrorState';
import { useState } from 'react';
import { useDebouncedValue } from '@/src/hooks/useDebouncedValue';

export const Route = createFileRoute('/departments')({
  component: DepartmentsPage,
});

function DepartmentsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebouncedValue(search);
  const { user } = useAuth();
  const readOnly = !hasAnyPermission(user?.permissions, [
    'departments.create',
    'departments.edit',
    'departments.delete',
  ]);
  const { data: departments, isLoading: departmentsLoading, isError: departmentsError, refetch: refetchDepartments } = useDepartments({
    page,
    search: debouncedSearch || undefined,
  });
  const { data: teachers, isLoading: teachersLoading } = useTeachers();
  const { data: classes, isLoading: groupsLoading } = useClasses();
  const { data: plannings } = usePlannings();

  const createDepartment = useCreateDepartment();
  const updateDepartment = useUpdateDepartment();
  const deleteDepartment = useDeleteDepartment();
  const deleteCourse = useDeleteCourse();

  const shiftPlannings: ShiftPlanning[] =
    plannings?.flatMap((period) => period.shift_plannings || []) || [];

  const handleAdd = (department: Department) => {
    createDepartment.mutate({
      name: department.name,
      code: department.code,
      description: department.description,
      color: department.color || '#3B82F6',
      is_active: department.is_active ?? true,
    });
  };

  const handleUpdate = (department: Department) => {
    updateDepartment.mutate({
      id: Number(department.id),
      data: {
        name: department.name,
        code: department.code,
        description: department.description,
        color: department.color,
        is_active: department.is_active,
      },
    });
  };

  const handleDelete = (id: number) => {
    deleteDepartment.mutate(id);
  };

  const handleDeleteCourse = (id: number) => {
    deleteCourse.mutate(id);
  };

  const handleSearchChange = (value: string) => {
    setSearch(value);
    setPage(1);
  };

  if (departmentsLoading || teachersLoading || groupsLoading) {
    return <LoadingSpinner />;
  }

  if (departmentsError) {
    return <ErrorState onRetry={() => refetchDepartments()} />;
  }

  return (
    <DepartmentManager
      departments={departments?.data ?? []}
      teachers={teachers?.data ?? []}
      classes={classes?.data ?? []}
      currentPage={departments?.current_page || 1}
      totalPages={departments?.last_page || 1}
      onPageChange={setPage}
      shiftPlannings={shiftPlannings}
      readOnly={readOnly}
      searchTerm={search}
      onSearchChange={handleSearchChange}
      onAdd={handleAdd}
      onUpdate={handleUpdate}
      onDelete={handleDelete}
      onDeleteCourse={handleDeleteCourse}
    />
  );
}
