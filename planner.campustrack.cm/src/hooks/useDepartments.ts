import { createCrudHooks } from '@/src/hooks/createCrudHooks';
import {
  departmentService,
  DepartmentParams,
  CreateDepartmentData,
  UpdateDepartmentData,
} from '@/src/services/departmentService';
import { Department } from '@/src/lib/types';

const crud = createCrudHooks<
  Department,
  DepartmentParams,
  CreateDepartmentData,
  UpdateDepartmentData
>({
  service: departmentService,
  queryKey: 'departments',
  singleKey: 'department',
  label: 'Département',
});

export const useDepartments = crud.useList;
export const useDepartment = crud.useGet;
export const useCreateDepartment = crud.useCreate;
export const useUpdateDepartment = crud.useUpdate;
export const useDeleteDepartment = crud.useDelete;
