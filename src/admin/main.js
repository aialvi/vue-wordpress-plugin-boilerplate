import { createApp } from 'vue';
import App from './components/App.vue';

// Add debugging
console.log('Vue admin script loaded!');

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - looking for mount element...');
    const mountElement = document.getElementById('aialvi-vue-admin-app');
    
    console.log('Mount element found:', mountElement);

    if (mountElement) {
        console.log('Creating Vue app...');
        const app = createApp(App);
        app.mount('#aialvi-vue-admin-app');

        console.log('Aminul\'s Vue Plugin Admin Panel initialized successfully!');
    } else {
        console.error('Mount element #aialvi-vue-admin-app not found');
        console.log('Available elements with id:', document.querySelectorAll('[id]'));
    }
});