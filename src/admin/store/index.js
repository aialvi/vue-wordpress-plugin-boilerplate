import { createStore } from 'vuex';
import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Track ongoing requests to prevent duplicates
const ongoingRequests = new Map();

const store = createStore({
	state: {
		settings: {
			table_rows: 5,
			date_format: 'human',
			notification_emails: [],
		},
		data: {
			table: [],
			graph: [],
		},
		loading: false,
		error: null,
		success: null,
	},
	mutations: {
		SET_SETTINGS(state, settings) {
			// Filter settings to only include the 3 required fields
			const filteredSettings = {
				table_rows: settings.table_rows || 5,
				date_format: settings.date_format || 'human',
				notification_emails: settings.notification_emails || [],
			};
			state.settings = { ...state.settings, ...filteredSettings };
		},
		SET_DATA(state, data) {
			state.data = data;
		},
		SET_LOADING(state, loading) {
			state.loading = loading;
		},
		SET_ERROR(state, error) {
			state.error = error;
		},
		SET_SUCCESS(state, message) {
			state.success = message;
		},
		CLEAR_MESSAGES(state) {
			state.error = null;
			state.success = null;
		},
		UPDATE_SINGLE_SETTING(state, { key, value }) {
			state.settings[key] = value;
		},
	},
	actions: {
		async fetchSettings({ commit }) {
			commit('SET_LOADING', true);
			commit('CLEAR_MESSAGES');

			try {
				// Use axios for REST API call
				const response = await axios.get(
					`${window.aialviVuePlugin.rest_url}settings`,
					{
						headers: {
							'X-WP-Nonce': window.aialviVuePlugin.rest_nonce,
						},
					}
				);

				commit('SET_SETTINGS', response.data);
				return response.data;
			} catch (error) {
				let errorMessage = 'Unable to load settings';

				if (error.response) {
					// Server responded with error status
					errorMessage =
						error.response.data?.message ||
						`HTTP ${error.response.status}: Failed to load settings`;
				} else if (error.request) {
					// Network error
					errorMessage =
						'Network error: Please check your connection';
				} else {
					// Other error
					errorMessage = error.message || errorMessage;
				}

				commit('SET_ERROR', errorMessage);
				throw new Error(errorMessage);
			} finally {
				commit('SET_LOADING', false);
			}
		},

		async saveSettings({ commit, state }) {
			commit('SET_LOADING', true);
			commit('CLEAR_MESSAGES');

			try {
				// Filter settings to only include expected fields
				const filteredSettings = {
					table_rows: state.settings.table_rows,
					date_format: state.settings.date_format,
					notification_emails:
						state.settings.notification_emails || [],
				};

				const response = await axios.post(
					`${window.aialviVuePlugin.rest_url}settings`,
					{
						settings: filteredSettings,
					},
					{
						headers: {
							'X-WP-Nonce': window.aialviVuePlugin.rest_nonce,
							'Content-Type': 'application/json',
						},
					}
				);

				commit('SET_SETTINGS', response.data.settings);
				commit('SET_SUCCESS', 'Settings saved successfully!');

				// Clear success message after 3 seconds
				setTimeout(() => {
					commit('SET_SUCCESS', null);
				}, 3000);

				return response.data;
			} catch (error) {
				let errorMessage = 'Unable to save settings';

				if (error.response) {
					errorMessage =
						error.response.data?.message ||
						`HTTP ${error.response.status}: Failed to save settings`;
				} else if (error.request) {
					errorMessage =
						'Network error: Please check your connection';
				} else {
					errorMessage = error.message || errorMessage;
				}

				commit('SET_ERROR', errorMessage);
				throw new Error(errorMessage);
			} finally {
				commit('SET_LOADING', false);
			}
		},

		async updateSingleSetting({ commit }, { key, value }) {
			// Create a request key to prevent duplicate calls
			const requestKey = `updateSetting_${key}_${value}`;

			// If the same request is already ongoing, return that promise
			if (ongoingRequests.has(requestKey)) {
				return ongoingRequests.get(requestKey);
			}

			commit('CLEAR_MESSAGES');

			const requestPromise = (async () => {
				try {
					const response = await axios.post(
						`${window.aialviVuePlugin.rest_url}setting/${key}`,
						{ value },
						{
							headers: {
								'X-WP-Nonce': window.aialviVuePlugin.rest_nonce,
								'Content-Type': 'application/json',
							},
						}
					);

					commit('UPDATE_SINGLE_SETTING', {
						key,
						value: response.data.value,
					});
					return response.data;
				} catch (error) {
					let errorMessage = 'Unable to update setting';

					if (error.response) {
						errorMessage =
							error.response.data?.message ||
							`HTTP ${error.response.status}: Failed to update setting`;
					} else if (error.request) {
						errorMessage =
							'Network error: Please check your connection';
					} else {
						errorMessage = error.message || errorMessage;
					}

					commit('SET_ERROR', errorMessage);
					throw new Error(errorMessage);
				} finally {
					// Remove from ongoing requests when done
					ongoingRequests.delete(requestKey);
				}
			})();

			// Store the promise to prevent duplicate requests
			ongoingRequests.set(requestKey, requestPromise);
			return requestPromise;
		},

		async fetchData({ commit }) {
			commit('SET_LOADING', true);
			commit('CLEAR_MESSAGES');

			try {
				// Use axios for REST API call
				const response = await axios.get(
					`${window.aialviVuePlugin.rest_url}data`,
					{
						headers: {
							'X-WP-Nonce': window.aialviVuePlugin.rest_nonce,
						},
					}
				);

				commit('SET_DATA', response.data);
				return response.data;
			} catch (error) {
				let errorMessage = 'Unable to load data';

				if (error.response) {
					errorMessage =
						error.response.data?.message ||
						`HTTP ${error.response.status}: Failed to load data`;
				} else if (error.request) {
					errorMessage =
						'Network error: Please check your connection';
				} else {
					errorMessage = error.message || errorMessage;
				}

				commit('SET_ERROR', errorMessage);
				throw new Error(errorMessage);
			} finally {
				commit('SET_LOADING', false);
			}
		},

		// Clear all messages
		clearMessages({ commit }) {
			commit('CLEAR_MESSAGES');
		},

		// Set success message manually
		setSuccess({ commit }, message) {
			commit('SET_SUCCESS', message);
		},

		// Set error message manually
		setError({ commit }, message) {
			commit('SET_ERROR', message);
		},
	},
	getters: {
		formattedTableData: state => {
			// Handle the actual API response structure: table.data.rows
			let tableRows = [];

			if (
				state.data.table &&
				state.data.table.data &&
				Array.isArray(state.data.table.data.rows)
			) {
				tableRows = state.data.table.data.rows;
			} else if (Array.isArray(state.data.table)) {
				// Fallback for simple array structure
				tableRows = state.data.table;
			}

			if (tableRows.length === 0) {
				return [];
			}

			return tableRows.slice(0, state.settings.table_rows).map(row => ({
				...row,
				date:
					state.settings.date_format === 'human'
						? new Date(row.date * 1000).toLocaleDateString(
								'en-US',
								{
									year: 'numeric',
									month: 'short',
									day: 'numeric',
									hour: '2-digit',
									minute: '2-digit',
								}
							)
						: row.date,
			}));
		},

		graphData: state => {
			// Handle the actual API response structure: graph as object with numbered keys
			let graphArray = [];

			if (state.data.graph && typeof state.data.graph === 'object') {
				// Convert object to array format expected by Chart.js
				graphArray = Object.values(state.data.graph).map(
					(item, index) => ({
						label: `Point ${index + 1}`,
						value: item.value,
						date: item.date,
						// Add formatted date for display
						dateFormatted: new Date(
							item.date * 1000
						).toLocaleDateString('en-US', {
							month: 'short',
							day: 'numeric',
						}),
					})
				);
			} else if (Array.isArray(state.data.graph)) {
				// Fallback for array structure
				graphArray = state.data.graph;
			}

			return graphArray;
		},

		// Check if app is in loading state
		isLoading: state => {
			return state.loading;
		},

		// Check if there are any errors
		hasError: state => {
			return !!state.error;
		},

		// Check if there are success messages
		hasSuccess: state => {
			return !!state.success;
		},

		// Get total number of table rows available
		totalTableRows: state => {
			if (
				state.data.table &&
				state.data.table.data &&
				Array.isArray(state.data.table.data.rows)
			) {
				return state.data.table.data.rows.length;
			} else if (Array.isArray(state.data.table)) {
				return state.data.table.length;
			}
			return 0;
		},

		// Get table headers
		tableHeaders: state => {
			if (
				state.data.table &&
				state.data.table.data &&
				Array.isArray(state.data.table.data.headers)
			) {
				return state.data.table.data.headers;
			}
			// Default headers
			return ['ID', 'URL', 'Title', 'Pageviews', 'Date'];
		},

		// Get table title
		tableTitle: state => {
			if (state.data.table && state.data.table.title) {
				return state.data.table.title;
			}
			return 'Data Table';
		},
	},
});

export default store;
