import { apiClient } from '@/src/services/api';
import { Role, Permission, ApiResponse, PaginatedResponse } from '@/src/lib/types';

const ROLES_BASE = '/api/roles';
const PERMISSIONS_BASE = '/api/permissions';

export type CreateRoleData = {
  name: string;
  guard_name?: string;
};

export type UpdateRoleData = {
  name?: string;
};

export const roleService = {
  getAll: async (): Promise<Role[]> => {
    const res = await apiClient.get<ApiResponse<Role[]>>(ROLES_BASE);
    return res.data.data;
  },

  getById: async (id: number): Promise<Role> => {
    const res = await apiClient.get<ApiResponse<Role>>(`${ROLES_BASE}/${id}`);
    return res.data.data;
  },

  create: async (data: CreateRoleData): Promise<Role> => {
    const res = await apiClient.post<ApiResponse<Role>>(ROLES_BASE, data);
    return res.data.data;
  },

  update: async (id: number, data: UpdateRoleData): Promise<Role> => {
    const res = await apiClient.put<ApiResponse<Role>>(`${ROLES_BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${ROLES_BASE}/${id}`);
  },

  getPermissions: async (id: number): Promise<Permission[]> => {
    const res = await apiClient.get<ApiResponse<Permission[]>>(`${ROLES_BASE}/${id}/permissions`);
    return res.data.data;
  },

  syncPermissions: async (id: number, permissionIds: number[]): Promise<Role> => {
    const res = await apiClient.post<ApiResponse<Role>>(`${ROLES_BASE}/${id}/permissions`, {
      permissions: permissionIds,
    });
    return res.data.data;
  },

  getUsers: async (id: number, params?: { page?: number }) => {
    const res = await apiClient.get<
      PaginatedResponse<{
        id: number;
        name: string;
        email: string;
      }>
    >(`${ROLES_BASE}/${id}/users`, { params });
    return res.data.data;
  },

  assignToUsers: async (id: number, userIds: number[]): Promise<Role> => {
    const res = await apiClient.post<ApiResponse<Role>>(`${ROLES_BASE}/${id}/users`, {
      users: userIds,
    });
    return res.data.data;
  },
};

export type CreatePermissionData = {
  name: string;
  guard_name?: string;
};

export type UpdatePermissionData = {
  name?: string;
};

export const permissionService = {
  getAll: async (): Promise<Permission[]> => {
    const res = await apiClient.get<ApiResponse<Permission[]>>(PERMISSIONS_BASE);
    return res.data.data;
  },

  create: async (data: CreatePermissionData): Promise<Permission> => {
    const res = await apiClient.post<ApiResponse<Permission>>(PERMISSIONS_BASE, data);
    return res.data.data;
  },

  getByResource: async (): Promise<Record<string, Permission[]>> => {
    const res = await apiClient.get<ApiResponse<Record<string, Permission[]>>>(
      `${PERMISSIONS_BASE}/by-resource`,
    );
    return res.data.data;
  },

  getById: async (id: number): Promise<Permission> => {
    const res = await apiClient.get<ApiResponse<Permission>>(`${PERMISSIONS_BASE}/${id}`);
    return res.data.data;
  },

  update: async (id: number, data: UpdatePermissionData): Promise<Permission> => {
    const res = await apiClient.put<ApiResponse<Permission>>(`${PERMISSIONS_BASE}/${id}`, data);
    return res.data.data;
  },

  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`${PERMISSIONS_BASE}/${id}`);
  },
};

export const userRbacService = {
  getRoles: async (userId: number): Promise<Role[]> => {
    const res = await apiClient.get<ApiResponse<Role[]>>(`/users/${userId}/roles`);
    return res.data.data;
  },

  assignRoles: async (userId: number, roleIds: number[]) => {
    const res = await apiClient.post<
      ApiResponse<{ id: number; name: string; email: string; roles: Role[] }>
    >(`/users/${userId}/roles`, { roles: roleIds });
    return res.data.data;
  },

  removeRoles: async (userId: number, roleIds: number[]) => {
    const res = await apiClient.delete<
      ApiResponse<{ id: number; name: string; email: string; roles: Role[] }>
    >(`/users/${userId}/roles`, { data: { roles: roleIds } });
    return res.data.data;
  },

  syncRoles: async (userId: number, roleIds: number[]) => {
    const res = await apiClient.put<
      ApiResponse<{ id: number; name: string; email: string; roles: Role[] }>
    >(`/users/${userId}/roles/sync`, { roles: roleIds });
    return res.data.data;
  },

  getPermissions: async (userId: number): Promise<Permission[]> => {
    const res = await apiClient.get<ApiResponse<Permission[]>>(`/users/${userId}/permissions`);
    return res.data.data;
  },

  getAllPermissions: async (userId: number): Promise<Permission[]> => {
    const res = await apiClient.get<ApiResponse<Permission[]>>(`/users/${userId}/all-permissions`);
    return res.data.data;
  },

  givePermission: async (userId: number, permissionId: number) => {
    const res = await apiClient.post<
      ApiResponse<{
        id: number;
        name: string;
        email: string;
        permissions: Permission[];
      }>
    >(`/users/${userId}/permissions`, { permission: permissionId });
    return res.data.data;
  },

  revokePermission: async (userId: number, permissionId: number) => {
    const res = await apiClient.delete<
      ApiResponse<{
        id: number;
        name: string;
        email: string;
        permissions: Permission[];
      }>
    >(`/users/${userId}/permissions`, { data: { permission: permissionId } });
    return res.data.data;
  },

  checkPermission: async (userId: number, permission: string): Promise<boolean> => {
    const res = await apiClient.post<ApiResponse<{ has_permission: boolean }>>(
      `/users/${userId}/check-permission`,
      { permission },
    );
    return res.data.data.has_permission;
  },
};
