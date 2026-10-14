import { SmartPagination } from '@/src/components/SmartPagination';
import React, { useState } from 'react';
import { User, Department } from '@/src/lib/types';
import { CreateUserData } from '@/src/services/userService';
import { Modal } from './Modal';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  Mail,
  Building2,
  CheckCircle2,
  XCircle,
  Clock,
  PowerOff,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';

// Rôles attribuables depuis l'interface d'administration
const BACKEND_ROLES = [
  'responsable-departement',
  'professeur',
  'personnel-administratif',
  'etudiant',
] as const;

type UserManagerProps = {
  users: User[];
  departments: Department[];
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  searchTerm: string;
  onSearchChange: (value: string) => void;
  onAdd: (user: CreateUserData) => void;
  onUpdate: (user: User) => void;
  onDelete: (id: number) => void;
  pendingUsers?: User[];
  onApprove?: (id: number, role?: string) => void;
  onReject?: (id: number) => void;
  onDeactivate?: (id: number) => void;
};

export const UserManager: React.FC<UserManagerProps> = ({
  users,
  departments,
  currentPage,
  totalPages,
  onPageChange,
  searchTerm,
  onSearchChange,
  onAdd,
  onUpdate,
  onDelete,
  pendingUsers = [],
  onApprove,
  onReject,
  onDeactivate,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);

  const [formData, setFormData] = useState<Partial<User>>({
    name: '',
    email: '',
    department_id: undefined,
  });
  // Rôle backend (nom Spatie) + mot de passe pour la création
  const [selectedRole, setSelectedRole] = useState<string>('professeur');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');

  const handleOpenModal = (user?: User) => {
    if (user) {
      setEditingUser(user);
      setFormData(user);
      setSelectedRole(user.roles?.[0]?.name ?? 'professeur');
    } else {
      setEditingUser(null);
      setFormData({
        name: '',
        email: '',
        department_id: undefined,
      });
      setSelectedRole('professeur');
    }
    setPassword('');
    setPasswordConfirmation('');
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.email) return;

    if (editingUser) {
      onUpdate({ ...editingUser, ...formData } as User);
    } else {
      if (password.length < 8 || password !== passwordConfirmation || !selectedRole) {
        return;
      }
      onAdd({
        name: formData.name,
        email: formData.email,
        password,
        password_confirmation: passwordConfirmation,
        department_id: formData.department_id,
        roles: [selectedRole],
      });
    }
    setIsModalOpen(false);
  };

  const getDepartmentName = (id?: number) => {
    if (!id) return '-';
    return departments.find((department) => department.id === id)?.name || '-';
  };

  const getBackendRoleLabel = (roleName: string) => {
    switch (roleName) {
      case 'administrateur':
        return t('adminRole');
      default:
        return getRoleLabel(roleName);
    }
  };

  const getRoleLabel = (roleName?: string) => {
    switch (roleName) {
      case 'professeur':
        return t('roleProfesseur');
      case 'etudiant':
        return t('roleEtudiant');
      case 'personnel-administratif':
        return t('rolePersonnel');
      case 'responsable-departement':
        return t('roleResponsable');
      default:
        return roleName ?? '-';
    }
  };

  return (
    <div className="space-y-6 h-full flex flex-col">
      {pendingUsers.length > 0 && (
        <div className="bg-amber-50/60 border border-amber-200 rounded-xl p-4">
          <div className="flex items-center gap-2 mb-3">
            <Clock size={18} className="text-amber-600" />
            <h3 className="font-semibold text-amber-900">
              {t('pendingApprovals')} ({pendingUsers.length})
            </h3>
          </div>
          <div className="space-y-2">
            {pendingUsers.map((pending) => (
              <div
                key={pending.id}
                className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white border border-amber-100 rounded-lg p-3"
              >
                <div className="flex items-center space-x-3">
                  <div className="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 text-xs font-semibold">
                    {pending.name.charAt(0)}
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-900">{pending.name}</p>
                    <p className="text-xs text-gray-500">{pending.email}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                    {t('requested')}: {getRoleLabel(pending.requested_role)}
                  </span>
                  {onApprove && (
                    <button
                      type="button"
                      onClick={() => onApprove(pending.id)}
                      className="flex items-center space-x-1 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors"
                    >
                      <CheckCircle2 size={14} />
                      <span>{t('approve')}</span>
                    </button>
                  )}
                  {onReject && (
                    <button
                      type="button"
                      onClick={() => onReject(pending.id)}
                      className="flex items-center space-x-1 px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-lg hover:bg-red-100 transition-colors"
                    >
                      <XCircle size={14} />
                      <span>{t('rejectAccount')}</span>
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div className="relative w-full sm:w-96">
          <Search
            className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
            size={20}
          />
          <input
            type="text"
            placeholder={t('searchUsers')}
            className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
          />
        </div>
        <button
          type="button"
          onClick={() => handleOpenModal()}
          className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
        >
          <Plus size={20} />
          <span>{t('addUser')}</span>
        </button>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200">
                <th className="p-4 font-semibold text-gray-600 text-sm">{t('fullName')}</th>
                <th className="p-4 font-semibold text-gray-600 text-sm">{t('email')}</th>
                <th className="p-4 font-semibold text-gray-600 text-sm">{t('role')}</th>
                <th className="p-4 font-semibold text-gray-600 text-sm">{t('department')}</th>
                <th className="p-4 font-semibold text-gray-600 text-sm text-right">
                  {t('actions')}
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {users?.map((user) => (
                <tr key={user.id} className="hover:bg-gray-50 transition-colors">
                  <td className="p-4">
                    <div className="flex items-center">
                      <div
                        className={`w-8 h-8 rounded-full flex items-center justify-center text-white text-xs mr-3 ${user.role === 'administrateur' ? 'bg-indigo-500' : 'bg-purple-500'}`}
                      >
                        {user.name.charAt(0)}
                      </div>
                      <span
                        className={`font-medium ${user.is_approved === false ? 'text-gray-400' : 'text-gray-900'}`}
                      >
                        {user.name}
                      </span>
                      {user.is_approved === false && (
                        <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700">
                          {t('deactivated')}
                        </span>
                      )}
                    </div>
                  </td>
                  <td className="p-4 text-gray-600">
                    <div className="flex items-center">
                      <Mail size={14} className="mr-2 text-gray-400" />
                      {user.email}
                    </div>
                  </td>
                  <td className="p-4">
                    <span
                      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.role === 'administrateur' ? 'bg-indigo-100 text-primary' : 'bg-purple-100 text-purple-800'}`}
                    >
                      {user.role === 'administrateur' ? t('adminRole') : t('hodRole')}
                    </span>
                  </td>
                  <td className="p-4 text-gray-600">
                    {user.role === 'responsable-departement' && user.department_id ? (
                      <div className="flex items-center">
                        <Building2 size={14} className="mr-2 text-gray-400" />
                        {getDepartmentName(user.department_id)}
                      </div>
                    ) : (
                      <span className="text-gray-400">-</span>
                    )}
                  </td>
                  <td className="p-4 text-right space-x-2">
                    {user.is_approved === false && onApprove ? (
                      <button
                        type="button"
                        onClick={() => onApprove(user.id)}
                        title={t('reactivateUser')}
                        className="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                      >
                        <CheckCircle2 size={18} />
                      </button>
                    ) : onDeactivate ? (
                      <button
                        type="button"
                        onClick={() => onDeactivate(user.id)}
                        title={t('deactivateUser')}
                        className="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                      >
                        <PowerOff size={18} />
                      </button>
                    ) : null}
                    <button
                      type="button"
                      onClick={() => handleOpenModal(user)}
                      className="p-2 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg transition-colors"
                    >
                      <Edit2 size={18} />
                    </button>
                    <button
                      type="button"
                      onClick={() => onDelete(user.id)}
                      className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    >
                      <Trash2 size={18} />
                    </button>
                  </td>
                </tr>
              ))}
              {users?.length === 0 && (
                <tr>
                  <td colSpan={5} className="p-8 text-center text-gray-500">
                    {t('noResults')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        <SmartPagination
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={onPageChange}
        />
      </div>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingUser ? t('editUser') : t('newUser')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="user-name" className="block text-sm font-medium text-gray-700 mb-1">{t('fullName')}</label>
            <input
              required
              id="user-name"
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            />
          </div>
          <div>
            <label htmlFor="user-email" className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
            <input
              required
              id="user-email"
              type="email"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="user-role" className="block text-sm font-medium text-gray-700 mb-1">{t('role')}</label>
              <select
                required
                id="user-role"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={selectedRole}
                onChange={(e) => setSelectedRole(e.target.value)}
              >
                {BACKEND_ROLES.map((role) => (
                  <option key={role} value={role}>
                    {getBackendRoleLabel(role)}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="user-department" className="block text-sm font-medium text-gray-700 mb-1">
                {t('department')}
              </label>
              <select
                id="user-department"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.department_id}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    department_id: e.target.value ? Number(e.target.value) : undefined,
                  })
                }
              >
                <option value="">{t('selectDept')}</option>
                {departments?.map((department) => (
                  <option key={department.id} value={department.id}>
                    {department.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {!editingUser && (
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label htmlFor="user-password" className="block text-sm font-medium text-gray-700 mb-1">
                  {t('password')}
                </label>
                <input
                  required
                  id="user-password"
                  minLength={8}
                  type="password"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                />
              </div>
              <div>
                <label htmlFor="user-password-confirmation" className="block text-sm font-medium text-gray-700 mb-1">
                  {t('confirmPassword')}
                </label>
                <input
                  required
                  id="user-password-confirmation"
                  minLength={8}
                  type="password"
                  className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none ${
                    passwordConfirmation && passwordConfirmation !== password
                      ? 'border-red-400'
                      : 'border-gray-300'
                  }`}
                  value={passwordConfirmation}
                  onChange={(e) => setPasswordConfirmation(e.target.value)}
                  placeholder="••••••••"
                />
                {passwordConfirmation && passwordConfirmation !== password && (
                  <p className="mt-1 text-xs text-red-600">
                    Les mots de passe ne correspondent pas
                  </p>
                )}
              </div>
            </div>
          )}

          <div className="pt-4 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary font-medium transition-colors shadow-sm"
            >
              {editingUser ? t('save') : t('add')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
