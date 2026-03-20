import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

const vitePort = Number(process.env.VITE_PORT) || 5173

export default defineConfig({
  plugins: [
    laravel({
      detectTls: false,
      input: [
        'resources/js/admin.js',
        'resources/js/pages/admin.js',
        'resources/css/admin.css',
        'resources/js/dashboard.js',
        'resources/js/inventory.js',
        'resources/css/inventory.css',
        'resources/js/usuarios.js',

        // CSS Y JS DE SALES
        'resources/js/sales.js',
        'resources/css/sales/sales.css',

        // CSS Y JS DE SUPPLIERS
        'resources/js/suppliers.js',
        'resources/css/suppliers/suppliers.css',

        // CSS DE SALES
        'resources/css/sales.css',

        // CSS Y JS DE CLIENTS
        'resources/css/clients/clients.css',
        'resources/css/clients-users.css',
        'resources/js/clients.js',
      ],
      refresh: true,
    }),
  ],
  server: {
    host: 'localhost',
    port: vitePort,
    strictPort: false,
  },
})
