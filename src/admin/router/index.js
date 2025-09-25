import { createRouter, createWebHashHistory } from 'vue-router';
import TableTab from '../components/TableTab.vue';
import GraphTab from '../components/GraphTab.vue';
import SettingsTab from '../components/SettingsTab.vue';

const routes = [
	{
		path: '/',
		redirect: () => {
			// Get tab from URL parameter on initial load
			const urlParams = new URLSearchParams(window.location.search);
			const tab = urlParams.get('tab');
			const validTabs = ['table', 'graph', 'settings'];

			if (tab && validTabs.includes(tab)) {
				return `/${tab}`;
			}
			return '/table';
		},
	},
	{
		path: '/table',
		name: 'table',
		component: TableTab,
		meta: { title: 'Table' },
	},
	{
		path: '/graph',
		name: 'graph',
		component: GraphTab,
		meta: { title: 'Graph' },
	},
	{
		path: '/settings',
		name: 'settings',
		component: SettingsTab,
		meta: { title: 'Settings' },
	},
];

const router = createRouter({
	history: createWebHashHistory(),
	routes,
});

// Function to update URL parameters
const updateUrlParam = tabName => {
	const url = new URL(window.location.href);
	url.searchParams.set('tab', tabName);
	window.history.replaceState({}, '', url.toString());
};

// Update URL when route changes
router.afterEach(to => {
	if (to.name && ['table', 'graph', 'settings'].includes(to.name)) {
		updateUrlParam(to.name);

		// Store last visited tab in localStorage for refresh handling
		localStorage.setItem('aialvi_vue_last_tab', to.name);
	}
});

export default router;
