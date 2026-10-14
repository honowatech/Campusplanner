/*
 * Configuration lue au chargement de la page, AVANT l'application
 * (balise <script src="/config.js"> de index.html).
 *
 * Ce fichier du dépôt reste vide : en local, src/lib/constants.ts se rabat sur
 * VITE_API_BASE_URL (.env.local). Sur le serveur, hops le remplace par celui de
 * ~/sites/<domaine>/shared/dist/config.js, propre à chaque environnement :
 * la même build passe ainsi de la préproduction à la production.
 *
 *   window.HONOWA_CONFIG = { apiBaseUrl: "https://planner-api.campustrack.cm" };
 *
 * Fichier public : n'y placer aucun secret.
 */
window.HONOWA_CONFIG = window.HONOWA_CONFIG || {};
