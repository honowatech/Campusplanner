import { createCrudHooks } from '@/src/hooks/createCrudHooks';
import {
  courseService,
  CourseParams,
  CreateCourseData,
  UpdateCourseData,
} from '@/src/services/courseService';
import { Course } from '@/src/lib/types';

const crud = createCrudHooks<Course, CourseParams, CreateCourseData, UpdateCourseData>({
  service: courseService,
  queryKey: 'courses',
  singleKey: 'course',
  label: 'Cours',
});

export const useCourses = crud.useList;
export const useCourse = crud.useGet;
export const useCreateCourse = crud.useCreate;
export const useUpdateCourse = crud.useUpdate;
export const useDeleteCourse = crud.useDelete;
