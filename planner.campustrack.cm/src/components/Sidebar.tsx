import type React from 'react';
import { Link, useLocation } from '@tanstack/react-router';
import {
  LayoutDashboard,
  Users,
  Calendar,
  BookOpen,
  Building,
  Building2,
  Shapes,
  UserCircle,
  Settings,
  UserCog,
  FlaskConical,
  CreditCard,
  MessageSquare,
  ShieldCheck,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { useAuth } from '@/src/auth';
import { canAccessRoute } from '@/src/utils/routePermissions';
import logoURL from '../public/campus track logo icon.png';

interface SidebarProps {
  isMobileOpen: boolean;
  setIsMobileOpen: (open: boolean) => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ isMobileOpen, setIsMobileOpen }) => {
  const { t } = useTranslation();
  const { user } = useAuth();
  const location = useLocation();

  const allMenuItems = [
    {
      id: 'dashboard',
      path: '/',
      label: t('dashboard'),
      icon: LayoutDashboard,
    },
    {
      id: 'departments',
      path: '/departments',
      label: t('departments'),
      icon: Building2,
    },
    { id: 'classes', path: '/classes', label: t('classes'), icon: Shapes },
    { id: 'teachers', path: '/teachers', label: t('teachers'), icon: Users },
    { id: 'students', path: '/students', label: t('students'), icon: Users },
    { id: 'users', path: '/users', label: t('users'), icon: UserCog },
    { id: 'courses', path: '/courses', label: t('courses'), icon: BookOpen },
    { id: 'rooms', path: '/rooms', label: t('rooms'), icon: Building },
    {
      id: 'timetables',
      path: '/timetables',
      label: t('timetables'),
      icon: Calendar,
    },
    { id: 'settings', path: '/settings', label: t('settings'), icon: Settings },
    { id: 'billing', path: '/billing', label: t('billing'), icon: CreditCard },
    { id: 'sms', path: '/sms', label: t('sms'), icon: MessageSquare },
    { id: 'administrateur', path: '/admin', label: t('adminConsole'), icon: ShieldCheck },
    { id: 'demo-mode', path: '/demo-mode', label: t('demoMode'), icon: FlaskConical },
    { id: 'profile', path: '/profile', label: t('profile'), icon: UserCircle },
  ];

  // Masque les entrées auxquelles l'utilisateur n'a pas accès (miroir des
  // permissions viewAny côté API).
  const menuItems = allMenuItems.filter((item) => canAccessRoute(user ?? null, item.path));

  const isActive = (path: string) => {
    if (path === '/') {
      return location.pathname === '/';
    }
    return location.pathname === path;
  };

  return (
    <>
      {/* Mobile Overlay */}
      {isMobileOpen && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
          onClick={() => setIsMobileOpen(false)}
        />
      )}

      {/* Sidebar Container */}
      <aside
        className={`
        fixed top-0 left-0 z-30 h-screen w-64 bg-primary text-white transform transition-transform duration-300 ease-in-out
        lg:translate-x-0 lg:static lg:inset-0
        ${isMobileOpen ? 'translate-x-0' : '-translate-x-full'}
      `}
      >
        <div className="flex items-center justify-center w-full h-22 bg-white border-r">
          <div className="flex items-center justify-between">
            <img className="size-12" src={logoURL} alt={t('appName')} />
            <span className="text-xl font-bold tracking-wider text-primary">{t('appName')}</span>
          </div>
        </div>

        <div className="p-4">
          <nav className="space-y-2">
            {menuItems.map((item) => {
              const Icon = item.icon;
              const active = isActive(item.path);
              return (
                <Link
                  key={item.id}
                  to={item.path}
                  onClick={() => setIsMobileOpen(false)}
                  className={`
                    w-full flex items-center space-x-3 px-4 py-3 rounded-lg transition-all duration-200
                    ${
                      active
                        ? 'bg-secondary text-white shadow-md'
                        : 'text-primary-foreground hover:bg-primary hover:text-white'
                    }
                  `}
                >
                  <Icon size={20} />
                  <span className="font-medium">{item.label}</span>
                </Link>
              );
            })}
          </nav>
        </div>

        <div className="absolute bottom-0 w-full p-4 bg-secondary">
          <div className="flex items-center space-x-3 text-indigo-300 text-sm">
            <div className="size-2 rounded-full bg-green-400 animate-pulse"></div>
            <span>v1.0.0 - Stable</span>
          </div>
        </div>
      </aside>
    </>
  );
};
