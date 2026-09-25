import { createFileRoute } from '@tanstack/react-router';
import { Login } from '@/src/components/Login';
import { useAuth } from '@/src/auth';

export const Route = createFileRoute('/login')({
  component: LoginPage,
});

function LoginPage() {
  const auth = useAuth();

  return (
    <Login
      onLoginWithApi={auth.loginWithApi}
      apiError={auth.apiError}
      isLoading={auth.isAuthenticating}
    />
  );
}
