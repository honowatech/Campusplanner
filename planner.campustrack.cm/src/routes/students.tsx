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
import { useAuth } from '@/src/auth';
import { hasAnyPermission } from '@/src/utils/routePermissions';
import { Student } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { ErrorState } from '@/src/components/ErrorState';
import { useState } from 'react';
import { useDebouncedValue } from '@/src/hooks/useDebouncedValue';

export const Route = createFileRoute('/students')({
  component: StudentsPage,
});

function StudentsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [filterClass, setFilterClass] = useState('');
  const [filterDepartment, setFilterDepartment] = useState('');
  const debouncedSearch = useDebouncedValue(search);

  const { user } = useAuth();
  const { data: students, isLoading: studentsLoading, isError: studentsError, refetch: refetchStudents } = useStudents({
    page,
    search: debouncedSearch || undefined,
    course_class_id: filterClass ? Number(filterClass) : undefined,
    department_id: filterDepartment ? Number(filterDepartment) : undefined,
  });
  const { data: departments, isLoading: departmentsLoading } = useDepartments();
  const { data: classes, isLoading: classesLoading } = useClasses();

  const readOnly = !hasAnyPermission(user?.permissions, [
    'students.create',
    'students.edit',
    'students.delete',
  ]);

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

  const handleSearchChange = (value: string) => {
    setSearch(value);
    setPage(1);
  };

  const handleFilterClassChange = (value: string) => {
    setFilterClass(value);
    setPage(1);
  };

  const handleFilterDepartmentChange = (value: string) => {
    setFilterDepartment(value);
    setFilterClass('');
    setPage(1);
  };

  if (studentsLoading || departmentsLoading || classesLoading) {
    return <LoadingSpinner />;
  }

  if (studentsError) {
    return <ErrorState onRetry={() => refetchStudents()} />;
  }

  return (
    <StudentManager
      students={students?.data ?? []}
      departments={departments?.data ?? []}
      classes={classes?.data ?? []}
      currentPage={students?.current_page || 1}
      totalPages={students?.last_page || 1}
      onPageChange={setPage}
      readOnly={readOnly}
      searchTerm={search}
      filterClass={filterClass}
      filterDepartment={filterDepartment}
      onSearchChange={handleSearchChange}
      onFilterClassChange={handleFilterClassChange}
      onFilterDepartmentChange={handleFilterDepartmentChange}
      onAddStudent={handleAddStudent}
      onUpdateStudent={handleUpdateStudent}
      onDeleteStudent={handleDeleteStudent}
      onMoveStudent={handleMoveStudent}
    />
  );
}
