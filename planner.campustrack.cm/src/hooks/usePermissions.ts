import { useAuth } from '@/src/auth';

export function usePermissions() {
  const { user } = useAuth();

  const hasPermission = (permission: string): boolean => {
    if (!user?.permissions) return false;
    return user.permissions.includes(permission);
  };

  const hasAnyPermission = (permissions: string[]): boolean => {
    const perms = user?.permissions;
    if (!perms) return false;
    return permissions.some((permission) => perms.includes(permission));
  };

  const hasAllPermissions = (permissions: string[]): boolean => {
    const perms = user?.permissions;
    if (!perms) return false;
    return permissions.every((p) => perms.includes(p));
  };

  const hasRole = (role: string | string[]): boolean => {
    if (!user?.roles) {
      const userRole = user?.role;
      return Array.isArray(role)
        ? userRole !== undefined && role.includes(userRole)
        : userRole === role;
    }
    const roleNames = user.roles.map((r) => r.name);
    if (Array.isArray(role)) {
      return role.some((role) => roleNames.includes(role));
    }
    return roleNames.includes(role);
  };

  const hasAnyRole = (roles: string[]): boolean => {
    if (!user?.roles) {
      const userRole = user?.role;
      return userRole !== undefined && roles.includes(userRole);
    }
    const roleNames = user.roles.map((r) => r.name);
    return roles.some((role) => roleNames.includes(role));
  };

  const can = (action: string, resource: string): boolean => {
    return hasPermission(`${action} ${resource}`);
  };

  const isAdmin = (): boolean => {
    return hasRole(['super-admin', 'administrateur', 'admin']);
  };

  const isSuperAdmin = (): boolean => {
    return hasRole('super-admin');
  };

  const isTeacher = (): boolean => {
    return hasRole('professeur');
  };

  const isStudent = (): boolean => {
    return hasRole('etudiant');
  };

  return {
    user,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    can,
    isAdmin,
    isSuperAdmin,
    isTeacher,
    isStudent,
  };
}
