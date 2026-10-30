import { Pack } from '@/src/lib/types';
import { getLocale } from '@/src/lib/locale';

export type BillingPeriod = 'monthly' | 'annual';

export function formatAmount(amount: number, currency: string): string {
  return new Intl.NumberFormat(getLocale()).format(amount) + ' ' + currency;
}

/**
 * Une formule par palier (starter, standard, premium) pour la période choisie,
 * à défaut celle disponible.
 */
export function packsForPeriod(packs: Pack[] | undefined, period: BillingPeriod): Pack[] {
  if (!packs) return [];

  const byTier = new Map<string, { monthly?: Pack; annual?: Pack }>();
  for (const pack of packs) {
    const entry = byTier.get(pack.tier) ?? {};
    entry[pack.billing_period] = pack;
    byTier.set(pack.tier, entry);
  }

  return Array.from(byTier.values())
    .map((periods) => periods[period] ?? periods.monthly ?? periods.annual)
    .filter((pack): pack is Pack => Boolean(pack));
}
