import { createApp } from 'vue';
import App from './components/App.vue';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function () {
	const mountElements = document.querySelectorAll('.aialvi-vue-public-app');

	mountElements.forEach(element => {
		const app = createApp(App);
		app.mount(element);
	});
});
