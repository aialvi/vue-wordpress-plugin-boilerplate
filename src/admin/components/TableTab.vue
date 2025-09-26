<template>
	<div class="table-tab">
		<header class="table-header">
			<h2>{{ __('Page Ranks', 'aialvi-page-ranks') }}</h2>
			<p>
				{{
					__(
						'This table provides key insights like viewed pages, traffic statistics, and notification recipients in real time.',

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
			:aria-label="__('Loading table data', 'aialvi-page-ranks')"
		>
			<div class="spinner is-active" aria-hidden="true" />
			<p>{{ __('Loading table data...', 'aialvi-page-ranks') }}</p>
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
					__('Retry loading table data', 'aialvi-page-ranks')
				"
				@click="refreshData"
			>
				<span class="dashicons dashicons-update" aria-hidden="true" />
				{{ __('Retry', 'aialvi-page-ranks') }}
			</button>
		</div>

		<!-- Table Container -->
		<div v-else-if="tableData.length > 0" class="table-container">
			<!-- Table Controls -->
			<div class="table-controls">
				<div class="table-info">
					<span class="info-badge">
						{{
							sprintf(
								__(
									'Showing %1$d of %2$d pages',
									'aialvi-page-ranks'
								),
								tableData.length,
								totalRows
							)
						}}
					</span>
				</div>
				<button
					type="button"
					class="button button-secondary button-small"
					:disabled="loading"
					:aria-label="__('Refresh table data', 'aialvi-page-ranks')"
					@click="refreshData"
				>
					<span
						class="dashicons dashicons-update"
						aria-hidden="true"
					/>
					{{ __('Refresh Data', 'aialvi-page-ranks') }}
				</button>
			</div>

			<!-- Data Table -->
			<div class="table-wrapper">
				<table
					class="wp-list-table widefat fixed striped"
					role="table"
					:aria-label="
						sprintf(
							__(
								'Data table showing %d pages with pageview statistics',
								'aialvi-page-ranks'
							),
							tableData.length
						)
					"
					:aria-rowcount="tableData.length + 1"
				>
					<caption class="screen-reader-text">
						{{
							sprintf(
								__(
									'Website data table showing %1$d of %2$d pages with their pageview statistics and dates',
									'aialvi-page-ranks'
								),
								tableData.length,
								totalRows
							)
						}}
					</caption>
					<thead>
						<tr role="row">
							<th
								scope="col"
								class="column-name"
								:aria-label="
									__('Page title column', 'aialvi-page-ranks')
								"
							>
								{{ __('Title', 'aialvi-page-ranks') }}
							</th>
							<th
								scope="col"
								class="column-value"
								:aria-label="
									__('Page link column', 'aialvi-page-ranks')
								"
							>
								{{ __('Link', 'aialvi-page-ranks') }}
							</th>
							<th
								scope="col"
								class="column-pageviews"
								:aria-label="
									__(
										'Page views count column',
										'aialvi-page-ranks'
									)
								"
							>
								{{ __('Pageviews', 'aialvi-page-ranks') }}
							</th>
							<th
								scope="col"
								class="column-date"
								:aria-label="
									__('Date column', 'aialvi-page-ranks')
								"
							>
								{{ __('Date', 'aialvi-page-ranks') }}
							</th>
						</tr>
					</thead>
					<tbody>
						<tr
							v-for="(row, index) in tableData"
							:key="row.id"
							class="data-row"
							role="row"
							:aria-rowindex="index + 2"
						>
							<td
								class="column-name"
								:aria-describedby="`title-${row.id}`"
							>
								<span :id="`title-${row.id}`">{{
									row.title
								}}</span>
							</td>
							<td class="column-value">
								<a
									:href="row.url"
									target="_blank"
									rel="noopener noreferrer"
									class="link-cell"
									:aria-label="
										sprintf(
											__(
												'Visit page: %s (opens in new tab)',
												'aialvi-page-ranks'
											),
											truncateUrl(row.url)
										)
									"
									:title="row.url"
								>
									<span
										class="dashicons dashicons-admin-links"
										aria-hidden="true"
									/>
									{{ truncateUrl(row.url) }}
									<span
										class="dashicons dashicons-external"
										aria-hidden="true"
									/>
								</a>
							</td>
							<td class="column-pageviews">
								<div
									class="pageviews-cell"
									:aria-label="
										sprintf(
											__(
												'%s page views',
												'aialvi-page-ranks'
											),
											formatPageviews(row.pageviews)
										)
									"
								>
									<span
										class="dashicons dashicons-visibility"
										aria-hidden="true"
									/>
									<span
										class="pageviews-number"
										:class="
											getPageviewsClass(row.pageviews)
										"
										:aria-label="
											sprintf(
												__(
													'%s page views',
													'aialvi-page-ranks'
												),
												formatPageviews(row.pageviews)
											)
										"
									>
										{{ formatPageviews(row.pageviews) }}
									</span>
									<div
										class="pageviews-bar"
										:style="{
											width:
												getPageviewsBarWidth(
													row.pageviews
												) + '%',
										}"
										aria-hidden="true"
									/>
								</div>
							</td>
							<td class="column-date">
								<time
									:datetime="getISODate(row.date)"
									:title="getDateTitle(row.date)"
									:aria-label="
										sprintf(
											__('Date: %s', 'aialvi-page-ranks'),
											row.date
										)
									"
								>
									{{ row.date }}
								</time>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- No Data State -->
		<div v-else class="no-data">
			<div class="no-data-icon">
				<span class="dashicons dashicons-database" aria-hidden="true" />
			</div>
			<h3>{{ __('No data available', 'aialvi-page-ranks') }}</h3>
			<p>
				{{
					__(
						'No data has been loaded from the API endpoint yet.',
						'aialvi-page-ranks'
					)
				}}
			</p>
			<button
				type="button"
				class="button button-primary"
				:aria-label="__('Load data from API', 'aialvi-page-ranks')"
				@click="refreshData"
			>
				<span class="dashicons dashicons-download" aria-hidden="true" />
				{{ __('Load Data', 'aialvi-page-ranks') }}
			</button>
		</div>

		<!-- Email Notifications Section -->
		<section
			v-if="notificationEmails.length > 0"
			class="email-notifications-section"
			:aria-label="__('Email notification settings', 'aialvi-page-ranks')"
		>
			<header class="section-header">
				<h3>
					<span
						class="dashicons dashicons-email"
						aria-hidden="true"
					/>
					{{ __('Notification Recipients', 'aialvi-page-ranks') }}
				</h3>
				<p class="description">
					{{
						__(
							'These email addresses will receive notifications from the plugin.',
							'aialvi-page-ranks'
						)
					}}
				</p>
			</header>
			<ul
				class="email-list"
				role="list"
				:aria-label="
					sprintf(
						__(
							'%d notification email addresses',
							'aialvi-page-ranks'
						),
						notificationEmails.length
					)
				"
			>
				<li
					v-for="(email, index) in notificationEmails"
					:key="`email-${index}`"
					class="email-item"
					role="listitem"
				>
					<span
						class="dashicons dashicons-email-alt"
						aria-hidden="true"
					/>
					<a
						:href="`mailto:${email}`"
						class="email-link"
						:aria-label="
							sprintf(
								__('Send email to %s', 'aialvi-page-ranks'),
								email
							)
						"
					>
						{{ email }}
					</a>
					<span
						v-if="isDefaultAdminEmail(email)"
						class="admin-badge"
						:aria-label="
							__('Site administrator email', 'aialvi-page-ranks')
						"
					>
						{{ __('Admin', 'aialvi-page-ranks') }}
					</span>
				</li>
			</ul>
		</section>
	</div>
</template>

<script>
import { computed, onMounted } from 'vue';
import { useStore } from 'vuex';

export default {
	name: 'TableTab',
	setup() {
		const store = useStore();

		// Computed properties
		const loading = computed(() => store.state.loading);
		const error = computed(() => store.state.error);
		const tableData = computed(() => store.getters.formattedTableData);
		const settings = computed(() => store.state.settings);
		const notificationEmails = computed(
			() => store.state.settings.notification_emails || []
		);

		// Get total rows from original data
		const totalRows = computed(() => store.getters.totalTableRows);

		// Helper functions
		const getValueClass = value => {
			const numValue = parseFloat(value);
			if (isNaN(numValue)) return 'value-text';
			if (numValue > 100) return 'value-high';
			if (numValue > 50) return 'value-medium';
			return 'value-low';
		};

		const formatValue = value => {
			const numValue = parseFloat(value);
			if (!isNaN(numValue)) {
				return numValue.toLocaleString();
			}
			return value;
		};

		// Helper function to truncate URLs for better display
		const truncateUrl = url => {
			if (!url) return '';

			try {
				const urlObj = new URL(url);
				const domain = urlObj.hostname.replace('www.', '');
				const path = urlObj.pathname;

				if (path === '/' || path === '') {
					return domain;
				}

				const maxLength = 40;
				const fullDisplay = domain + path;

				if (fullDisplay.length <= maxLength) {
					return fullDisplay;
				}

				const pathLength = maxLength - domain.length - 3; // 3 for "..."
				if (pathLength > 0) {
					return domain + path.substring(0, pathLength) + '...';
				}

				return domain + '...';
			} catch (e) {
				// If URL parsing fails, return truncated original
				return url.length > 40 ? url.substring(0, 37) + '...' : url;
			}
		};

		// Format pageviews with proper number formatting
		const formatPageviews = pageviews => {
			const num = parseFloat(pageviews);
			if (isNaN(num)) return '0';

			if (num >= 1000000) {
				return (num / 1000000).toFixed(1) + 'M';
			} else if (num >= 1000) {
				return (num / 1000).toFixed(1) + 'K';
			}
			return num.toLocaleString();
		};

		// Get pageviews class based on value
		const getPageviewsClass = pageviews => {
			const num = parseFloat(pageviews);
			if (isNaN(num)) return 'pageviews-zero';
			if (num >= 8000) return 'pageviews-high';
			if (num >= 1000) return 'pageviews-medium';
			if (num > 0) return 'pageviews-low';
			return 'pageviews-zero';
		};

		// Calculate pageviews bar width for visual representation
		const getPageviewsBarWidth = pageviews => {
			const num = parseFloat(pageviews);
			if (isNaN(num) || num <= 0) return 0;

			// Get the maximum pageviews for relative scaling
			const maxPageviews = Math.max(
				...tableData.value.map(row => parseFloat(row.pageviews) || 0)
			);
			if (maxPageviews === 0) return 0;

			return Math.min((num / maxPageviews) * 100, 100);
		};

		const getISODate = dateValue => {
			// If it's a timestamp, convert to ISO
			if (typeof dateValue === 'number' || !isNaN(dateValue)) {
				return new Date(dateValue * 1000).toISOString();
			}
			// If it's already formatted, try to parse it
			return new Date(dateValue).toISOString();
		};

		const getDateTitle = dateValue => {
			if (typeof dateValue === 'number' || !isNaN(dateValue)) {
				const date = new Date(dateValue * 1000);
				return `Full date: ${date.toLocaleString()}`;
			}
			return dateValue;
		};

		const isDefaultAdminEmail = email => {
			return email === (window.aialviVuePlugin?.admin_email || '');
		};

		// Refresh data manually
		const refreshData = async () => {
			try {
				await store.dispatch('fetchData');
			} catch (refreshError) {
				// Error is handled by the store
			}
		};

		// WordPress translation functions
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
			// Simple sprintf implementation for fallback
			return format.replace(
				/%(\d+)\$([sd])|%([sd%])/g,
				(match, position, type1, type2) => {
					if (match === '%%') return '%';

					// Handle numbered placeholders like %1$d, %2$d
					if (position) {
						const index = parseInt(position) - 1;
						if (index >= 0 && index < args.length) {
							return String(args[index]);
						}
						return match;
					}

					// Handle sequential placeholders like %s, %d
					if (type2 && args.length > 0) {
						return String(args.shift());
					}

					return match;
				}
			);
		};

		// Initialize data when component mounts
		onMounted(() => {
			// Only fetch if we don't have data
			if (
				!store.state.data.table ||
				store.state.data.table.length === 0
			) {
				refreshData();
			}
		});

		return {
			loading,
			error,
			tableData,
			settings,
			notificationEmails,
			totalRows,
			getValueClass,
			formatValue,
			truncateUrl,
			formatPageviews,
			getPageviewsClass,
			getPageviewsBarWidth,
			getISODate,
			getDateTitle,
			isDefaultAdminEmail,
			refreshData,
			__,
			sprintf,
		};
	},
};
</script>

<style scoped>
.table-tab {
	max-width: 100%;
}

.table-header {
	margin-bottom: 25px;
}

.table-header h2 {
	margin: 0 0 8px 0;
	color: #1d2327;
	font-size: 22px;
}

.table-header p {
	margin: 0;
	color: #646970;
	font-size: 14px;
}

.table-container {
	margin-bottom: 40px;
}

.table-controls {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15px;
	padding: 15px;
	background: #f9f9f9;
	border: 1px solid #e0e0e0;
	border-radius: 4px;
}

.table-info {
	display: flex;
	gap: 15px;
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

.date-format-info {
	color: #646970;
	font-size: 13px;
	font-style: italic;
}

.button-small {
	font-size: 12px;
	min-height: 26px;
	padding: 0 8px;
}

.table-wrapper {
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	overflow: hidden;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.wp-list-table {
	width: 100%;
	border-spacing: 0;
	border-collapse: collapse;
	background: #fff;
	margin: 0;
}

.wp-list-table th,
.wp-list-table td {
	padding: 12px 15px;
	text-align: left;
	border-bottom: 1px solid #e0e0e0;
}

.wp-list-table th {
	background: #f8f9fa;
	font-weight: 600;
	color: #1d2327;
	font-size: 13px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.wp-list-table.striped > tbody > tr:nth-child(odd) {
	background-color: #f9f9f9;
}

.data-row:hover {
	background-color: #f0f6fc !important;
}

.column-name {
	width: 25%;
}

.column-value {
	width: 25%;
}

.column-pageviews {
	width: 25%;
}

.column-date {
	width: 25%;
}

/* Link cell styles */
.link-cell {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	color: #0073aa;
	text-decoration: none;
	transition: all 0.2s ease;
	font-size: 13px;
	max-width: 100%;
	overflow: hidden;
}

.link-cell span {
	margin-top: 6px;
}

.link-cell .dashicons-admin-links {
	font-size: 14px;
	flex-shrink: 0;
}

.link-cell .dashicons-external {
	font-size: 12px;
	flex-shrink: 0;
	opacity: 0.7;
}

.link-cell:hover .dashicons-external {
	opacity: 1;
}

/* Pageviews cell styles */
.pageviews-cell {
	position: relative;
	display: flex;
	align-items: center;
	gap: 8px;
	border-radius: 6px;
	overflow: hidden;
}

.pageviews-cell .dashicons-visibility {
	font-size: 14px;
	color: #646970;
	flex-shrink: 0;
	margin-top: 6px;
	margin-left: 4px;
}

.pageviews-number {
	font-weight: 600;
	font-size: 13px;
	z-index: 2;
	position: relative;
}

.pageviews-high {
	color: #00a32a;
}

.pageviews-medium {
	color: #dba617;
}

.pageviews-low {
	color: #d63638;
}

.pageviews-zero {
	color: #646970;
}

.pageviews-bar {
	position: absolute;
	left: 0;
	top: 0;
	bottom: 0;
	background: linear-gradient(
		90deg,
		rgba(0, 115, 170, 0.1) 0%,
		rgba(0, 115, 170, 0.2) 100%
	);
	border-radius: 6px;
	transition: width 0.3s ease;
	z-index: 1;
}

.pageviews-high + .pageviews-bar {
	background: linear-gradient(
		90deg,
		rgba(0, 163, 42, 0.1) 0%,
		rgba(0, 163, 42, 0.2) 100%
	);
}

.pageviews-medium + .pageviews-bar {
	background: linear-gradient(
		90deg,
		rgba(219, 166, 23, 0.1) 0%,
		rgba(219, 166, 23, 0.2) 100%
	);
}

.pageviews-low + .pageviews-bar {
	background: linear-gradient(
		90deg,
		rgba(214, 54, 56, 0.1) 0%,
		rgba(214, 54, 56, 0.2) 100%
	);
}

.value-badge {
	padding: 4px 8px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
}

.value-high {
	background: #00a32a;
	color: white;
}

.value-medium {
	background: #dba617;
	color: white;
}

.value-low {
	background: #d63638;
	color: white;
}

.value-text {
	background: #646970;
	color: white;
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

.email-notifications-section {
	margin-top: 40px;
	padding-top: 30px;
	border-top: 1px solid #e0e0e0;
}

.section-header {
	margin-bottom: 20px;
}

.section-header h3 {
	margin: 0 0 8px 0;
	display: flex;
	align-items: center;
	gap: 8px;
	color: #1d2327;
	font-size: 18px;
}

.section-header .description {
	margin: 0;
	color: #646970;
	font-size: 14px;
}

.email-list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 10px;
}

.email-item {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 10px 15px;
	background: #f9f9f9;
	border: 1px solid #e0e0e0;
	border-radius: 4px;
	font-size: 14px;
}

.email-item .dashicons {
	color: #0073aa;
	font-size: 16px;
}

.email-link {
	color: #0073aa;
	text-decoration: none;
	flex: 1;
}

.email-link:hover {
	text-decoration: underline;
}

.admin-badge {
	background: #00a32a;
	color: white;
	padding: 2px 6px;
	border-radius: 8px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
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

/* Responsive design */
@media (max-width: 768px) {
	.table-controls {
		flex-direction: column;
		gap: 15px;
		align-items: flex-start;
	}

	.table-info {
		flex-direction: column;
		gap: 8px;
		align-items: flex-start;
	}

	.wp-list-table {
		font-size: 14px;
	}

	.wp-list-table th,
	.wp-list-table td {
		padding: 8px 10px;
	}

	.email-list {
		grid-template-columns: 1fr;
	}

	/* Mobile-specific link cell styles */
	.link-cell {
		font-size: 12px;
		padding: 4px 8px;
		gap: 4px;
	}

	.link-cell .dashicons-admin-links,
	.link-cell .dashicons-external {
		font-size: 12px;
	}

	/* Mobile-specific pageviews styles */
	.pageviews-cell {
		padding: 6px 10px;
		min-height: 28px;
		gap: 6px;
	}

	.pageviews-cell .dashicons-visibility {
		font-size: 12px;
	}

	.pageviews-number {
		font-size: 12px;
	}

	/* Adjust column widths for mobile */
	.column-value {
		width: 30%;
	}

	.column-pageviews {
		width: 25%;
	}
}
</style>
