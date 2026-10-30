import { createFileRoute } from '@tanstack/react-router';
import { BillingManager } from '@/src/components/BillingManager';

export const Route = createFileRoute('/billing')({
  // `pack` : formule choisie à l'inscription, présélectionnée pour le paiement.
  validateSearch: (search: Record<string, unknown>): { pack?: number } => ({
    pack: Number(search.pack) || undefined,
  }),
  component: BillingPage,
});

function BillingPage() {
  const { pack } = Route.useSearch();

  return <BillingManager initialPackId={pack} />;
}
