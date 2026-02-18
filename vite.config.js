// vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/view-controls.js',
        'resources/js/inventory.js',
      ],
      refresh: true,
    }),
  ],
})
