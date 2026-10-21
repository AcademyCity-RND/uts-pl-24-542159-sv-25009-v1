import { resolve } from 'node:path'
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

const pageRoutes: Record<string, string> = {
  '/': '/pages/index.html',
  '/login': '/pages/login.html',
  '/register': '/pages/register.html',
  '/report': '/pages/report.html',
  '/detail': '/pages/detail.html',
  '/admin': '/pages/admin.html'
}

function rewriteMpaRoute(request: { url?: string }) {
  if (!request.url) return

  const [pathname, query] = request.url.split('?')
  const page = pageRoutes[pathname]
  if (page) {
    request.url = query ? `${page}?${query}` : page
  }
}

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    {
      name: 'mpa-dev-routes',
      configureServer(server) {
        server.middlewares.use((request, _response, next) => {
          rewriteMpaRoute(request)
          next()
        })
      },
      configurePreviewServer(server) {
        server.middlewares.use((request, _response, next) => {
          rewriteMpaRoute(request)
          next()
        })
      }
    }
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      vue: 'vue/dist/vue.esm-bundler.js'
    }
  },
  server: {
    port: 5173,
    host: true
  },
  build: {
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'pages/index.html'),
        login: resolve(__dirname, 'pages/login.html'),
        register: resolve(__dirname, 'pages/register.html'),
        report: resolve(__dirname, 'pages/report.html'),
        detail: resolve(__dirname, 'pages/detail.html'),
        admin: resolve(__dirname, 'pages/admin.html')
      }
    }
  }
})
