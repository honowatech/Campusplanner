import axios, { AxiosError, AxiosInstance, InternalAxiosRequestConfig } from 'axios';
import { API_BASE_URL } from '@/src/lib/constants';

type RetriableConfig = InternalAxiosRequestConfig & { csrfRetried?: boolean };

export const apiClient: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  // Mode SPA Sanctum : la session est portée par un cookie httpOnly
  // (withCredentials) et le jeton CSRF du cookie XSRF-TOKEN est renvoyé dans
  // l'en-tête X-XSRF-TOKEN. withXSRFToken est indispensable car l'API est sur
  // une autre origine (localhost:3000 -> localhost:8000, sous-domaines en prod)
  // : sans lui, axios n'envoie l'en-tête que sur les requêtes same-origin.
  withCredentials: true,
  withXSRFToken: true,
  // timeout: 10000,
});

const SAFE_METHODS = ['get', 'head', 'options'];
const CSRF_URL = '/api/csrf-cookie';

let csrfCookieRequest: Promise<void> | null = null;

// Bootstrap CSRF : les services qui écrivent en parallèle partagent la même
// requête, et le résultat est mis en cache jusqu'à un 419.
export function ensureCsrfCookie(force = false): Promise<void> {
  if (force) {
    csrfCookieRequest = null;
  }

  if (!csrfCookieRequest) {
    csrfCookieRequest = apiClient
      .get(CSRF_URL)
      .then(() => undefined)
      .catch((error) => {
        csrfCookieRequest = null;
        throw error;
      });
  }

  return csrfCookieRequest;
}

// Intercepteur de requête : garantit la présence du jeton CSRF avant toute
// écriture, l'API rejetant les POST/PUT/PATCH/DELETE sans X-XSRF-TOKEN.
apiClient.interceptors.request.use(
  (config) => {
    const method = (config.method ?? 'get').toLowerCase();
    if (!SAFE_METHODS.includes(method) && !config.url?.includes('csrf-cookie')) {
      return ensureCsrfCookie().then(() => config);
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// Intercepteur de réponse : rejoue une fois sur 419, purge la session sur 401
apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as RetriableConfig | undefined;
    const status = error.response?.status;
    const url = config?.url || '';

    if (status === 419 && config && !config.csrfRetried) {
      // Jeton CSRF expiré (session renouvelée entre-temps) : on le régénère
      // puis on rejoue la requête une seule fois.
      config.csrfRetried = true;
      await ensureCsrfCookie(true);
      return apiClient.request(config);
    }

    // /auth-user et les routes d'authentification gèrent déjà leur état :
    // un 401 ici vient d'une session expirée côté API.
    const isAuthRoute =
      url.includes('/login') || url.includes('/register') || url.includes('/auth-user');

    if (status === 401 && !isAuthRoute && window.location.pathname !== '/login') {
      // L'état d'auth est reconstruit au chargement depuis le cookie de session
      window.location.assign('/login');
    }

    return Promise.reject(error);
  },
);

// Helper pour extraire les erreurs de validation
export function getValidationErrors(error: AxiosError): Record<string, string[]> | null {
  if (error.response?.status === 422) {
    const data = error.response.data as { data?: Record<string, string[]> };
    return data.data || null;
  }
  return null;
}

// Helper pour obtenir le message d'erreur
export function getErrorMessage(error: AxiosError): string {
  const data = error.response?.data as { message?: string };
  return data?.message || 'Une erreur est survenue. Veuillez réessayer.';
}
