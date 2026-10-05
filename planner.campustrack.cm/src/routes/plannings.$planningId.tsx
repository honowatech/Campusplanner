import { createFileRoute, Link } from '@tanstack/react-router';
import { Timetable } from '@/src/components/Timetable';
import { useTeachers } from '@/src/hooks/useTeachers';
import { useRooms } from '@/src/hooks/useRooms';
import { useDepartments } from '@/src/hooks/useDepartments';
import { useClasses } from '@/src/hooks/useClasses';
import { useAuth } from '@/src/auth';
import { usePlanning } from '@/src/hooks/usePlannings';
import { useSettings } from '@/src/hooks/useSettings';
import { useCreateShiftPlanning, useDeleteShiftPlanning } from '@/src/hooks/useShiftPlannings';
import type { ShiftPlanning } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { useCourses } from '@/src/hooks/useCourses';
import { ArrowLeftCircle } from 'lucide-react';

export const Route = createFileRoute('/plannings/$planningId')({
  component: TimetableDetailPage,
});

function TimetableDetailPage() {
  const { planningId } = Route.useParams();
  const { user } = useAuth();

  const { data: courses, isLoading: coursesLoading } = useCourses();
  const { data: teachers, isLoading: teachersLoading } = useTeachers();
  const { data: rooms, isLoading: roomsLoading } = useRooms();
  const { data: departments, isLoading: departmentsLoading } = useDepartments();
  const { data: classes, isLoading: classesLoading } = useClasses();
  const { data: planning, isLoading: planningLoading } = usePlanning(Number(planningId));
  const { data: appSettings, isLoading: settingsLoading } = useSettings();
  const createShift = useCreateShiftPlanning();
  const deleteShift = useDeleteShiftPlanning();

  const shiftPlannings: ShiftPlanning[] = planning?.shift_plannings || [];

  const handleAddShift = (shift: Omit<ShiftPlanning, 'id' | 'created_at' | 'updated_at'>) => {
    createShift.mutate({
      planning_id: shift.planning_id,
      course_id: shift.course_id,
      teacher_id: shift.teacher_id,
      room_id: shift.room_id,
      course_class_id: shift.course_class_id,
      date: shift.date,
      starting_hour: shift.starting_hour.slice(0, 5),
      ending_hour: shift.ending_hour.slice(0, 5),
      number_teachers: shift.number_teachers,
    });
  };

  const handleDeleteShift = (id: number) => {
    deleteShift.mutate(id);
  };

  if (
    coursesLoading ||
    teachersLoading ||
    roomsLoading ||
    departmentsLoading ||
    classesLoading ||
    planningLoading ||
    settingsLoading
  ) {
    return <LoadingSpinner fullScreen={false} />;
  }

  if (!appSettings) {
    return <LoadingSpinner fullScreen={false} />;
  }

  return (
    <>
      <div className="flex mb-4">
        <Link to="/timetables">
          <ArrowLeftCircle className="size-10 fill-white stroke-1 stroke-primary" />
        </Link>
      </div>
      <Timetable
        courses={courses?.data ?? []}
        teachers={teachers?.data ?? []}
        rooms={rooms?.data ?? []}
        departments={departments?.data ?? []}
        classes={classes?.data ?? []}
        shiftPlannings={shiftPlannings}
        planning={planning!}
        user={user}
        onDeleteShift={handleDeleteShift}
        onAddShift={handleAddShift}
        settings={appSettings}
      />
    </>
  );
}
