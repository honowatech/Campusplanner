import { User, UserRole, Role, Permission, SubscriptionSummary } from '@/src/lib/types';
import React, { createContext, useContext, useState, useEffect } from 'react';
import { apiClient } from '@/src/services/api';
import { AxiosError } from 'axios';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';

type ApiUserWithRoles = {
  id: number;
  name: string;
  email: string;
  department_id?: number;
  roles?: Role[];
  permissions?: Permission[];
  features?: string[];
  subscription?: SubscriptionSummary | null;
};

function convertApiUserToUser(apiUser: ApiUserWithRoles, defaultRole: UserRole = 'etudiant'): User {
  const roles = apiUser.roles || [];
  const permissions = apiUser.permissions || [];

  let primaryRole: UserRole = defaultRole;
  if (roles.length > 0) {
    const roleNames = roles.map((r) => r.name);
    if (roleNames.includes('super-admin')) primaryRole = 'super-admin';
    else if (roleNames.includes('administrateur')) primaryRole = 'administrateur';
    else if (roleNames.includes('responsable-departement')) primaryRole = 'responsable-departement';
    else if (roleNames.includes('professeur')) primaryRole = 'professeur';
    else if (roleNames.includes('etudiant')) primaryRole = 'etudiant';
    else if (roleNames.includes('personnel-administratif')) primaryRole = 'personnel-administratif';
  }

  return {
    id: apiUser.id,
    name: apiUser.name,
    email: apiUser.email,
    role: primaryRole,
    roles: roles,
    permissions: permissions.map((permission) => permission.name),
    features: apiUser.features ?? [],
    subscription: apiUser.subscription ?? null,
    department_id: apiUser.department_id,
  };
}

export type AuthState = {
  isAuthenticated: boolean;
  user: User | null;
  isLoading: boolean;
  isAuthenticating: boolean;
  apiError: string | null;
  loginWithApi: (email: string, password: string, role?: UserRole) => Promise<void>;
  demoLogin: (role: string) => Promise<void>;
  logout: () => Promise<void>;
  clearApiError: () => void;
  refreshUser: () => Promise<void>;
};

const AuthContext = createContext<AuthState | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  // Soumission du formulaire de login en cours (distinct du chargement initial :
  // ne remplace plus toute l'application par un spinner)
  const [isAuthenticating, setIsAuthenticating] = useState(false);
  const [apiError, setApiError] = useState<string | null>(null);

  // Restaure l'état d'auth au chargement : le cookie de session httpOnly fait
  // foi, GET /api/auth-user est la seule source de vérité
  useEffect(() => {
    const initAuth = async () => {
      // Purge des anciennes sessions (jeton Bearer et utilisateur en localStorage)
      localStorage.removeItem('auth-token');
      localStorage.removeItem('api-user');
      localStorage.removeItem('campus-planner-user');

      try {
        const response = await apiClient.get('/api/auth-user');
        if (response.data.status === 'success') {
          setUser(convertApiUserToUser(response.data.data));
          setIsAuthenticated(true);
        }
      } catch {
        // Aucune session active : la page de login s'affiche
      } finally {
        setIsLoading(false);
      }
    };

    initAuth();
  }, []);

  // Show loading state while checking auth
  if (isLoading) {
    return <LoadingSpinner />;
  }

  // Login via API
  const loginWithApi = async (
    email: string,
    password: string,
    role: UserRole = 'etudiant',
  ): Promise<void> => {
    setIsAuthenticating(true);
    setApiError(null);

    try {
      const response = await apiClient.post('api/login', { email, password });

      if (response.data.status === 'success') {
        const apiUser = response.data.data;

        // Conversion ApiUser -> User
        const userData = convertApiUserToUser(apiUser, role);

        setUser(userData);
        setIsAuthenticated(true);

        localStorage.setItem('show-login-toast', 'true');
      } else {
        setApiError(response.data.message || 'Erreur de connexion');
        throw new Error(response.data.message);
      }
    } catch (error) {
      const axiosError = error as AxiosError<{ message?: string; data?: Record<string, string[]> }>;
      const errorMessage =
        axiosError.response?.data?.message || 'Erreur de connexion. Veuillez réessayer.';
      setApiError(errorMessage);
      // On relance l'erreur axios pour permettre au formulaire d'extraire
      // les erreurs de champs (422) de la réponse.
      throw axiosError;
    } finally {
      setIsAuthenticating(false);
    }
  };

  // Connexion démo : choisit un compte par rôle (sans mot de passe),
  // uniquement lorsque le mode démo est activé côté API.
  const demoLogin = async (role: string): Promise<void> => {
    setIsAuthenticating(true);
    setApiError(null);

    try {
      const response = await apiClient.post('/api/demo-login', { role });

      if (response.data.status === 'success') {
        const apiUser = response.data.data;
        setUser(convertApiUserToUser(apiUser));
        setIsAuthenticated(true);
        localStorage.setItem('show-login-toast', 'true');
      } else {
        setApiError(response.data.message || 'Erreur de connexion');
        throw new Error(response.data.message);
      }
    } catch (error) {
      const axiosError = error as AxiosError<{ message?: string }>;
      const errorMessage =
        axiosError.response?.data?.message || 'Erreur de connexion. Veuillez réessayer.';
      setApiError(errorMessage);
      throw axiosError;
    } finally {
      setIsAuthenticating(false);
    }
  };

  const logout = async (): Promise<void> => {
    try {
      await apiClient.post('/api/logout', {});
    } catch (error) {
      // Même si le logout API échoue, on nettoie quand même l'état local
      console.error('Logout API error:', error);
    }

    // Reset state
    setUser(null);
    setIsAuthenticated(false);
    setApiError(null);
  };

  const clearApiError = () => {
    setApiError(null);
  };

  const refreshUser = async (): Promise<void> => {
    try {
      const response = await apiClient.get('/api/auth-user');
      if (response.data.status === 'success') {
        const userData = convertApiUserToUser(response.data.data, user?.role);
        setUser(userData);
      }
    } catch (error) {
      console.error('Failed to refresh user:', error);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        isAuthenticated,
        user,
        isLoading,
        isAuthenticating,
        apiError,
        loginWithApi,
        demoLogin,
        logout,
        clearApiError,
        refreshUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
