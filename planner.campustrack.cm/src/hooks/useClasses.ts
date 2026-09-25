import { createCrudHooks } from '@/src/hooks/createCrudHooks';
import {
  classService,
  ClassParams,
  CreateClassData,
  UpdateClassData,
} from '@/src/services/classService';
import { CourseClass } from '@/src/lib/types';

const crud = createCrudHooks<CourseClass, ClassParams, CreateClassData, UpdateClassData>({
  service: classService,
  queryKey: 'classes',
  singleKey: 'class',
  label: 'Classe',
});

export const useClasses = crud.useList;
export const useClass = crud.useGet;
export const useCreateClass = crud.useCreate;
export const useUpdateClass = crud.useUpdate;
export const useDeleteClass = crud.useDelete;
