import React from 'react';
import { User, Department } from '@/src/lib/types';
import { useTranslation } from '@/src/utils/i18n';
import { User as UserIcon, Shield, Building2, Mail } from 'lucide-react';

type UserProfileProps = {
  user: User;
  department?: Department;
};

export const UserProfile: React.FC<UserProfileProps> = ({ user, department }) => {
  const { t } = useTranslation();

  return (
    <div className="max-w-4xl mx-auto">
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {/* Header Background */}
        <div className="h-32 bg-linear-to-r from-primary to-secondary"></div>

        <div className="px-8 pb-8 relative">
          {/* Avatar */}
          <div className="absolute -top-16 left-8">
            <div className="size-32 rounded-full border-4 border-white bg-white shadow-md flex items-center justify-center">
              {user.avatar ? (
                <img
                  src={user.avatar}
                  alt={user.name}
                  className="size-full rounded-full object-cover"
                />
              ) : (
                <div
                  className={`size-full rounded-full flex items-center justify-center text-4xl font-bold text-white ${user.role === 'admin' ? 'bg-primary' : 'bg-secondary'}`}
                >
                  {user.name.charAt(0)}
                </div>
              )}
            </div>
          </div>

          {/* User Header Info */}
          <div className="ml-40 pt-4 mb-8">
            <h1 className="text-3xl font-bold text-gray-900">{user.name}</h1>
            <div className="flex items-center space-x-4 mt-2">
              <span
                className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${user.role === 'admin' ? 'bg-indigo-100 text-primary' : 'bg-purple-100 text-purple-800'}`}
              >
                {user.role === 'admin' ? (
                  <Shield size={14} className="mr-1.5" />
                ) : (
                  <UserIcon size={14} className="mr-1.5" />
                )}
                {user.role === 'admin' ? t('adminRole') : t('hodRole')}
              </span>
              <span className="flex items-center text-gray-500 text-sm">
                <Mail size={14} className="mr-1.5" />
                {user.email}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8 border-t border-gray-100 pt-8">
            {/* Left Column - Personal Info */}
            <div className="md:col-span-2 space-y-6">
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('personalInfo')}</h3>
                <div className="bg-gray-50 rounded-xl p-6 space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-medium text-gray-500 uppercase mb-1">
                        {t('fullName')}
                      </label>
                      <p className="text-gray-900 font-medium">{user.name}</p>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 uppercase mb-1">
                        {t('email')}
                      </label>
                      <p className="text-gray-900 font-medium">{user.email}</p>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 uppercase mb-1">
                        ID
                      </label>
                      <p className="text-gray-900 font-medium font-mono">{user.id}</p>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-gray-500 uppercase mb-1">
                        {t('role')}
                      </label>
                      <p className="text-gray-900 font-medium capitalize">{user.role}</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Managed Department Section for HOD */}
              {user.role === 'hod' && department && (
                <div>
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">{t('managedDept')}</h3>
                  <div className="bg-purple-50 rounded-xl p-6 border border-purple-100">
                    <div className="flex items-center space-x-4 mb-4">
                      <div className="p-3 bg-white rounded-lg shadow-sm text-purple-600">
                        <Building2 size={24} />
                      </div>
                      <div>
                        <h4 className="text-xl font-bold text-gray-900">{department.name}</h4>
                        <p className="text-sm text-gray-500">{department.code}</p>
                      </div>
                    </div>
                    <p className="text-gray-600 text-sm leading-relaxed">
                      You have full administrative access to manage teachers, classes, and schedules
                      within the <span className="font-semibold">{department.name}</span>{' '}
                      department.
                    </p>
                  </div>
                </div>
              )}
            </div>

            {/* Right Column - Stats/Quick Actions */}
            <div className="space-y-6">
              <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h4 className="font-semibold text-gray-900 mb-4">{t('accountSettings')}</h4>
                <div className="space-y-3">
                  <button className="w-full text-left px-4 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors flex items-center">
                    Change Password
                  </button>
                  <button className="w-full text-left px-4 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors flex items-center">
                    Notification Preferences
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
