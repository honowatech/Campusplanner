export type Language = 'en' | 'fr';

export const STORAGE_KEY = 'campus-planner-language';

/**
 * Résout la langue active sans dépendre du contexte React :
 * valeur persistée -> langue du navigateur -> anglais.
 */
export function getStoredLanguage(): Language {
  const stored = localStorage.getItem(STORAGE_KEY);
  if (stored === 'en' || stored === 'fr') {
    return stored;
  }

  return navigator.language?.toLowerCase().startsWith('fr') ? 'fr' : 'en';
}

/**
 * Locale BCP 47 correspondant à la langue active (pour Intl / toLocaleDateString).
 */
export function getLocale(): string {
  return getStoredLanguage() === 'fr' ? 'fr-FR' : 'en-GB';
}
