import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/js/admin.js',
        'resources/js/dashboard.js',
        'resources/js/inventory.js',
        'resources/js/usuarios.js',

        // CSS Y JS DE SUPPLIERS
        'resources/js/suppliers.js',
        'resources/css/suppliers/suppliers.css',

        // CSS Y JS DE CLIENTS
        'resources/css/clients/clients.css', 
        'resources/js/clients.js',
      ],
      refresh: true,
    }),
  ],
})
