<template>
	<div class="graph-tab">
		<header class="graph-header">
			<h2>{{ __('Data Visualization', 'aialvi-page-ranks') }}</h2>
			<p>
				{{
					__(
						'Interactive charts and graphs showing Page Views based on the API data for better insights.',
						'aialvi-page-ranks'
					)
				}}
			</p>
		</header>

		<!-- Loading State -->
		<div
			v-if="loading"
			class="loading-wrapper"
			role="status"
			:aria-label="__('Loading graph data', 'aialvi-page-ranks')"
		>
			<div class="spinner is-active" aria-hidden="true" />
			<p>{{ __('Loading graph data...', 'aialvi-page-ranks') }}</p>
		</div>

		<!-- Error State -->
		<div v-else-if="error" class="notice notice-error" role="alert">
			<p>
				<span class="dashicons dashicons-warning" aria-hidden="true" />
				<strong>{{ __('Error:', 'aialvi-page-ranks') }}</strong>
				{{ error }}
			</p>
			<button
				type="button"
				class="button button-secondary"
				:aria-label="
					__('Retry loading graph data', 'aialvi-page-ranks')
				"
				@click="refreshData"
			>
				<span class="dashicons dashicons-update" aria-hidden="true" />
				{{ __('Retry', 'aialvi-page-ranks') }}
			</button>
		</div>

		<!-- Graph Container -->
		<div v-else-if="graphData.length > 0" class="graph-container">
			<!-- Graph Controls -->
			<div class="graph-controls">
				<div class="chart-info">
					<span class="info-badge">
						{{
							sprintf(
								__('%d pageviews data', 'aialvi-page-ranks'),
								graphData.length
							)
						}}
					</span>
				</div>
				<div class="controls-group">
					<button
						type="button"
						class="button button-secondary button-small"
						:disabled="loading"
						:aria-label="
							__('Refresh chart data', 'aialvi-page-ranks')
						"
						@click="refreshData"
					>
						<span
							class="dashicons dashicons-update"
							aria-hidden="true"
						/>
						{{ __('Refresh', 'aialvi-page-ranks') }}
					</button>
				</div>
			</div>

			<!-- Chart Container -->
			<div
				class="chart-wrapper"
				role="img"
				:aria-label="
					sprintf(
						__(
							'Line chart showing page views over time with %d data points',
							'aialvi-page-ranks'
						),
						graphData.length
					)
				"
			>
				<canvas
					id="dataChart"
					ref="chartCanvas"
					:aria-label="
						__(
							'Interactive chart showing page views data',
							'aialvi-page-ranks'
						)
					"
				/>
			</div>

			<!-- Chart Legend/Info -->
			<section
				class="chart-info-panel"
				:aria-label="__('Chart statistics', 'aialvi-page-ranks')"
			>
				<h4>{{ __('Chart Information', 'aialvi-page-ranks') }}</h4>
				<div class="stats-grid" role="list">
					<div class="stat-item" role="listitem">
						<span class="stat-label">{{
							__('Total Points:', 'aialvi-page-ranks')
						}}</span>
						<span
							class="stat-value"
							:aria-label="
								sprintf(
									__(
										'%s total data points',
										'aialvi-page-ranks'
									),
									graphData.length
								)
							"
						>
							{{ graphData.length }}
						</span>
					</div>
					<div class="stat-item" role="listitem">
						<span class="stat-label">{{
							__('Max Value:', 'aialvi-page-ranks')
						}}</span>
						<span
							class="stat-value"
							:aria-label="
								sprintf(
									__(
										'Maximum value: %s',
										'aialvi-page-ranks'
									),
									maxValue
								)
							"
						>
							{{ maxValue }}
						</span>
					</div>
					<div class="stat-item" role="listitem">
						<span class="stat-label">{{
							__('Min Value:', 'aialvi-page-ranks')
						}}</span>
						<span
							class="stat-value"
							:aria-label="
								sprintf(
									__(
										'Minimum value: %s',
										'aialvi-page-ranks'
									),
									minValue
								)
							"
						>
							{{ minValue }}
						</span>
					</div>
					<div class="stat-item" role="listitem">
						<span class="stat-label">{{
							__('Average:', 'aialvi-page-ranks')
						}}</span>
						<span
							class="stat-value"
							:aria-label="
								sprintf(
									__(
										'Average value: %s',
										'aialvi-page-ranks'
									),
									averageValue
								)
							"
						>
							{{ averageValue }}
						</span>
					</div>
				</div>
			</section>
		</div>

		<!-- No Data State -->
		<div v-else class="no-data">
			<div class="no-data-icon">
				<span
					class="dashicons dashicons-chart-line"
					aria-hidden="true"
				/>
			</div>
			<h3>{{ __('No graph data available', 'aialvi-page-ranks') }}</h3>
			<p>
				{{
					__(
						'No chart data has been loaded from the API endpoint yet.',
						'aialvi-page-ranks'
					)
				}}
			</p>
			<button
				type="button"
				class="button button-primary"
				:aria-label="
					__('Load chart data from API', 'aialvi-page-ranks')
				"
				@click="refreshData"
			>
				<span class="dashicons dashicons-download" aria-hidden="true" />
				{{ __('Load Data', 'aialvi-page-ranks') }}
			</button>
		</div>
	</div>
</template>

<script>
import {
	computed,
	onMounted,
	ref,
	nextTick,
	watch,
	onBeforeUnmount,
} from 'vue';
import { useStore } from 'vuex';
import {
	Chart as ChartJS,
	CategoryScale,
	LinearScale,
	PointElement,
	LineElement,
	LineController,
	Title,
	Tooltip,
	Legend,
	Filler,
} from 'chart.js';

ChartJS.register(
	CategoryScale,
	LinearScale,
	PointElement,
	LineElement,
	LineController,
	Title,
	Tooltip,
	Legend,
	Filler
);

export default {
	name: 'GraphTab',
	setup() {
		const store = useStore();
		const chartCanvas = ref(null);
		let chartInstance = null;
		let isCreatingChart = false; // Prevent race conditions

		// Computed properties
		const loading = computed(() => store.state.loading);
		const error = computed(() => store.state.error);
		const graphData = computed(() => store.getters.graphData);

		// Chart statistics
		const chartValues = computed(() => {
			return graphData.value.map(item =>
				parseFloat(item.value || item.count || 0)
			);
		});

		const maxValue = computed(() => {
			return chartValues.value.length > 0
				? Math.max(...chartValues.value).toLocaleString()
				: 0;
		});

		const minValue = computed(() => {
			return chartValues.value.length > 0
				? Math.min(...chartValues.value).toLocaleString()
				: 0;
		});

		const averageValue = computed(() => {
			if (chartValues.value.length === 0) return 0;
			const sum = chartValues.value.reduce((acc, val) => acc + val, 0);
			return (sum / chartValues.value.length).toFixed(2);
		});

		// WordPress translation function
		const __ = (text, domain = 'aialvi-page-ranks') => {
			if (window.wp && window.wp.i18n && window.wp.i18n.__) {
				return window.wp.i18n.__(text, domain);
			}
			return text;
		};

		const sprintf = (format, ...args) => {
			if (window.wp && window.wp.i18n && window.wp.i18n.sprintf) {
				return window.wp.i18n.sprintf(format, ...args);
			}
			// Simple sprintf implementation
			let i = 0;
			return format.replace(/%[sd%]/g, match => {
				if (match === '%%') return '%';
				if (i < args.length) return String(args[i++]);
				return match;
			});
		};

		// Get chart configuration for line chart only
		const getChartConfig = () => {
			const labels = graphData.value.map(
				item =>
					item.dateFormatted ||
					item.label ||
					`Point ${item.date || 'N/A'}`
			);
			const values = graphData.value.map(item => item.value || 0);

			return {
				type: 'line',
				data: {
					labels,
					datasets: [
						{
							label: __('Page Views', 'aialvi-page-ranks'),
							data: values,
							borderColor: '#2271b1',
							backgroundColor: 'rgba(34, 113, 177, 0.1)',
							borderWidth: 3,
							fill: true,
							tension: 0.4,
							pointBackgroundColor: '#2271b1',
							pointBorderColor: '#fff',
							pointBorderWidth: 2,
							pointRadius: 6,
							pointHoverRadius: 8,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						title: {
							display: true,
							text: __(
								'Website Traffic Over Time',
								'aialvi-page-ranks'
							),
							font: {
								size: 16,
								weight: 'bold',
							},
							color: '#1d2327',
						},
						legend: {
							position: 'top',
							labels: {
								usePointStyle: true,
								padding: 20,
								font: {
									size: 12,
								},
							},
						},
						tooltip: {
							backgroundColor: 'rgba(0,0,0,0.8)',
							titleColor: '#fff',
							bodyColor: '#fff',
							cornerRadius: 6,
							displayColors: false,
							callbacks: {
								title: function (context) {
									return context[0].label;
								},
								label: function (context) {
									return `Views: ${new Intl.NumberFormat().format(context.raw)}`;
								},
							},
						},
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								precision: 0,
								callback: function (value) {
									return new Intl.NumberFormat().format(
										value
									);
								},
							},
							grid: {
								color: 'rgba(0,0,0,0.1)',
							},
						},
						x: {
							grid: {
								color: 'rgba(0,0,0,0.1)',
							},
						},
					},
					interaction: {
						intersect: false,
						mode: 'index',
					},
				},
			};
		};

		// Create or update chart
		const createChart = async () => {
			// Prevent race conditions. Only one chart creation at a time
			if (isCreatingChart) {
				return;
			}

			if (loading.value) {
				return;
			}

			if (!chartCanvas.value || !graphData.value.length) {
				return;
			}

			isCreatingChart = true;

			try {
				// Destroy existing chart if it exists
				if (chartInstance) {
					try {
						chartInstance.destroy();
					} catch (destroyError) {
						store.dispatch(
							'setError',
							`Failed to destroy chart: ${destroyError.message}`
						);
					}
					chartInstance = null;
				}

				await nextTick();

				// Double-check canvas is available after nextTick
				if (!chartCanvas.value) {
					return;
				}

				// Clear any existing Chart.js instance on this canvas
				const existingChart = ChartJS.getChart(chartCanvas.value);
				if (existingChart) {
					existingChart.destroy();
				}

				const ctx = chartCanvas.value.getContext('2d');
				const config = getChartConfig();

				chartInstance = new ChartJS(ctx, config);
			} catch (chartError) {
				store.dispatch(
					'setError',
					`Failed to create chart: ${chartError.message}`
				);
			} finally {
				isCreatingChart = false;
			}
		};

		// Refresh data manually
		const refreshData = async () => {
			try {
				await store.dispatch('fetchData');
				// Force chart recreation after data is loaded
				await nextTick();
				await createChart();
			} catch (refreshError) {
				// Error is handled by the store
			}
		};

		// Watch for data changes, but not during loading
		watch(
			graphData,
			async newData => {
				// Only recreate chart if we're not in loading state and have data
				if (
					!loading.value &&
					newData &&
					newData.length > 0 &&
					!isCreatingChart
				) {
					await nextTick();
					await createChart();
				}
			},
			{ deep: true }
		);

		// Watch loading state to recreate chart when loading finishes
		watch(loading, async (isLoading, wasLoading) => {
			// When loading finishes and we have data, recreate the chart
			if (
				wasLoading &&
				!isLoading &&
				graphData.value.length > 0 &&
				!isCreatingChart
			) {
				await nextTick();
				await createChart();
			}
		});

		// Initialize component
		onMounted(async () => {
			// Only refresh if we don't have data
			if (
				!store.state.data.graph ||
				store.state.data.graph.length === 0
			) {
				await refreshData();
			} else {
				await nextTick();
				await createChart();
			}
		});

		// Cleanup
		onBeforeUnmount(() => {
			isCreatingChart = false;
			if (chartInstance) {
				try {
					chartInstance.destroy();
				} catch (destroyError) {
					store.dispatch(
						'setError',
						`Failed to destroy chart on unmount: ${destroyError.message}`
					);
				}
				chartInstance = null;
			}
		});

		return {
			loading,
			error,
			graphData,
			chartCanvas,
			maxValue,
			minValue,
			averageValue,
			refreshData,
			__,
			sprintf,
		};
	},
};
</script>

<style scoped>
.graph-tab {
	max-width: 100%;
}

.graph-header {
	margin-bottom: 25px;
}

.graph-header h2 {
	margin: 0 0 8px 0;
	color: #1d2327;
	font-size: 22px;
}

.graph-header p {
	margin: 0;
	color: #646970;
	font-size: 14px;
}

.graph-container {
	margin-bottom: 30px;
}

.graph-controls {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
	padding: 15px;
	background: #f9f9f9;
	border: 1px solid #e0e0e0;
	border-radius: 4px;
}

.chart-info {
	display: flex;
	gap: 15px;
	align-items: center;
}

.controls-group {
	display: flex;
	gap: 10px;
	align-items: center;
}

.info-badge {
	background: #0073aa;
	color: white;
	padding: 4px 8px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
}

.chart-type {
	color: #646970;
	font-size: 13px;
	font-style: italic;
}

.button-small {
	font-size: 12px;
	min-height: 26px;
	padding: 0 8px;
}

.chart-wrapper {
	background: #fff;
	border: 1px solid #e0e0e0;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 20px;
	height: 450px;
	position: relative;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-wrapper canvas {
	max-width: 100%;
	height: 100%;
}

.chart-info-panel {
	background: #f8f9fa;
	border: 1px solid #e0e0e0;
	border-radius: 4px;
	padding: 20px;
}

.chart-info-panel h4 {
	margin: 0 0 15px 0;
	color: #1d2327;
	font-size: 16px;
}

.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
}

.stat-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 10px 15px;
	background: #fff;
	border: 1px solid #e0e0e0;
	border-radius: 4px;
}

.stat-label {
	font-weight: 600;
	color: #646970;
	font-size: 13px;
}

.stat-value {
	font-weight: 700;
	color: #0073aa;
	font-size: 14px;
}

.no-data {
	text-align: center;
	padding: 60px 20px;
	color: #646970;
}

.no-data-icon .dashicons {
	font-size: 48px;
	color: #c3c4c7;
	margin-bottom: 20px;
}

.no-data h3 {
	margin: 0 0 10px 0;
	color: #1d2327;
}

.no-data p {
	margin: 0 0 20px 0;
}

.loading-wrapper {
	text-align: center;
	padding: 60px 20px;
}

.spinner {
	background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxjaXJjbGUgY3g9IjEwIiBjeT0iMTAiIHI9IjEwIiBmaWxsPSJub25lIiBzdHJva2U9IiNkNjNhMzgiIHN0cm9rZS13aWR0aD0iNCIvPgogICAgPGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzAwNzNhYSIgc3Ryb2tlLXdpZHRoPSI0IiBzdHJva2UtZGFzaGFycmF5PSI2MiIgc3Ryb2tlLWRhc2hvZmZzZXQ9IjMxIiBvcGFjaXR5PSIuNyI+CiAgICAgICAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJyb3RhdGUiIGZyb209IjAgMTAgMTAiIHRvPSIzNjAgMTAgMTAiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIi8+CiAgICA8L2NpcmNsZT4KPC9zdmc+')
		no-repeat center center;
	background-size: 20px 20px;
	display: inline-block;
	height: 16px;
	width: 16px;
	vertical-align: middle;
	margin-right: 8px;
}

.notice {
	background: #fff;
	border-left: 4px solid #72aee6;
	box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
	margin: 20px 0;
	padding: 12px;
}

.notice p {
	margin: 0 0 10px 0;
	display: flex;
	align-items: center;
	gap: 8px;
}

.notice-error {
	border-left-color: #d63638;
}

.button {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	text-decoration: none;
	font-size: 13px;
	line-height: 2.15384615;
	min-height: 32px;
	margin: 0;
	padding: 0 12px;
	cursor: pointer;
	border-width: 1px;
	border-style: solid;
	border-radius: 3px;
	white-space: nowrap;
	box-sizing: border-box;
	transition: all 0.15s ease-in-out;
}

.button-primary {
	background: #2271b1;
	border-color: #2271b1;
	color: #fff;
}

.button-primary:hover:not(:disabled) {
	background: #135e96;
	border-color: #135e96;
}

.button-secondary {
	background: #f6f7f7;
	border-color: #c3c4c7;
	color: #50575e;
}

.button-secondary:hover:not(:disabled) {
	background: #f0f0f1;
	border-color: #8c8f94;
}

.button:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

.dashicons {
	font-size: 16px;
	line-height: 1;
	text-decoration: none;
}

.dashicons-update {
	margin-top: 4px;
}

/* Responsive design */
@media (max-width: 768px) {
	.graph-controls {
		flex-direction: column;
		gap: 15px;
		align-items: flex-start;
	}

	.chart-info {
		flex-direction: column;
		gap: 8px;
		align-items: flex-start;
	}

	.controls-group {
		width: 100%;
		justify-content: space-between;
	}

	.chart-wrapper {
		height: 350px;
		padding: 15px;
	}

	.stats-grid {
		grid-template-columns: 1fr;
	}
}
</style>
