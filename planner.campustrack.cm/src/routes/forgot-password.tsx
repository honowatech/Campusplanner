import { createFileRoute } from '@tanstack/react-router';
import { ForgotPassword } from '@/src/components/PasswordReset';

export const Route = createFileRoute('/forgot-password')({
  // `email` : prérempli depuis la page de connexion.
  validateSearch: (search: Record<string, unknown>): { email?: string } => ({
    email: typeof search.email === 'string' ? search.email : undefined,
  }),
  component: ForgotPasswordPage,
});

function ForgotPasswordPage() {
  const { email } = Route.useSearch();

  return <ForgotPassword initialEmail={email} />;
}
