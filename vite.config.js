import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: resolve(__dirname, 'src/admin/main.js'),
      output: {
        entryFileNames: 'admin.bundle.js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: 'admin.[ext]',
        name: 'AIALVIVueAdminPlugin'
      }
    },
    manifest: false,
  },
  server: {
    port: 3000,
    cors: true,
    hmr: {
      host: 'localhost'
    }
  },
  define: {
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false
  }
});