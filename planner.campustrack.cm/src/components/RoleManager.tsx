import React, { useState } from 'react';
import { Role, Permission } from '@/src/lib/types';
import { Modal } from './Modal';
import { Plus, Edit2, Trash2, Shield, Key, CheckSquare, Square } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';

interface RoleManagerProps {
  roles: Role[];
  permissions: Permission[];
  onCreateRole: (name: string) => void;
  onUpdateRole: (id: number, name: string) => void;
  onDeleteRole: (id: number) => void;
  onSyncPermissions: (roleId: number, permissionIds: number[]) => void;
}

const ROLE_COLORS: Record<string, string> = {
  'super-admin': 'bg-red-100 text-red-700 border-red-200',
  administrateur: 'bg-purple-100 text-purple-700 border-purple-200',
  professeur: 'bg-blue-100 text-blue-700 border-blue-200',
  etudiant: 'bg-green-100 text-green-700 border-green-200',
};

export const RoleManager: React.FC<RoleManagerProps> = ({
  roles,
  permissions,
  onCreateRole,
  onUpdateRole,
  onDeleteRole,
  onSyncPermissions,
}) => {
  const { t } = useTranslation();
  const [isRoleModalOpen, setIsRoleModalOpen] = useState(false);
  const [isPermissionsModalOpen, setIsPermissionsModalOpen] = useState(false);
  const [editingRole, setEditingRole] = useState<Role | null>(null);
  const [selectedRoleId, setSelectedRoleId] = useState<number | null>(null);
  const [selectedPermissions, setSelectedPermissions] = useState<Set<number>>(new Set());

  const [roleName, setRoleName] = useState('');

  const groupedPermissions = permissions.reduce(
    (acc, p) => {
      const parts = p.name.split(' ');
      const resource = parts[1] || 'general';
      if (!acc[resource]) acc[resource] = [];
      acc[resource].push(p);
      return acc;
    },
    {} as Record<string, Permission[]>,
  );

  const handleOpenRoleModal = (role?: Role) => {
    if (role) {
      setEditingRole(role);
      setRoleName(role.name);
    } else {
      setEditingRole(null);
      setRoleName('');
    }
    setIsRoleModalOpen(true);
  };

  const handleRoleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!roleName.trim()) return;

    if (editingRole) {
      onUpdateRole(editingRole.id, roleName);
    } else {
      onCreateRole(roleName);
    }
    setIsRoleModalOpen(false);
    setRoleName('');
  };

  const openPermissionsModal = (role: Role) => {
    setSelectedRoleId(role.id);
    // TODO: charger les permissions réelles du rôle (roleService.getPermissions(role.id))
    // au lieu d'une sélection aléatoire destructive.
    setSelectedPermissions(new Set());
    setIsPermissionsModalOpen(true);
  };

  const togglePermission = (permId: number) => {
    const newSet = new Set(selectedPermissions);
    if (newSet.has(permId)) {
      newSet.delete(permId);
    } else {
      newSet.add(permId);
    }
    setSelectedPermissions(newSet);
  };

  const handlePermissionsSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (selectedRoleId) {
      onSyncPermissions(selectedRoleId, Array.from(selectedPermissions));
    }
    setIsPermissionsModalOpen(false);
  };

  const isSystemRole = (roleName: string) => {
    return ['super-admin', 'administrateur', 'professeur', 'etudiant'].includes(roleName);
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-xl font-bold text-gray-900">{t('rolesAndPermissions')}</h2>
          <p className="text-sm text-gray-500 mt-1">{t('manageRolesDescription')}</p>
        </div>
        <button
          onClick={() => handleOpenRoleModal()}
          className="flex items-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors font-medium"
        >
          <Plus size={18} />
          <span>{t('addRole')}</span>
        </button>
      </div>

      <div className="grid gap-4">
        {roles.map((role) => {
          const colorClass = ROLE_COLORS[role.name] || 'bg-gray-100 text-gray-700 border-gray-200';

          return (
            <div
              key={role.id}
              className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-all"
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                  <div className={`p-3 rounded-lg ${colorClass} border`}>
                    <Shield size={24} />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900">{role.name}</h3>
                    <p className="text-sm text-gray-500">
                      {t('guard')}: {role.guard_name}
                    </p>
                  </div>
                  {isSystemRole(role.name) && (
                    <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                      {t('system')}
                    </span>
                  )}
                </div>

                <div className="flex items-center space-x-2">
                  <button
                    onClick={() => openPermissionsModal(role)}
                    className="flex items-center space-x-1 px-3 py-1.5 text-sm text-gray-600 hover:text-secondary hover:bg-indigo-50 rounded-lg transition-colors"
                  >
                    <Key size={16} />
                    <span>{t('permissions')}</span>
                  </button>
                  <button
                    onClick={() => handleOpenRoleModal(role)}
                    className="p-2 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg"
                    disabled={isSystemRole(role.name)}
                  >
                    <Edit2 size={16} />
                  </button>
                  {!isSystemRole(role.name) && (
                    <button
                      onClick={() => onDeleteRole(role.id)}
                      className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg"
                    >
                      <Trash2 size={16} />
                    </button>
                  )}
                </div>
              </div>
            </div>
          );
        })}

        {roles.length === 0 && (
          <div className="text-center py-12 text-gray-400 bg-white rounded-xl border border-gray-100">
            <Shield size={48} className="mx-auto mb-4 opacity-20" />
            <p>{t('noRolesFound')}</p>
          </div>
        )}
      </div>

      <Modal
        isOpen={isRoleModalOpen}
        onClose={() => setIsRoleModalOpen(false)}
        title={editingRole ? t('editRole') : t('newRole')}
      >
        <form onSubmit={handleRoleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('roleName')}</label>
            <input
              required
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={roleName}
              onChange={(e) => setRoleName(e.target.value)}
              placeholder="e.g. editor"
            />
          </div>
          <div className="pt-4 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => setIsRoleModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary font-medium transition-colors shadow-sm"
            >
              {editingRole ? t('save') : t('create')}
            </button>
          </div>
        </form>
      </Modal>

      <Modal
        isOpen={isPermissionsModalOpen}
        onClose={() => setIsPermissionsModalOpen(false)}
        title={t('managePermissions')}
      >
        <form onSubmit={handlePermissionsSubmit} className="space-y-4">
          <div className="max-h-96 overflow-y-auto space-y-4">
            {Object.entries(groupedPermissions).map(([resource, perms]: [string, Permission[]]) => (
              <div key={resource} className="border border-gray-100 rounded-lg p-3">
                <h4 className="font-medium text-gray-700 mb-2 capitalize">{resource}</h4>
                <div className="grid grid-cols-2 gap-2">
                  {perms.map((perm) => {
                    const isSelected = selectedPermissions.has(perm.id);
                    return (
                      <button
                        key={perm.id}
                        type="button"
                        onClick={() => togglePermission(perm.id)}
                        className={`flex items-center space-x-2 p-2 rounded-lg text-sm text-left transition-colors ${
                          isSelected
                            ? 'bg-indigo-50 text-primary'
                            : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
                        }`}
                      >
                        {isSelected ? <CheckSquare size={16} /> : <Square size={16} />}
                        <span className="truncate">{perm.name}</span>
                      </button>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>

          <div className="pt-4 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => setIsPermissionsModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary font-medium transition-colors shadow-sm"
            >
              {t('savePermissions')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
