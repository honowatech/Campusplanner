import { createFileRoute } from '@tanstack/react-router';
import { StudentManager } from '@/src/components/StudentManager';
import {
  useStudents,
  useCreateStudent,
  useUpdateStudent,
  useDeleteStudent,
  useMoveStudent,
} from '@/src/hooks/useStudents';
import { useDepartments } from '@/src/hooks/useDepartments';
import { useClasses } from '@/src/hooks/useClasses';
import { Student } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { useState } from 'react';

export const Route = createFileRoute('/students')({
  component: StudentsPage,
});

function StudentsPage() {
  const [page, setPage] = useState(1);
  const { data: students, isLoading: studentsLoading } = useStudents({ page });
  const { data: departments, isLoading: departmentsLoading } = useDepartments();
  const { data: classes, isLoading: classesLoading } = useClasses();

  const createStudent = useCreateStudent();
  const updateStudent = useUpdateStudent();
  const deleteStudent = useDeleteStudent();
  const moveStudent = useMoveStudent();

  const handleAddStudent = (student: Student) => {
    createStudent.mutate({
      first_name: student.first_name,
      last_name: student.last_name,
      email: student.email,
      phone: student.phone,
      date_of_birth: student.date_of_birth,
      gender: student.gender,
      address: student.address,
      parent_name: student.parent_name,
      parent_phone: student.parent_phone,
      matricule: student.matricule,
      admission_date: student.admission_date,
      course_class_id: student.course_class_id || 1,
      is_active: student.is_active ?? true,
    });
  };

  const handleUpdateStudent = (student: Student) => {
    updateStudent.mutate({
      id: student.id,
      data: {
        first_name: student.first_name,
        last_name: student.last_name,
        email: student.email,
        phone: student.phone,
        date_of_birth: student.date_of_birth,
        gender: student.gender,
        address: student.address,
        parent_name: student.parent_name,
        parent_phone: student.parent_phone,
        matricule: student.matricule,
        admission_date: student.admission_date || new Date().toISOString().split('T')[0],
        course_class_id: student.course_class_id,
        is_active: student.is_active,
      },
    });
  };

  const handleDeleteStudent = (id: number) => {
    deleteStudent.mutate(id);
  };

  const handleMoveStudent = (id: number, classId: number) => {
    moveStudent.mutate({ id, classId });
  };

  if (studentsLoading || departmentsLoading || classesLoading) {
    return <LoadingSpinner />;
  }

  return (
    <StudentManager
      students={students?.data ?? []}
      departments={departments?.data ?? []}
      classes={classes?.data ?? []}
      currentPage={students?.current_page || 1}
      totalPages={students?.last_page || 1}
      onPageChange={setPage}
      onAddStudent={handleAddStudent}
      onUpdateStudent={handleUpdateStudent}
      onDeleteStudent={handleDeleteStudent}
      onMoveStudent={handleMoveStudent}
    />
  );
}
