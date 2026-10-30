import { createFileRoute } from '@tanstack/react-router';
import { SchoolSignup } from '@/src/components/SchoolSignup';

export const Route = createFileRoute('/signup')({
  validateSearch: (search: Record<string, unknown>): { pack?: number } => ({
    pack: Number(search.pack) || undefined,
  }),
  component: SignupPage,
});

function SignupPage() {
  const { pack } = Route.useSearch();

  return <SchoolSignup initialPackId={pack} />;
}
