import { createFileRoute } from '@tanstack/react-router';
import { AdminConsole } from '@/src/components/AdminConsole';

export const Route = createFileRoute('/admin')({
  component: AdminPage,
});

function AdminPage() {
  return <AdminConsole />;
}
