import React, { useState } from 'react';
import { Lock, Mail, Loader2, AlertCircle } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { useFormValidation } from '@/src/hooks/useFormValidation';
import {
  loginSchema,
  registerSchema,
  REGISTRABLE_ROLES,
  RegistrableRole,
} from '@/src/schemas/auth';
import { UserRole } from '@/src/lib/types';
import type { AxiosError } from 'axios';
import { useRegister } from '@/src/hooks/useUsers';
import { UserPlus, CheckCircle2 } from 'lucide-react';

function roleLabel(role: string, t: (key: string) => string): string {
  switch (role) {
    case 'professeur':
      return t('roleProfesseur');
    case 'etudiant':
      return t('roleEtudiant');
    case 'personnel-administratif':
      return t('rolePersonnel');
    case 'responsable-departement':
      return t('roleResponsable');
    default:
      return role;
  }
}

interface LoginProps {
  onLoginWithApi: (email: string, password: string, role?: UserRole) => Promise<void>;
  apiError: string | null;
  isLoading: boolean;
}

export const Login: React.FC<LoginProps> = ({ onLoginWithApi, apiError, isLoading }) => {
  const { t } = useTranslation();
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [apiFieldErrors, setApiFieldErrors] = useState<Record<string, string[]>>({});
  const [registerSuccess, setRegisterSuccess] = useState(false);

  // Formulaire d'inscription
  const [regName, setRegName] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regPasswordConfirmation, setRegPasswordConfirmation] = useState('');
  const [regRole, setRegRole] = useState<RegistrableRole | ''>('');
  const registerMutation = useRegister();

  const {
    errors: registerErrors,
    validate: validateRegister,
    clearError: clearRegisterError,
    clearAllErrors: clearAllRegisterErrors,
  } = useFormValidation(registerSchema);

  const {
    errors: validationErrors,
    validate,
    clearError,
    clearAllErrors,
  } = useFormValidation(loginSchema);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    clearAllErrors();
    setApiFieldErrors({});

    // Validation Zod
    const result = validate({ email, password });

    if (!result.success) {
      return; // La validation a échoué, les erreurs sont affichées
    }

    // Appel API avec les données validées
    try {
      const data = result.data;
      if (!data) return;
      await onLoginWithApi(data.email, data.password);
    } catch (error) {
      // Le message générique est affiché via le contexte auth (apiError) ;
      // on extrait ici les erreurs de champs (422) pour les afficher sous les inputs.
      const axiosError = error as AxiosError<{ data?: Record<string, string[]> }>;
      const fieldErrors = axiosError.response?.data?.data;
      if (fieldErrors && typeof fieldErrors === 'object' && !Array.isArray(fieldErrors)) {
        setApiFieldErrors(fieldErrors);
      }
    }
  };

  const handleRegisterSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    clearAllRegisterErrors();
    setRegisterSuccess(false);

    const result = validateRegister({
      name: regName,
      email: regEmail,
      password: regPassword,
      password_confirmation: regPasswordConfirmation,
      requested_role: regRole === '' ? undefined : regRole,
    });

    if (!result.success || !result.data) {
      return;
    }

    try {
      await registerMutation.mutateAsync({
        ...result.data,
        requested_role: result.data.requested_role as RegistrableRole,
      });
      setRegisterSuccess(true);
      setEmail(regEmail);
      setMode('login');
    } catch {
      // Le toast d'erreur est affiché par le hook useRegister
    }
  };

  const handleEmailChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setEmail(e.target.value);
    clearError('email');
    setApiFieldErrors((prev) => ({ ...prev, email: [] }));
  };

  const handlePasswordChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setPassword(e.target.value);
    clearError('password');
    setApiFieldErrors((prev) => ({ ...prev, password: [] }));
  };

  return (
    <div className="min-h-screen bg-linear-to-br from-indigo-100 to-purple-100 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-4xl flex overflow-hidden">
        {/* Left Side - Branding */}
        <div className="w-1/2 bg-secondary p-12 hidden md:flex flex-col justify-between text-white relative">
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')] opacity-10 bg-cover bg-center"></div>
          <div className="relative z-10">
            <h1 className="text-4xl font-bold mb-2">{t('appName')}</h1>
            <p className="text-indigo-200">University Management Simplified</p>
          </div>
          <div className="relative z-10">
            <p className="text-sm text-indigo-300 opacity-80">© 2025 Campus Planner Systems</p>
          </div>
        </div>

        {/* Right Side - Login Form */}
        <div className="w-full md:w-1/2 p-8 md:p-12">
          <div className="text-center mb-8">
            <h2 className="text-2xl font-bold text-gray-900">{t('welcomeBack')}</h2>
            <p className="text-gray-500 text-sm mt-1">{t('loginSubtitle')}</p>
          </div>

          {/* Affichage des erreurs API générales */}
          {apiError && (
            <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3">
              <AlertCircle size={20} className="text-red-500 mt-0.5 shrink-0" />
              <p className="text-sm text-red-700">{apiError}</p>
            </div>
          )}

          {registerSuccess && (
            <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start space-x-3">
              <CheckCircle2 size={20} className="text-green-500 mt-0.5 shrink-0" />
              <p className="text-sm text-green-700">{t('accountPending')}</p>
            </div>
          )}

          {mode === 'login' ? (
            <form onSubmit={handleSubmit} className="space-y-6 mb-8">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Mail size={18} className="text-gray-400" />
                  </div>
                  <input
                    type="email"
                    value={email}
                    onChange={handleEmailChange}
                    disabled={isLoading}
                    className={`block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
                      validationErrors.email || apiFieldErrors.email
                        ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                    } ${isLoading ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                    placeholder="you@university.edu"
                  />
                </div>
                {(validationErrors.email || apiFieldErrors.email?.[0]) && (
                  <p className="mt-1 text-sm text-red-600">
                    {validationErrors.email || apiFieldErrors.email?.[0]}
                  </p>
                )}
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('password')}
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Lock size={18} className="text-gray-400" />
                  </div>
                  <input
                    type="password"
                    value={password}
                    onChange={handlePasswordChange}
                    disabled={isLoading}
                    className={`block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
                      validationErrors.password || apiFieldErrors.password
                        ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                    } ${isLoading ? 'bg-gray-100 cursor-not-allowed' : ''}`}
                    placeholder="••••••••"
                  />
                </div>
                {(validationErrors.password || apiFieldErrors.password?.[0]) && (
                  <p className="mt-1 text-sm text-red-600">
                    {validationErrors.password || apiFieldErrors.password?.[0]}
                  </p>
                )}
              </div>

              <button
                type="submit"
                disabled={isLoading}
                className="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isLoading ? (
                  <>
                    <Loader2 size={18} className="animate-spin mr-2" />
                    Connexion...
                  </>
                ) : (
                  t('signIn')
                )}
              </button>
            </form>
          ) : (
            <form onSubmit={handleRegisterSubmit} className="space-y-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('fullName')}
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <UserPlus size={18} className="text-gray-400" />
                  </div>
                  <input
                    type="text"
                    value={regName}
                    onChange={(e) => {
                      setRegName(e.target.value);
                      clearRegisterError('name');
                    }}
                    disabled={registerMutation.isPending}
                    className={`block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
                      registerErrors.name
                        ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                    }`}
                    placeholder="Jean Dupont"
                  />
                </div>
                {registerErrors.name && (
                  <p className="mt-1 text-sm text-red-600">{registerErrors.name}</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Mail size={18} className="text-gray-400" />
                  </div>
                  <input
                    type="email"
                    value={regEmail}
                    onChange={(e) => {
                      setRegEmail(e.target.value);
                      clearRegisterError('email');
                    }}
                    disabled={registerMutation.isPending}
                    className={`block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
                      registerErrors.email
                        ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                    }`}
                    placeholder="you@university.edu"
                  />
                </div>
                {registerErrors.email && (
                  <p className="mt-1 text-sm text-red-600">{registerErrors.email}</p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('requestedRole')}
                </label>
                <select
                  value={regRole}
                  onChange={(e) => {
                    setRegRole(e.target.value as RegistrableRole);
                    clearRegisterError('requested_role');
                  }}
                  disabled={registerMutation.isPending}
                  className={`block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
                    registerErrors.requested_role
                      ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
                      : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                  }`}
                >
                  <option value="">{t('selectRequestedRole')}</option>
                  {REGISTRABLE_ROLES.map((role) => (
                    <option key={role} value={role}>
                      {roleLabel(role, t)}
                    </option>
                  ))}
                </select>
                {registerErrors.requested_role && (
                  <p className="mt-1 text-sm text-red-600">{registerErrors.requested_role}</p>
                )}
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('password')}
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <Lock size={18} className="text-gray-400" />
                    </div>
                    <input
                      type="password"
                      value={regPassword}
                      onChange={(e) => {
                        setRegPassword(e.target.value);
                        clearRegisterError('password');
                      }}
                      disabled={registerMutation.isPending}
                      className={`block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
                        registerErrors.password
                          ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
                          : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                      }`}
                      placeholder="••••••••"
                    />
                  </div>
                  {registerErrors.password && (
                    <p className="mt-1 text-sm text-red-600">{registerErrors.password}</p>
                  )}
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    {t('confirmPassword')}
                  </label>
                  <input
                    type="password"
                    value={regPasswordConfirmation}
                    onChange={(e) => {
                      setRegPasswordConfirmation(e.target.value);
                      clearRegisterError('password_confirmation');
                    }}
                    disabled={registerMutation.isPending}
                    className={`block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
                      registerErrors.password_confirmation
                        ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                    }`}
                    placeholder="••••••••"
                  />
                  {registerErrors.password_confirmation && (
                    <p className="mt-1 text-sm text-red-600">
                      {registerErrors.password_confirmation}
                    </p>
                  )}
                </div>
              </div>

              <button
                type="submit"
                disabled={registerMutation.isPending}
                className="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {registerMutation.isPending ? (
                  <>
                    <Loader2 size={18} className="animate-spin mr-2" />
                    {t('registering')}
                  </>
                ) : (
                  t('createAccount')
                )}
              </button>

              <div className="text-center">
                <button
                  type="button"
                  onClick={() => {
                    setMode('login');
                    clearAllRegisterErrors();
                  }}
                  className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                >
                  {t('haveAccount')} {t('signIn')}
                </button>
              </div>
            </form>
          )}

          {mode === 'login' && (
            <div className="text-center">
              <button
                type="button"
                onClick={() => {
                  setMode('register');
                  setRegisterSuccess(false);
                  clearAllRegisterErrors();
                }}
                className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
              >
                {t('noAccount')} {t('createAccount')}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
