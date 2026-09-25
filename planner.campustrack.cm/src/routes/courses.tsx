import { createFileRoute } from '@tanstack/react-router';
import { CourseManager } from '@/src/components/CourseManager';
import {
  useCourses,
  useCreateCourse,
  useUpdateCourse,
  useDeleteCourse,
} from '@/src/hooks/useCourses';
import { useTeachers } from '@/src/hooks/useTeachers';
import { useClasses } from '@/src/hooks/useClasses';
import { usePlannings } from '@/src/hooks/usePlannings';
import { Course, ShiftPlanning } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { useState } from 'react';

export const Route = createFileRoute('/courses')({
  component: CoursesPage,
});

function CoursesPage() {
  const [page, setPage] = useState(1);
  const { data: courses, isLoading: coursesLoading } = useCourses({ page });
  const { data: teachers, isLoading: teachersLoading } = useTeachers();
  const { data: classes, isLoading: groupsLoading } = useClasses();
  const { data: plannings } = usePlannings();

  const createCourse = useCreateCourse();
  const updateCourse = useUpdateCourse();
  const deleteCourse = useDeleteCourse();

  const shiftPlannings: ShiftPlanning[] =
    plannings?.flatMap((period) => period.shift_plannings || []) || [];

  const handleAddCourse = (course: Course) => {
    createCourse.mutate({
      name: course.name,
      code: course.code,
      description: course.description,
      department_id: course.department_id,
      coefficient: course.coefficient || 1,
      hours_per_week: course.hours_per_week,
      is_active: course.is_active ?? true,
    });
  };

  const handleUpdateCourse = (course: Course) => {
    updateCourse.mutate({
      id: course.id,
      data: {
        name: course.name,
        code: course.code,
        description: course.description,
        department_id: course.department_id,
        coefficient: course.coefficient,
        hours_per_week: course.hours_per_week,
        is_active: course.is_active,
      },
    });
  };

  const handleDeleteCourse = (id: number) => {
    deleteCourse.mutate(id);
  };

  if (coursesLoading || teachersLoading || groupsLoading) {
    return <LoadingSpinner />;
  }

  return (
    <CourseManager
      courses={courses?.data ?? []}
      currentPage={courses?.current_page || 1}
      totalPages={courses?.last_page || 1}
      onPageChange={setPage}
      teachers={teachers?.data ?? []}
      classes={classes?.data ?? []}
      shiftPlannings={shiftPlannings}
      onAddCourse={handleAddCourse}
      onUpdateCourse={handleUpdateCourse}
      onDeleteCourse={handleDeleteCourse}
    />
  );
}
