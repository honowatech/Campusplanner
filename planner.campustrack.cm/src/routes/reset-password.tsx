import { createFileRoute } from '@tanstack/react-router';
import { ResetPassword } from '@/src/components/PasswordReset';

export const Route = createFileRoute('/reset-password')({
  // Lien reçu par email : /reset-password?token=…&email=…
  validateSearch: (search: Record<string, unknown>): { token?: string; email?: string } => ({
    token: typeof search.token === 'string' ? search.token : undefined,
    email: typeof search.email === 'string' ? search.email : undefined,
  }),
  component: ResetPasswordPage,
});

function ResetPasswordPage() {
  const { token, email } = Route.useSearch();

  return <ResetPassword token={token} email={email} />;
}
