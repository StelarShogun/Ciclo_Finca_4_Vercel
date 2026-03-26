import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

const vitePort = Number(process.env.VITE_PORT) || 5173

// =======================
// ADMIN ASSETS
// =======================
const adminAssets = [
  // JS
  'resources/js/admin/admin.js',
  'resources/js/admin/dashboard.js',
  'resources/js/admin/inventory.js',
  'resources/js/admin/sales.js',
  'resources/js/admin/suppliers.js',

  // CSS
  'resources/css/admin.css',
  'resources/css/inventory.css',
  'resources/css/sales/sales.css',
  'resources/css/suppliers/suppliers.css',
]

// =======================
// CLIENT ASSETS
// =======================
const clientAssets = [
  // JS
  'resources/js/client/clients-page.js',
  'resources/js/client/clients-users.js',

  // CSS
  'resources/css/client/clients-page.css',
  'resources/css/client/clients-users.css',
]

export default defineConfig({
  plugins: [
    laravel({
      detectTls: false,
      input: [
        ...adminAssets,
        ...clientAssets,
      ],
      refresh: true,
    }),
  ],
  server: {
    host: '0.0.0.0', // Para Docker
    port: vitePort,
    strictPort: true,
    hmr: {
      host: 'localhost', // Navegador accede desde tu máquina
    },
  },
})