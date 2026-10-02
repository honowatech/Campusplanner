import { createFileRoute } from '@tanstack/react-router';
import { DemoModeManager } from '@/src/components/DemoModeManager';
import { useDemoMode, useToggleDemoMode } from '@/src/hooks/useDemoMode';

export const Route = createFileRoute('/demo-mode')({
  component: DemoModePage,
});

function DemoModePage() {
  const { data: status, isLoading } = useDemoMode();
  const toggleDemoMode = useToggleDemoMode();

  return (
    <DemoModeManager
      status={status}
      isLoading={isLoading}
      onToggle={(enabled) => toggleDemoMode.mutate(enabled)}
    />
  );
}
