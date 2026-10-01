import { createFileRoute } from '@tanstack/react-router';
import { Dashboard } from '@/src/components/Dashboard';
import { dashboardService } from '@/src/services/dashboardService';
import { useAuth } from '@/src/auth';
import { useTeachers } from '@/src/hooks/useTeachers';
import { useCourses } from '@/src/hooks/useCourses';
import { useEffect } from 'react';
import { toast } from 'sonner';

export const Route = createFileRoute('/')({
  component: DashboardPage,
  loader: async () => {
    try {
      return await dashboardService.getOverview();
    } catch (error) {
      console.error('Failed to load dashboard:', error);
      toast.error('Impossible de charger le tableau de bord. Veuillez réessayer.');
      return null;
    }
  },
});

function DashboardPage() {
  const data = Route.useLoaderData();
  const auth = useAuth();
  const { data: teachers } = useTeachers();
  const { data: courses } = useCourses();

  useEffect(() => {
    const showToast = localStorage.getItem('show-login-toast');
    if (showToast === 'true') {
      toast.success('Connexion réussie');
      localStorage.removeItem('show-login-toast');
    }
  }, []);

  return (
    <Dashboard
      initialData={data ?? undefined}
      teachers={teachers?.data ?? []}
      courses={courses?.data ?? []}
      user={auth.user}
    />
  );
}
