import React, { useState } from 'react';
import { Link } from '@tanstack/react-router';
import { AlertCircle, CheckCircle2, KeyRound, Loader2, Lock, Mail } from 'lucide-react';
import type { AxiosError } from 'axios';
import { useTranslation } from '@/src/utils/i18n';
import { passwordService } from '@/src/services/passwordService';
import { getErrorMessage } from '@/src/services/api';

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const inputClass = (hasError: boolean) =>
  `block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
    hasError
      ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
      : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
  }`;

const buttonClass =
  'w-full flex justify-center items-center py-2.5 px-4 rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed';

/** Cadre commun des pages publiques de mot de passe (même style que la connexion). */
function PasswordCard({
  title,
  subtitle,
  children,
}: {
  title: string;
  subtitle: string;
  children: React.ReactNode;
}) {
  const { t } = useTranslation();

  return (
    <div className="min-h-screen bg-linear-to-br from-indigo-100 to-purple-100 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-8 md:p-10">
        <div className="flex justify-center mb-4">
          <div className="p-3 bg-indigo-100 rounded-full">
            <KeyRound className="text-primary" size={24} />
          </div>
        </div>
        <div className="text-center mb-6">
          <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
          <p className="text-gray-500 text-sm mt-1">{subtitle}</p>
        </div>
        {children}
        <div className="text-center mt-6">
          <Link to="/login" className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
            {t('passwordReset.backToLogin')}
          </Link>
        </div>
      </div>
    </div>
  );
}

function Banner({ tone, children }: { tone: 'error' | 'success'; children: React.ReactNode }) {
  const Icon = tone === 'error' ? AlertCircle : CheckCircle2;
  return (
    <div
      role={tone === 'error' ? 'alert' : 'status'}
      className={`mb-6 p-4 rounded-lg border flex items-start space-x-3 ${
        tone === 'error' ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'
      }`}
    >
      <Icon
        size={20}
        className={`mt-0.5 shrink-0 ${tone === 'error' ? 'text-red-500' : 'text-green-500'}`}
      />
      <div className={`text-sm ${tone === 'error' ? 'text-red-700' : 'text-green-700'}`}>
        {children}
      </div>
    </div>
  );
}

/** Demande d'un lien de réinitialisation (tous les comptes). */
export function ForgotPassword({ initialEmail = '' }: { initialEmail?: string }) {
  const { t } = useTranslation();
  const [email, setEmail] = useState(initialEmail);
  const [fieldError, setFieldError] = useState<string | null>(null);
  const [apiError, setApiError] = useState<string | null>(null);
  const [sentMessage, setSentMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setApiError(null);

    if (!EMAIL_PATTERN.test(email.trim())) {
      setFieldError(t('passwordReset.emailInvalid'));
      return;
    }

    setSubmitting(true);
    try {
      setSentMessage(await passwordService.forgot(email.trim()));
    } catch (error) {
      setApiError(getErrorMessage(error as AxiosError));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PasswordCard
      title={t('passwordReset.forgotTitle')}
      subtitle={t('passwordReset.forgotSubtitle')}
    >
      {apiError && <Banner tone="error">{apiError}</Banner>}
      {sentMessage ? (
        <Banner tone="success">
          <p>{sentMessage}</p>
          <p className="mt-1 text-green-600">{t('passwordReset.sentHint')}</p>
        </Banner>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-6" noValidate>
          <div>
            <label htmlFor="forgot-email" className="block text-sm font-medium text-gray-700 mb-1">
              {t('email')}
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Mail size={18} className="text-gray-400" />
              </div>
              <input
                id="forgot-email"
                type="email"
                autoComplete="email"
                value={email}
                onChange={(e) => {
                  setEmail(e.target.value);
                  setFieldError(null);
                }}
                disabled={submitting}
                className={inputClass(Boolean(fieldError))}
                placeholder="you@university.edu"
              />
            </div>
            {fieldError && <p className="mt-1 text-sm text-red-600">{fieldError}</p>}
          </div>
          <button type="submit" disabled={submitting} className={buttonClass}>
            {submitting ? (
              <>
                <Loader2 size={18} className="animate-spin mr-2" />
                {t('passwordReset.sending')}
              </>
            ) : (
              t('passwordReset.sendLink')
            )}
          </button>
        </form>
      )}
    </PasswordCard>
  );
}

/** Choix du nouveau mot de passe depuis le lien reçu par email. */
export function ResetPassword({ token, email }: { token?: string; email?: string }) {
  const { t } = useTranslation();
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [errors, setErrors] = useState<{ password?: string; confirmation?: string }>({});
  const [apiError, setApiError] = useState<string | null>(null);
  const [doneMessage, setDoneMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (!token || !email) {
    return (
      <PasswordCard
        title={t('passwordReset.resetTitle')}
        subtitle={t('passwordReset.resetSubtitle')}
      >
        <Banner tone="error">{t('passwordReset.invalidLink')}</Banner>
        <Link to="/forgot-password" className={buttonClass}>
          {t('passwordReset.requestNew')}
        </Link>
      </PasswordCard>
    );
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setApiError(null);

    const nextErrors: typeof errors = {};
    if (password.length < 8) nextErrors.password = t('passwordReset.passwordTooShort');
    if (password !== confirmation) nextErrors.confirmation = t('passwordReset.passwordsMismatch');
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;

    setSubmitting(true);
    try {
      setDoneMessage(
        await passwordService.reset({
          token,
          email,
          password,
          password_confirmation: confirmation,
        }),
      );
    } catch (error) {
      setApiError(getErrorMessage(error as AxiosError));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <PasswordCard title={t('passwordReset.resetTitle')} subtitle={email}>
      {apiError && (
        <Banner tone="error">
          <p>{apiError}</p>
          <Link
            to="/forgot-password"
            className="inline-block mt-1 font-medium text-red-700 underline hover:text-red-800"
          >
            {t('passwordReset.requestNew')}
          </Link>
        </Banner>
      )}
      {doneMessage ? (
        <>
          <Banner tone="success">{doneMessage}</Banner>
          <Link to="/login" className={buttonClass}>
            {t('signIn')}
          </Link>
        </>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-5" noValidate>
          <input type="email" value={email} autoComplete="username" readOnly hidden />
          {(
            [
              ['reset-password', t('passwordReset.newPassword'), password, setPassword, 'password'],
              [
                'reset-confirmation',
                t('confirmPassword'),
                confirmation,
                setConfirmation,
                'confirmation',
              ],
            ] as const
          ).map(([id, label, value, setValue, key]) => (
            <div key={id}>
              <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">
                {label}
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock size={18} className="text-gray-400" />
                </div>
                <input
                  id={id}
                  type="password"
                  autoComplete="new-password"
                  value={value}
                  onChange={(e) => {
                    setValue(e.target.value);
                    setErrors((prev) => ({ ...prev, [key]: undefined }));
                  }}
                  disabled={submitting}
                  className={inputClass(Boolean(errors[key]))}
                  placeholder="••••••••"
                />
              </div>
              {errors[key] && <p className="mt-1 text-sm text-red-600">{errors[key]}</p>}
            </div>
          ))}
          <button type="submit" disabled={submitting} className={buttonClass}>
            {submitting ? (
              <>
                <Loader2 size={18} className="animate-spin mr-2" />
                {t('passwordReset.saving')}
              </>
            ) : (
              t('passwordReset.submit')
            )}
          </button>
        </form>
      )}
    </PasswordCard>
  );
}
