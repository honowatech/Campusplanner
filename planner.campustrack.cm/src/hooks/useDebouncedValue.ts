import { useEffect, useState } from 'react';

/**
 * Retourne `value` retardée de `delay` ms après la dernière modification.
 * Évite de déclencher un appel API à chaque frappe lors d'une recherche.
 */
export function useDebouncedValue<T>(value: T, delay = 350): T {
  const [debounced, setDebounced] = useState(value);

  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay);
    return () => clearTimeout(timer);
  }, [value, delay]);

  return debounced;
}
