import { createFileRoute } from '@tanstack/react-router';
import { SmsManager } from '@/src/components/SmsManager';

export const Route = createFileRoute('/sms')({
  component: SmsPage,
});

function SmsPage() {
  return <SmsManager />;
}
