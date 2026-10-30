import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import {
  AlertCircle,
  Building2,
  Check,
  Loader2,
  Lock,
  Mail,
  Phone,
  Sparkles,
  UserRound,
} from 'lucide-react';
import type { AxiosError } from 'axios';
import { useTranslation } from '@/src/utils/i18n';
import { useAuth } from '@/src/auth';
import { usePacks } from '@/src/hooks/useSubscription';
import { useFormValidation } from '@/src/hooks/useFormValidation';
import { schoolSignupSchema, SchoolSignupFormData } from '@/src/schemas/auth';
import { BillingPeriod, formatAmount, packsForPeriod } from '@/src/lib/packs';
import { getErrorMessage } from '@/src/services/api';
import { toast } from 'sonner';

type FieldName = keyof SchoolSignupFormData;

// Formule à payer après l'inscription. Gardée hors du composant : il est
// remonté pendant la transition vers /billing (changement de layout), et la
// redirection doit survivre à ce remontage.
let postSignupPackId: number | null = null;

/**
 * Inscription libre-service d'une école : création de l'espace et du compte
 * administrateur, choix de la formule, puis redirection vers le paiement.
 * Aucune validation par le super-admin n'est nécessaire.
 */
export function SchoolSignup({ initialPackId }: { initialPackId?: number }) {
  const { t } = useTranslation();
  const auth = useAuth();
  const navigate = useNavigate();
  const { data: packs, isLoading: loadingPacks } = usePacks();
  const { errors, validate, clearError } = useFormValidation(schoolSignupSchema);

  const [form, setForm] = useState<SchoolSignupFormData>({
    name: '',
    admin_name: '',
    admin_email: '',
    phone: '',
    admin_password: '',
    admin_password_confirmation: '',
  });
  const [period, setPeriod] = useState<BillingPeriod>('monthly');
  const [packId, setPackId] = useState<number | null>(initialPackId ?? null);
  const [submitting, setSubmitting] = useState(false);
  const [apiError, setApiError] = useState<string | null>(null);
  const [apiFieldErrors, setApiFieldErrors] = useState<Record<string, string[]>>({});

  // Formule passée dans l'URL : aligne la période affichée sur celle-ci.
  useEffect(() => {
    const initial = packs?.find((pack) => pack.id === initialPackId);
    if (initial) setPeriod(initial.billing_period);
  }, [packs, initialPackId]);

  // Redirection une fois la session prise en compte par le contexte d'auth
  // (naviguer avant ferait intervenir le garde des pages protégées) :
  // paiement de la formule choisie, sinon tableau de bord. Un utilisateur déjà
  // connecté qui ouvre /signup retourne aussi à l'accueil.
  useEffect(() => {
    if (!auth.isAuthenticated) return;

    const pack = postSignupPackId;
    if (pack) {
      navigate({ to: '/billing', search: { pack } });
    } else {
      navigate({ to: '/' });
    }
  }, [auth.isAuthenticated, navigate]);

  const visiblePacks = packsForPeriod(packs, period);

  const update = (field: FieldName) => (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm((prev) => ({ ...prev, [field]: e.target.value }));
    clearError(field);
    setApiFieldErrors((prev) => ({ ...prev, [field]: [] }));
  };

  const fieldError = (field: FieldName, apiField: string = field): string | undefined =>
    errors[field] || apiFieldErrors[apiField]?.[0];

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setApiError(null);
    setApiFieldErrors({});

    const result = validate(form);
    if (!result.success || !result.data) return;

    setSubmitting(true);
    // Positionné avant l'ouverture de la session, que l'effet de redirection
    // peut suivre immédiatement.
    postSignupPackId = packId;
    try {
      await auth.signupSchool({
        ...result.data,
        phone: result.data.phone || undefined,
        pack_id: packId ?? undefined,
      });
      toast.success(t('signup.success'));
    } catch (error) {
      postSignupPackId = null;
      const axiosError = error as AxiosError<{ data?: Record<string, string[]> }>;
      const fieldErrors = axiosError.response?.data?.data;
      if (axiosError.response?.status === 422 && fieldErrors && !Array.isArray(fieldErrors)) {
        setApiFieldErrors(fieldErrors);
      }
      setApiError(getErrorMessage(axiosError as AxiosError));
    } finally {
      setSubmitting(false);
    }
  };

  const inputClass = (hasError: boolean) =>
    `block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-offset-2 sm:text-sm transition-colors ${
      hasError
        ? 'border-red-300 focus:ring-red-500 focus:border-red-500'
        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
    }`;

  const renderField = (
    field: FieldName,
    label: string,
    icon: React.ReactNode,
    props: React.InputHTMLAttributes<HTMLInputElement> = {},
    hint?: string,
  ) => {
    const error = fieldError(field);
    return (
      <div>
        <label htmlFor={`signup-${field}`} className="block text-sm font-medium text-gray-700 mb-1">
          {label}
        </label>
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
            {icon}
          </div>
          <input
            id={`signup-${field}`}
            value={form[field] ?? ''}
            onChange={update(field)}
            disabled={submitting}
            className={inputClass(Boolean(error))}
            {...props}
          />
        </div>
        {error ? (
          <p className="mt-1 text-sm text-red-600">{error}</p>
        ) : (
          hint && <p className="mt-1 text-xs text-gray-500">{hint}</p>
        )}
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-linear-to-br from-indigo-100 to-purple-100 flex items-center justify-center p-4">
      <form
        onSubmit={handleSubmit}
        className="bg-white rounded-2xl shadow-xl w-full max-w-5xl overflow-hidden"
        noValidate
      >
        <div className="bg-secondary text-white px-8 py-6">
          <h1 className="text-2xl font-bold">{t('signup.title')}</h1>
          <p className="text-indigo-200 text-sm mt-1">{t('signup.subtitle')}</p>
          <ol className="flex flex-wrap gap-x-6 gap-y-1 mt-4 text-xs font-semibold text-indigo-100">
            {['signup.step1', 'signup.step2', 'signup.step3'].map((key, index) => (
              <li key={key} className="flex items-center gap-2">
                <span className="size-5 rounded-full bg-white/20 flex items-center justify-center">
                  {index + 1}
                </span>
                {t(key)}
              </li>
            ))}
          </ol>
        </div>

        <div className="p-6 md:p-8 space-y-8">
          {apiError && (
            <div className="p-4 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3">
              <AlertCircle size={20} className="text-red-500 mt-0.5 shrink-0" />
              <p className="text-sm text-red-700">{apiError}</p>
            </div>
          )}

          {/* 1. L'école et son administrateur */}
          <section className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {renderField('name', t('signup.schoolName'), <Building2 size={18} />, {
              placeholder: 'Institut Universitaire…',
              autoComplete: 'organization',
            })}
            {renderField('admin_name', t('signup.adminName'), <UserRound size={18} />, {
              placeholder: 'Jean Dupont',
              autoComplete: 'name',
            })}
            {renderField('admin_email', t('signup.adminEmail'), <Mail size={18} />, {
              type: 'email',
              placeholder: 'direction@ecole.cm',
              autoComplete: 'email',
            })}
            {renderField(
              'phone',
              t('signup.phone'),
              <Phone size={18} />,
              { type: 'tel', placeholder: '6XX XX XX XX', autoComplete: 'tel' },
              t('signup.phoneHint'),
            )}
            {renderField('admin_password', t('password'), <Lock size={18} />, {
              type: 'password',
              placeholder: '••••••••',
              autoComplete: 'new-password',
            })}
            {renderField('admin_password_confirmation', t('confirmPassword'), <Lock size={18} />, {
              type: 'password',
              placeholder: '••••••••',
              autoComplete: 'new-password',
            })}
          </section>

          {/* 2. La formule */}
          <section>
            <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
              <h2 className="text-lg font-bold text-gray-900">{t('signup.choosePlan')}</h2>
              <div className="flex rounded-lg border border-gray-200 overflow-hidden">
                {(['monthly', 'annual'] as const).map((value) => (
                  <button
                    key={value}
                    type="button"
                    onClick={() => {
                      setPeriod(value);
                      // Conserve le palier choisi en changeant de période.
                      const tier = packs?.find((pack) => pack.id === packId)?.tier;
                      const match = packs?.find(
                        (pack) => pack.tier === tier && pack.billing_period === value,
                      );
                      if (match) setPackId(match.id);
                    }}
                    className={`px-4 py-1.5 text-sm font-medium transition-colors ${
                      period === value ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50'
                    }`}
                  >
                    {t(`signup.${value}`)}
                  </button>
                ))}
              </div>
            </div>

            {loadingPacks ? (
              <div className="flex justify-center py-8">
                <Loader2 className="animate-spin text-primary" />
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <PlanCard
                  selected={packId === null}
                  onSelect={() => setPackId(null)}
                  title={t('signup.trialOnly')}
                  price={<Sparkles className="text-amber-500" size={28} />}
                  detail={t('signup.trialHint')}
                />
                {visiblePacks.map((pack) => (
                  <PlanCard
                    key={pack.id}
                    selected={packId === pack.id}
                    onSelect={() => setPackId(pack.id)}
                    highlight={pack.tier === 'standard'}
                    title={pack.name}
                    price={
                      <>
                        <span className="block text-2xl font-bold text-gray-900 whitespace-nowrap">
                          {formatAmount(pack.price, pack.currency)}
                        </span>
                        <span className="block text-xs text-gray-500">
                          {t(
                            pack.billing_period === 'annual' ? 'signup.perYear' : 'signup.perMonth',
                          )}
                        </span>
                      </>
                    }
                    detail={`${pack.features.length} ${t('signup.featuresIncluded')}`}
                  />
                ))}
              </div>
            )}
          </section>

          <div className="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4 pt-6 border-t border-gray-100">
            <p className="text-sm text-gray-600">
              {t('signup.haveAccount')}{' '}
              <Link to="/login" className="text-indigo-600 hover:text-indigo-800 font-medium">
                {t('signIn')}
              </Link>
            </p>
            <button
              type="submit"
              disabled={submitting}
              className="flex justify-center items-center py-2.5 px-6 rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {submitting ? (
                <>
                  <Loader2 size={18} className="animate-spin mr-2" />
                  {t('signup.submitting')}
                </>
              ) : packId ? (
                t('signup.submitWithPlan')
              ) : (
                t('signup.submit')
              )}
            </button>
          </div>
        </div>
      </form>
    </div>
  );
}

function PlanCard({
  selected,
  onSelect,
  title,
  price,
  detail,
  highlight = false,
}: {
  selected: boolean;
  onSelect: () => void;
  title: string;
  price: React.ReactNode;
  detail: string;
  highlight?: boolean;
}) {
  return (
    <button
      type="button"
      onClick={onSelect}
      aria-pressed={selected}
      className={`relative rounded-xl border p-5 text-left transition-all ${
        selected
          ? 'border-primary ring-2 ring-primary/20 bg-indigo-50/50'
          : highlight
            ? 'border-indigo-200 hover:border-indigo-300'
            : 'border-gray-200 hover:border-gray-300'
      }`}
    >
      <p className="font-bold text-gray-900 capitalize">{title}</p>
      <div className="mt-2 min-h-12">{price}</div>
      <p className="text-sm text-gray-600 mt-3">{detail}</p>
      {selected && (
        <span className="absolute top-3 right-3 text-primary">
          <Check size={20} />
        </span>
      )}
    </button>
  );
}
