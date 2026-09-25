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
import { Department, ShiftPlanning } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { useState } from 'react';

export const Route = createFileRoute('/departments')({
  component: DepartmentsPage,
});

function DepartmentsPage() {
  const [page, setPage] = useState(1);
  const { data: departments, isLoading: departmentsLoading } = useDepartments({
    page,
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

  if (departmentsLoading || teachersLoading || groupsLoading) {
    return <LoadingSpinner />;
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
      onAdd={handleAdd}
      onUpdate={handleUpdate}
      onDelete={handleDelete}
      onDeleteCourse={handleDeleteCourse}
    />
  );
}
