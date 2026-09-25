import { createFileRoute } from '@tanstack/react-router';
import { SettingsManager } from '@/src/components/SettingsManager';
import { useAuth } from '@/src/auth';
import { useSettings, useUpdateSettings } from '@/src/hooks/useSettings';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';

export const Route = createFileRoute('/settings')({
  component: SettingsPage,
});

function SettingsPage() {
  const auth = useAuth();
  const { data: appSettings, isLoading } = useSettings();
  const updateSettings = useUpdateSettings();

  const handleUpdateSettings = (settings: Parameters<typeof updateSettings.mutate>[0]) => {
    updateSettings.mutate(settings);
  };

  if (isLoading || !appSettings) {
    return <LoadingSpinner fullScreen={false} />;
  }

  return (
    <SettingsManager
      settings={appSettings}
      user={auth.user!}
      onUpdateSettings={handleUpdateSettings}
    />
  );
}
