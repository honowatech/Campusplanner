import { createFileRoute } from '@tanstack/react-router';
import { UserProfile } from '@/src/components/UserProfile';
import { useAuth } from '@/src/auth';
import { useDepartments } from '@/src/hooks/useDepartments';

export const Route = createFileRoute('/profile')({
  component: ProfilePage,
});

function ProfilePage() {
  const auth = useAuth();
  const { data: departments } = useDepartments();

  return (
    <UserProfile
      user={auth.user!}
      department={departments?.data?.find(
        (department) => department.id === auth.user?.department_id,
      )}
    />
  );
}
