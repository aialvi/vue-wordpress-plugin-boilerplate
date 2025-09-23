import { createApp } from 'vue';
import App from './components/App.vue';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function () {
	const mountElement = document.getElementById('aialvi-vue-admin-app');

	if (mountElement) {
		const app = createApp(App);
		app.mount('#aialvi-vue-admin-app');
	}
});
