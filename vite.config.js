import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

const vitePort = Number(process.env.VITE_PORT) || 5173

export default defineConfig({
  plugins: [
    laravel({
      detectTls: false,
      input: [
        'resources/js/admin/admin.js',
        'resources/css/admin.css',
        'resources/js/admin/dashboard.js',
        'resources/js/admin/inventory.js',

        'resources/js/admin/sales.js',
        'resources/css/sales/sales.css',

        'resources/js/admin/suppliers.js',
        'resources/css/suppliers/suppliers.css',

        // Clients
        'resources/css/client/clients-page.css',
        'resources/css/client/clients-users.css',
        'resources/js/client/clients-page.js',
        'resources/js/client/clients-users.js',
      ],
      refresh: true,
    }),
  ],
  server: {
    host: '0.0.0.0', // Clave para docker, permite conexiones desde fuera del contenedor
    port: vitePort,
    strictPort: true,
    hmr: {
      host: 'localhost', // Navegador accede desde tu máquina
    },
  },
})