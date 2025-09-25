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
				name: 'AIALVIVueAdminPlugin',
			},
		},
		manifest: true,
	},
	server: {
		port: 3000,
		host: true,
		cors: true,
		origin: 'http://localhost:3000',
		hmr: {
			port: 3000,
			host: 'localhost',
		},
		fs: {
			allow: ['..'],
		},
	},
	define: {
		__VUE_OPTIONS_API__: true,
		__VUE_PROD_DEVTOOLS__: false,
	},
});
