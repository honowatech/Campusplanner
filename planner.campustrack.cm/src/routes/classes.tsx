import { createFileRoute } from '@tanstack/react-router';
import { StudentGroupManager } from '@/src/components/StudentGroupManager';
import { useClasses, useCreateClass, useUpdateClass, useDeleteClass } from '@/src/hooks/useClasses';
import { useDepartments } from '@/src/hooks/useDepartments';
import { useAuth } from '@/src/auth';
import { hasAnyPermission } from '@/src/utils/routePermissions';
import { CourseClass } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { useState } from 'react';

export const Route = createFileRoute('/classes')({
  component: ClassesPage,
});

function ClassesPage() {
  const auth = useAuth();
  const [page, setPage] = useState(1);
  const readOnly = !hasAnyPermission(auth.user?.permissions, ['classes.manage']);
  const { data: classes, isLoading: groupsLoading } = useClasses({ page });
  const { data: departments, isLoading: departmentsLoading } = useDepartments();

  const createClass = useCreateClass();
  const updateClass = useUpdateClass();
  const deleteClass = useDeleteClass();

  const handleAdd = (classItem: CourseClass) => {
    createClass.mutate({
      name: classItem.name,
      code: classItem.code,
      department_id: Number(classItem.department_id),
      level: classItem.level,
      capacity: classItem.capacity,
      academic_year: '2025-2026',
      is_active: true,
    });
  };

  const handleUpdate = (classItem: CourseClass) => {
    updateClass.mutate({
      id: Number(classItem.id),
      data: {
        name: classItem.name,
        code: classItem.code,
        department_id: Number(classItem.department_id),
        level: classItem.level,
        capacity: classItem.capacity,
      },
    });
  };

  const handleDelete = (id: number) => {
    deleteClass.mutate(id);
  };

  if (groupsLoading || departmentsLoading) {
    return <LoadingSpinner />;
  }

  return (
    <StudentGroupManager
      classes={classes?.data ?? []}
      departments={departments?.data ?? []}
      user={auth.user!}
      currentPage={page}
      totalPages={classes?.last_page || 1}
      onPageChange={setPage}
      readOnly={readOnly}
      onAdd={handleAdd}
      onUpdate={handleUpdate}
      onDelete={handleDelete}
    />
  );
}
