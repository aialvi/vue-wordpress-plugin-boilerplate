import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';
import store from './store';

// Debug function that shows errors in the UI instead of console
function showError(mountElement, title, message, debug = '') {
	mountElement.innerHTML = `
		<div class="notice notice-error">
			<p><strong>${title}:</strong> ${message}</p>
			${debug ? `<p><small>Debug: ${debug}</small></p>` : ''}
			<button onclick="location.reload()" class="button button-secondary">Refresh Page</button>
		</div>
	`;
}

// Function to detect and handle script conflicts
function handleScriptConflicts() {
	// Suppress specific WordPress script errors that might interfere
	const originalError = window.onerror;
	window.onerror = function (message, source, lineno, colno, error) {
		// Suppress SVG painter errors
		if (message && message.includes('svg-painter')) {
			return true; // Prevent error from bubbling
		}

		// Call original error handler if it exists
		if (originalError) {
			return originalError(message, source, lineno, colno, error);
		}

		return false;
	};
}

// Function to initialize Vue app
function initializeVueApp() {
	const mountElement = document.getElementById('aialvi-vue-admin-app');

	if (!mountElement) {
		// Can't show error if mount element doesn't exist
		return;
	}

	// Check for nonce availability
	const nonce = mountElement.dataset.nonce;

	if (!nonce) {
		showError(
			mountElement,
			'Security Error',
			'Unable to load admin panel. Please refresh the page.',
			'Missing nonce in mount element'
		);
		return;
	}

	// Check if window.aialviVuePlugin is available
	if (!window.aialviVuePlugin) {
		showError(
			mountElement,
			'Configuration Error',
			'Unable to load admin panel. Please refresh the page.',
			'window.aialviVuePlugin not loaded'
		);
		return;
	}

	try {
		// Clear the loading content
		mountElement.innerHTML = '';

		// Create and mount the Vue app
		const app = createApp(App);
		app.use(router);
		app.use(store);

		// Add global error handler for Vue
		app.config.errorHandler = (err, _instance, info) => {
			// eslint-disable-next-line no-console
			console.error('Vue error:', err, info);
			showError(
				mountElement,
				'Vue Application Error',
				'An error occurred in the admin panel.',
				err.message
			);
		};

		app.mount('#aialvi-vue-admin-app');

		// Store app instance for HMR
		if (import.meta.hot) {
			window.__VUE_APP__ = app;
		}
	} catch (error) {
		showError(
			mountElement,
			'Application Error',
			'Failed to load admin panel.',
			error.message
		);
	}
}

// Initialize conflict handling
handleScriptConflicts();

// Check if DOM is already ready
if (document.readyState === 'loading') {
	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', initializeVueApp);
} else {
	// DOM is already ready
	initializeVueApp();
}

// Accept HMR updates
if (import.meta.hot) {
	import.meta.hot.accept(['./components/App.vue'], () => {
		// eslint-disable-next-line no-console
		console.log('HMR: Reloading Vue app...');

		// Unmount current app if it exists
		if (window.__VUE_APP__) {
			window.__VUE_APP__.unmount();
		}

		// Reinitialize
		initializeVueApp();
	});
}
