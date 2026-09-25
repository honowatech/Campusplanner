import { createCrudHooks } from '@/src/hooks/createCrudHooks';
import {
  teacherService,
  TeacherParams,
  CreateTeacherData,
  UpdateTeacherData,
} from '@/src/services/teacherService';
import { Teacher } from '@/src/lib/types';

const crud = createCrudHooks<Teacher, TeacherParams, CreateTeacherData, UpdateTeacherData>({
  service: teacherService,
  queryKey: 'teachers',
  singleKey: 'teacher',
  label: 'Enseignant',
});

export const useTeachers = crud.useList;
export const useTeacher = crud.useGet;
export const useCreateTeacher = crud.useCreate;
export const useUpdateTeacher = crud.useUpdate;
export const useDeleteTeacher = crud.useDelete;
