import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  // Set the third parameter to '' to load all env regardless of the `VITE_` prefix.
  const env = loadEnv(mode, path.resolve(__dirname, '../../'), '')

  const allowedHosts = ['frontend', 'candidacy-frontend', 'localhost']
  if (env.PUBLIC_DOMAIN) {
    allowedHosts.push(env.PUBLIC_DOMAIN)
  }
  if (process.env.PUBLIC_DOMAIN) {
    allowedHosts.push(process.env.PUBLIC_DOMAIN)
  }

  return {
    plugins: [vue()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src')
      }
    },
    server: {
      host: '0.0.0.0',
      port: 3000,
      allowedHosts: allowedHosts,
      proxy: {
        '/api': {
          target: 'http://api-gateway:8080',
          changeOrigin: true
        }
      }
    }
  }
})
