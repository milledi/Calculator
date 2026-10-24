import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { fileURLToPath, URL } from "node:url";

export default defineConfig({
  plugins: [react()],
  server: {
    // В Docker на Windows/macOS события об изменении файлов не доходят до контейнера,
    // поэтому там Vite опрашивает диск сам (VITE_USE_POLLING в docker-compose.yml)
    watch: { usePolling: process.env.VITE_USE_POLLING === "true", interval: 300 },
    // API и админку отдаёт PHP-бекенд (server/)
    proxy: {
      "/api": process.env.VITE_API_PROXY || "http://localhost:4000",
      "/admin": process.env.VITE_API_PROXY || "http://localhost:4000",
    },
  },
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },
});
