import { createFileRoute } from '@tanstack/react-router';
import { BillingManager } from '@/src/components/BillingManager';

export const Route = createFileRoute('/billing')({
  component: BillingPage,
});

function BillingPage() {
  return <BillingManager />;
}
