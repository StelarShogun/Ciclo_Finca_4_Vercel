import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/app.js',
        'resources/js/admin.js',
        'resources/js/customers.js',
        'resources/js/dashboard.js',
        'resources/js/inventory.js',
        'resources/js/usuarios.js',
        'resources/js/suppliers.js',
        'resources/css/suppliers/supplier-entry.css',
      ],
      refresh: true,
    }),
  ],
})
