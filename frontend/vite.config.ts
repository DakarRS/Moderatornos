import { defineConfig } from 'vite'
import { tanstackStart } from '@tanstack/react-start/plugin/vite'
import viteReact from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  resolve: { tsconfigPaths: true },
  // Inlining would put every country's flag in the CSS; as files, only those shown are fetched.
  build: { assetsInlineLimit: 0 },
  plugins: [tailwindcss(), tanstackStart(), viteReact()],
})
