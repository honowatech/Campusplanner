import { useState, useEffect } from 'react';
import { createRootRoute, Outlet, Link, useNavigate, useLocation } from '@tanstack/react-router';
import { TanStackRouterDevtools } from '@tanstack/router-devtools';
import { Sidebar } from '@/src/components/Sidebar';
import { Menu, Bell, Globe, LogOut } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { useAuth } from '@/src/auth';
import { canAccessRoute } from '@/src/utils/routePermissions';
import { Toaster } from '@/src/components/ui/sonner';

export const Route = createRootRoute({
  component: RootComponent,
});

function RootComponent() {
  const navigate = useNavigate();
  const location = useLocation();
  const { t, language, setLanguage } = useTranslation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const auth = useAuth();
  const isDev = import.meta.env.DEV;

  const toggleLanguage = () => setLanguage(language === 'en' ? 'fr' : 'en');

  useEffect(() => {
    const publicRoutes = ['/login'];
    const isPublicRoute = publicRoutes.includes(location.pathname);

    if (!auth.isAuthenticated && !isPublicRoute) {
      navigate({ to: '/login' });
      return;
    }

    if (auth.isAuthenticated && location.pathname === '/login') {
      navigate({ to: '/' });
      return;
    }

    // Garde de rôle : redirige vers le tableau de bord si la permission
    // requise pour la page est absente (l'API renvoie de toute façon 403).
    if (auth.isAuthenticated && !canAccessRoute(auth.user, location.pathname)) {
      navigate({ to: '/' });
    }
  }, [auth.isAuthenticated, auth.user, location.pathname, navigate]);

  if (location.pathname === '/login' && !auth.isAuthenticated) {
    return (
      <>
        <Outlet />
        <Toaster position="bottom-right" />
        {isDev && <TanStackRouterDevtools />}
      </>
    );
  }

  if (!auth.isAuthenticated) {
    return null;
  }

  return (
    <div className="flex h-screen bg-gray-50 font-sans overflow-hidden">
      <Sidebar isMobileOpen={isMobileMenuOpen} setIsMobileOpen={setIsMobileMenuOpen} />

      <div className="flex-1 flex flex-col min-w-0">
        <header className="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between sticky top-0 z-20">
          <div className="flex items-center">
            <button
              type="button"
              onClick={() => setIsMobileMenuOpen(true)}
              className="p-2 mr-4 text-gray-600 hover:bg-gray-100 rounded-lg lg:hidden"
            >
              <Menu size={24} />
            </button>
            <h1 className="text-2xl font-bold text-gray-800 capitalize">
              {(() => {
                const rawTitle = location.pathname.slice(1) || 'dashboard';
                const translated = t(rawTitle);
                // Clé absente du dictionnaire -> titre lisible depuis le chemin
                return translated === rawTitle ? rawTitle.replace(/[-/]/g, ' ') : translated;
              })()}
            </h1>
          </div>

          <div className="flex items-center space-x-6">
            <button
              onClick={toggleLanguage}
              className="flex items-center space-x-1.5 px-3 py-1.5 rounded-md bg-indigo-50 text-primary hover:bg-indigo-100 transition-colors text-sm font-semibold"
              type="button"
            >
              <Globe size={16} />
              <span>{language.toUpperCase()}</span>
            </button>

            <div className="flex items-center space-x-3 text-gray-400">
              <button type="button" className="hover:text-secondary transition-colors relative">
                <Bell size={20} />
                <span className="absolute top-0 right-0 block size-2 rounded-full ring-2 ring-white bg-red-400 transform translate-x-1/2 -translate-y-1/2"></span>
              </button>
            </div>

            <div className="h-8 w-px bg-gray-200 mx-2"></div>

            <div className="flex items-center space-x-3 pl-2 cursor-pointer hover:bg-gray-50 p-2 rounded-xl transition-colors group relative">
              <div className="text-right hidden md:block">
                <p className="text-sm font-bold text-gray-900">{auth.user?.name}</p>
                <p className="text-xs text-gray-500">
                  {auth.user?.role === 'admin' ? t('adminRole') : t('hodRole')}
                </p>
              </div>
              <div
                className={`size-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm border-2 border-white ${auth.user?.role === 'admin' ? 'bg-linear-to-tr from-primary to-secondary' : 'bg-linear-to-tr from-secondary to-secondary'}`}
              >
                {auth.user?.name ? auth.user?.name.charAt(0) : "U"}
              </div>

              <div className="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg py-1 border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                <Link
                  to="/profile"
                  className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  {t('profile')}
                </Link>
                <Link
                  to="/settings"
                  className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                  {t('settings')}
                </Link>
                <button
                  type="button"
                  onClick={auth.logout}
                  className="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center"
                >
                  <LogOut size={14} className="mr-2" />
                  {t('logout')}
                </button>
              </div>
            </div>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto p-4 lg:p-8 scroll-smooth bg-gray-50/50">
          <div className="max-w-7xl mx-auto min-h-full">
            <Outlet />
            <Toaster position="bottom-right" />
          </div>
        </main>
      </div>

      {isDev && <TanStackRouterDevtools />}
    </div>
  );
}
