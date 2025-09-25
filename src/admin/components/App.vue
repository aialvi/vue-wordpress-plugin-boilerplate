<template>
	<div class="aialvi-admin-app" role="application">
		<header class="admin-header">
			<div class="header-top">
				<h1 class="plugin-title">
					{{ __("Aminul's Page Ranks", 'aialvi-page-ranks') }}
				</h1>
			</div>
			<p class="description">
				{{
					__(
						'A powerful WordPress plugin built with Vue.js for data visualization and management',
						'aialvi-page-ranks'
					)
				}}
			</p>
		</header>

		<!-- Tab Navigation in WordPress admin style -->
		<nav
			class="nav-tab-wrapper wp-clearfix"
			role="tablist"
			:aria-label="__('Plugin navigation', 'aialvi-page-ranks')"
		>
			<router-link
				v-for="tab in tabs"
				:key="tab.name"
				:to="{ name: tab.name }"
				class="nav-tab"
				:class="{ 'nav-tab-active': $route.name === tab.name }"
				:aria-current="$route.name === tab.name ? 'page' : null"
				:aria-selected="$route.name === tab.name"
				role="tab"
				:aria-controls="`${tab.name}-panel`"
				:tabindex="$route.name === tab.name ? 0 : -1"
				@keydown="handleTabKeydown($event, tab)"
			>
				<span :class="tab.icon" :aria-hidden="true" />
				{{ tab.label }}
			</router-link>
		</nav>

		<!-- Tab Content Area -->
		<main class="admin-content">
			<!-- Global Loading State -->
			<div
				v-if="globalLoading"
				class="loading-wrapper"
				role="status"
				:aria-label="__('Loading plugin content', 'aialvi-page-ranks')"
			>
				<div class="spinner is-active" aria-hidden="true" />
				<p>{{ __('Loading...', 'aialvi-page-ranks') }}</p>
			</div>

			<!-- Global Error State -->
			<div
				v-else-if="globalError"
				class="notice notice-error"
				role="alert"
				:aria-label="__('Error notification', 'aialvi-page-ranks')"
			>
				<p>
					<strong>{{ __('Error:', 'aialvi-page-ranks') }}</strong>
					{{ globalError }}
				</p>
				<button
					type="button"
					class="button button-secondary"
					:aria-describedby="globalError ? 'error-description' : null"
					@click="retryInitialization"
				>
					{{ __('Retry', 'aialvi-page-ranks') }}
				</button>
				<span id="error-description" class="screen-reader-text">
					{{
						__(
							'Click to retry loading the plugin data',
							'aialvi-page-ranks'
						)
					}}
				</span>
			</div>

			<!-- Tab Content -->
			<div
				v-else
				:id="`${$route.name}-panel`"
				class="tab-content"
				role="tabpanel"
				:aria-labelledby="`${$route.name}-tab`"
			>
				<router-view />
			</div>
		</main>
	</div>
</template>

<script>
import { computed, onMounted, ref } from 'vue';
import { useStore } from 'vuex';
import { useRouter, useRoute } from 'vue-router';

export default {
	name: 'App',
	setup() {
		const store = useStore();
		const router = useRouter();
		const route = useRoute();

		const globalLoading = ref(false);
		const globalError = ref('');

		const tabs = [
			{
				name: 'table',
				label: 'Table',
				icon: 'dashicons dashicons-list-view',
			},
			{
				name: 'graph',
				label: 'Graph',
				icon: 'dashicons dashicons-chart-line',
			},
			{
				name: 'settings',
				label: 'Settings',
				icon: 'dashicons dashicons-admin-settings',
			},
		];

		// Computed properties
		const loading = computed(() => store.state.loading);
		const error = computed(() => store.state.error);

		// Initialize tab based on URL parameter or localStorage
		const initializeTab = () => {
			const urlParams = new URLSearchParams(window.location.search);
			const tabParam = urlParams.get('tab');
			const validTabs = ['table', 'graph', 'settings'];

			let targetTab = 'table'; // Default

			if (tabParam && validTabs.includes(tabParam)) {
				targetTab = tabParam;
			} else {
				// Check localStorage for last visited tab (for refresh handling)
				const lastTab = localStorage.getItem('aialvi_vue_last_tab');
				if (lastTab && validTabs.includes(lastTab)) {
					targetTab = lastTab;
				}
			}

			// Navigate to the determined tab only if not already there
			if (route.name !== targetTab) {
				router.replace({ name: targetTab });
			}
		};

		// Handle keyboard navigation for tabs
		const handleTabKeydown = (event, tab) => {
			const currentIndex = tabs.findIndex(t => t.name === tab.name);
			let nextIndex = currentIndex;

			switch (event.key) {
				case 'ArrowLeft':
					event.preventDefault();
					nextIndex =
						currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
					break;
				case 'ArrowRight':
					event.preventDefault();
					nextIndex =
						currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
					break;
				case 'Home':
					event.preventDefault();
					nextIndex = 0;
					break;
				case 'End':
					event.preventDefault();
					nextIndex = tabs.length - 1;
					break;
				case 'Enter':
				case ' ':
					event.preventDefault();
					router.push({ name: tab.name });
					return;
				default:
					return;
			}

			// Navigate to the next tab and focus it
			const nextTab = tabs[nextIndex];
			router.push({ name: nextTab.name });
		};

		// Retry initialization on error
		const retryInitialization = async () => {
			globalError.value = '';
			globalLoading.value = true;

			try {
				await Promise.all([
					store.dispatch('fetchSettings'),
					store.dispatch('fetchData'),
				]);
			} catch (fetchError) {
				globalError.value =
					fetchError.message || 'Failed to initialize plugin';
			} finally {
				globalLoading.value = false;
			}
		};

		// WordPress translation function
		const __ = (text, domain = 'aialvi-page-ranks') => {
			if (window.wp && window.wp.i18n && window.wp.i18n.__) {
				return window.wp.i18n.__(text, domain);
			}
			return text;
		};

		onMounted(async () => {
			// Initialize tab from URL or localStorage
			initializeTab();

			// Fetch initial data with timeout protection
			globalLoading.value = true;
			const timeout = setTimeout(() => {
				globalError.value =
					'Request timed out. Please refresh the page.';
				globalLoading.value = false;
			}, 30000); // 30 second timeout

			try {
				await Promise.race([
					Promise.all([
						store.dispatch('fetchSettings'),
						store.dispatch('fetchData'),
					]),
					new Promise((_, reject) =>
						setTimeout(() => reject(new Error('Timeout')), 25000)
					),
				]);
				clearTimeout(timeout);
			} catch (fetchError) {
				clearTimeout(timeout);
				if (fetchError.message === 'Timeout') {
					globalError.value =
						'Request timed out. Please check your network connection and refresh the page.';
				} else {
					globalError.value =
						fetchError.message || 'Failed to initialize plugin';
				}
			} finally {
				globalLoading.value = false;
			}
		});

		return {
			tabs,
			globalLoading,
			globalError,
			loading,
			error,
			retryInitialization,
			handleTabKeydown,
			__,
		};
	},
};
</script>

<style scoped>
.aialvi-admin-app {
	margin: 0;
	font-family:
		-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
	background: #f1f1f1;
}

.admin-header {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-bottom: none;
	padding: 20px 30px;
	margin: 20px 0 0 0;
}

.header-top {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 10px;
}

.plugin-title {
	color: #1d2327;
	font-size: 24px;
	font-weight: 600;
	margin: 0;
	display: flex;
	align-items: center;
	gap: 10px;
}

.plugin-icon {
	font-size: 28px;
}

.header-info {
	display: flex;
	align-items: center;
	gap: 15px;
}

.version {
	background: #f0f6fc;
	color: #0a4b78;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 600;
}

.description {
	color: #646970;
	font-size: 14px;
	margin: 0;
	line-height: 1.4;
}

/* WordPress-style tabs with enhancements */
.nav-tab-wrapper {
	border-bottom: 1px solid #c3c4c7;
	margin: 0;
	padding: 0 30px;
	background: #fff;
	border-left: 1px solid #c3c4c7;
	border-right: 1px solid #c3c4c7;
}

.nav-tab {
	background: #f6f7f7;
	border: 1px solid #c3c4c7;
	border-bottom: none;
	color: #50575e;
	text-decoration: none;
	display: inline-flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
	font-weight: 600;
	line-height: 24px;
	margin: 0 5px -1px 0;
	padding: 12px 16px;
	position: relative;
	text-align: center;
	transition: all 0.2s ease;
	outline: none;
}

.nav-tab:hover {
	background-color: #fff;
	color: #0a4b78;
	border-color: #0a4b78;
}

.nav-tab:focus {
	border-bottom: 3px solid #0a4b78;
	outline: none !important;
	box-shadow: none !important;
}

.nav-tab:focus-visible {
	outline: none !important;
	box-shadow: none !important;
}

/* Additional rules to ensure router-link elements don't show outlines */
.nav-tab-wrapper a:focus,
.nav-tab-wrapper a:focus-visible {
	outline: none !important;
	box-shadow: none !important;
}

.nav-tab-active {
	background: #fff;
	border-bottom: 3px solid #0a4b78;
	color: #0a4b78;
	margin-bottom: -3px;
	font-weight: 700;
}

.nav-tab .dashicons {
	font-size: 16px;
	line-height: 1;
}

/* Content area */
.admin-content {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-top: none;
	min-height: 500px;
}

.tab-content {
	padding: 30px;
}

/* Loading and error states */
.loading-wrapper {
	text-align: center;
	padding: 60px 20px;
}

.spinner {
	background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxjaXJjbGUgY3g9IjEwIiBjeT0iMTAiIHI9IjEwIiBmaWxsPSJub25lIiBzdHJva2U9IiNkNjNhMzgiIHN0cm9rZS13aWR0aD0iNCIvPgogICAgPGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwNzNhYSIgc3Ryb2tlLXdpZHRoPSI0IiBzdHJva2UtZGFzaGFycmF5PSI2MiIgc3Ryb2tlLWRhc2hvZmZzZXQ9IjMxIiBvcGFjaXR5PSIuNyI+CiAgICAgICAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJyb3RhdGUiIGZyb209IjAgMTAgMTAiIHRvPSIzNjAgMTAgMTAiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIi8+CiAgICA8L2NpcmNsZT4KPC9zdmc+')
		no-repeat center center;
	background-size: 20px 20px;
	display: inline-block;
	height: 20px;
	width: 20px;
	vertical-align: middle;
	margin-right: 10px;
}

.notice {
	background: #fff;
	border-left: 4px solid #72aee6;
	box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
	margin: 30px;
	padding: 12px;
}

.notice p {
	margin: 0.5em 0;
	padding: 2px;
}

.notice-error {
	border-left-color: #d63638;
}

.button {
	display: inline-block;
	text-decoration: none;
	font-size: 13px;
	line-height: 2.15384615;
	min-height: 30px;
	margin: 8px 0 0 0;
	padding: 0 10px;
	cursor: pointer;
	border-width: 1px;
	border-style: solid;
	border-radius: 3px;
	white-space: nowrap;
	box-sizing: border-box;
	background: #f6f7f7;
	border-color: #c3c4c7;
	color: #50575e;
}

.button:hover {
	background: #f0f0f1;
	border-color: #8c8f94;
}

.screen-reader-text {
	clip: rect(1px, 1px, 1px, 1px);
	position: absolute !important;
	height: 1px;
	width: 1px;
	overflow: hidden;
}

/* Responsive design */
@media (max-width: 768px) {
	.admin-header {
		padding: 15px 20px;
	}

	.header-top {
		flex-direction: column;
		align-items: flex-start;
		gap: 10px;
	}

	.nav-tab-wrapper {
		padding: 0 20px;
	}

	.nav-tab {
		padding: 10px 12px;
		font-size: 13px;
	}

	.tab-content {
		padding: 20px;
	}
}

/* WordPress clearfix */
.wp-clearfix:after {
	content: '';
	display: table;
	clear: both;
}
</style>
