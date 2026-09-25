<div align="center">
<img width="1200" height="475" alt="GHBanner" src="https://github.com/user-attachments/assets/0aa67016-6eaf-458a-adb2-6e31a0763ed6" />
</div>

# Run and deploy your AI Studio app

This contains everything you need to run your app locally.

View your app in AI Studio: https://ai.studio/apps/drive/1MTKv1xiAPihj6qCMTTBzyWZZGwinwsYQ

## Run Locally

**Prerequisites:**  Node.js


1. Install dependencies:
   `npm install`
2. Set the API URL in [.env.local](.env.local): `VITE_API_BASE_URL=http://localhost:8000`
3. Run the app:
   `npm run dev`

## Authentication

Authentication is a Laravel Sanctum **session cookie** (httpOnly): no token is stored in the browser. The API must therefore be reached from the same site as the front-end, with the same host name on both sides:

- use `http://localhost:3000` for the front and `http://localhost:8000` for the API (mixing `localhost` and `127.0.0.1` breaks the cookie);
- in production, serve both on subdomains of the same domain (`planner.campustrack.cm` and `planner-api.campustrack.cm`) and set `SESSION_DOMAIN=.campustrack.cm` on the API, otherwise the front-end cannot read the `XSRF-TOKEN` cookie.

`GET /api/csrf-cookie` is called automatically before the first write, and a 419 (expired CSRF token) is retried once.

The Gemini key lives in the **API** `.env` (`GEMINI_API_KEY`); the front-end calls the server-side proxy `POST /api/ai/generate` and never sees the key.
