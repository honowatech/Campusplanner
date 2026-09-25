import path from 'path';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { tanstackRouter } from "@tanstack/router-plugin/vite";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(() => {
    return {
      server: {
        port: 3000,
        host: "0.0.0.0",
      },
      plugins: [
        tanstackRouter({ target: "react", autoCodeSplitting: true }),
        react(),
        tailwindcss(),
      ],
      resolve: {
        alias: {
          "@": path.resolve(__dirname, "."),
        },
      },
      build: {
        rollupOptions: {
          output: {
            // Scinder les grosses dépendances pour éviter les chunks > 600 ko
            manualChunks: {
              "vendor-charts": ["recharts"],
              "vendor-query": ["@tanstack/react-query"],
            },
          },
        },
      },
    };
});
