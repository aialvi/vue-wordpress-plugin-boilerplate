<template>
	<div class="settings-tab">
		<header class="settings-header">
			<h2>{{ __('Plugin Settings', 'aialvi-page-ranks') }}</h2>
			<p>
				{{
					__(
						'Configure how the plugin displays data and handles notifications.',
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
			:aria-label="__('Loading settings', 'aialvi-page-ranks')"
		>
			<div class="spinner is-active" aria-hidden="true" />
			<p>{{ __('Loading settings...', 'aialvi-page-ranks') }}</p>
		</div>

		<!-- Settings Form -->
		<form
			v-else
			class="settings-form"
			:aria-label="__('Plugin settings form', 'aialvi-page-ranks')"
			@submit.prevent="saveSettings"
		>
			<!-- Table Rows Setting -->
			<fieldset class="form-group">
				<div class="form-row">
					<div class="form-label">
						<label for="table_rows">
							{{
								__(
									'Number of table rows to display',
									'aialvi-page-ranks'
								)
							}}
						</label>
						<p id="table_rows_description" class="description">
							{{
								__(
									'Choose how many rows to show in the data table (1-5).',
									'aialvi-page-ranks'
								)
							}}
						</p>
					</div>
					<div class="form-control">
						<input
							id="table_rows"
							v-model.number="localSettings.table_rows"
							type="number"
							min="1"
							max="5"
							step="1"
							class="regular-text"
							:class="{ error: validationErrors.table_rows }"
							:aria-describedby="
								validationErrors.table_rows
									? 'table_rows_error table_rows_description'
									: 'table_rows_description'
							"
							:aria-invalid="!!validationErrors.table_rows"
							@input="validateAndClampTableRows($event)"
							@change="
								updateSettingInstantly(
									'table_rows',
									localSettings.table_rows
								)
							"
						/>
						<div
							v-if="validationErrors.table_rows"
							id="table_rows_error"
							class="error-message"
							role="alert"
						>
							{{ validationErrors.table_rows }}
						</div>
					</div>
				</div>
			</fieldset>

			<!-- Date Format Setting -->
			<fieldset class="form-group">
				<div class="form-row">
					<div class="form-label">
						<legend>
							{{
								__('Date format in table', 'aialvi-page-ranks')
							}}
						</legend>
						<p id="date_format_description" class="description">
							{{
								__(
									'Choose how dates should be displayed in the data table.',
									'aialvi-page-ranks'
								)
							}}
						</p>
					</div>
					<div class="form-control">
						<div
							role="radiogroup"
							aria-describedby="date_format_description"
						>
							<label class="radio-label">
								<input
									v-model="localSettings.date_format"
									type="radio"
									name="date_format"
									value="human"
									@change="
										updateSettingInstantly(
											'date_format',
											'human'
										)
									"
								/>
								{{ __('Human readable', 'aialvi-page-ranks') }}
								<span class="example"
									>(e.g., {{ dateFormatExample.human }})</span
								>
							</label>
							<label class="radio-label">
								<input
									v-model="localSettings.date_format"
									type="radio"
									name="date_format"
									value="timestamp"
									@change="
										updateSettingInstantly(
											'date_format',
											'timestamp'
										)
									"
								/>
								{{ __('Timestamp', 'aialvi-page-ranks') }}
								<span class="example"
									>(e.g.,
									{{ dateFormatExample.timestamp }})</span
								>
							</label>
						</div>
					</div>
				</div>
			</fieldset>

			<!-- Notification Emails Setting -->
			<fieldset class="form-group">
				<div class="form-row">
					<div class="form-label">
						<legend>
							{{ __('Notification emails', 'aialvi-page-ranks') }}
						</legend>
						<p
							id="notification_emails_description"
							class="description"
						>
							{{
								__(
									'Add email addresses that should receive notifications (1-5 emails).',
									'aialvi-page-ranks'
								)
							}}
						</p>
					</div>
					<div class="form-control">
						<div
							class="email-list-wrapper"
							role="group"
							aria-describedby="notification_emails_description"
							:aria-label="
								sprintf(
									__(
										'%d email addresses configured',
										'aialvi-page-ranks'
									),
									localSettings.notification_emails.length
								)
							"
						>
							<div
								v-for="(
									email, index
								) in localSettings.notification_emails"
								:key="`email-${index}`"
								class="email-input-row"
							>
								<label
									:for="`email_${index}`"
									class="screen-reader-text"
								>
									{{
										sprintf(
											__(
												'Email address %d',
												'aialvi-page-ranks'
											),
											index + 1
										)
									}}
								</label>
								<input
									:id="`email_${index}`"
									v-model="
										localSettings.notification_emails[index]
									"
									type="email"
									class="regular-text"
									:class="{
										error: validationErrors[
											`email_${index}`
										],
									}"
									:placeholder="
										__(
											'Enter email address',
											'aialvi-page-ranks'
										)
									"
									:aria-describedby="
										validationErrors[`email_${index}`]
											? `email_${index}_error`
											: null
									"
									:aria-invalid="
										!!validationErrors[`email_${index}`]
									"
									@input="validateEmailField(index)"
								/>
								<button
									v-if="
										localSettings.notification_emails
											.length > 1
									"
									type="button"
									class="button button-secondary button-small remove-email-button"
									:aria-label="
										sprintf(
											__(
												'Remove email address %d',
												'aialvi-page-ranks'
											),
											index + 1
										)
									"
									@click="removeEmail(index)"
								>
									<span
										class="dashicons dashicons-minus"
										aria-hidden="true"
									/>
								</button>
								<div
									v-if="validationErrors[`email_${index}`]"
									:id="`email_${index}_error`"
									class="error-message"
									role="alert"
								>
									{{ validationErrors[`email_${index}`] }}
								</div>
							</div>
							<button
								v-if="
									localSettings.notification_emails.length < 5
								"
								type="button"
								class="button button-secondary button-small"
								:aria-label="
									__(
										'Add another email address',
										'aialvi-page-ranks'
									)
								"
								@click="addEmail"
							>
								<span
									class="dashicons dashicons-plus"
									aria-hidden="true"
								/>
								{{ __('Add Email', 'aialvi-page-ranks') }}
							</button>
							<p v-else class="description" aria-live="polite">
								{{
									__(
										'Maximum of 5 email addresses allowed.',
										'aialvi-page-ranks'
									)
								}}
							</p>
						</div>
					</div>
				</div>
			</fieldset>

			<!-- Form Actions -->
			<div class="form-actions">
				<button
					type="submit"
					class="button button-primary"
					:disabled="saving || !isFormValid"
					:aria-describedby="saving ? 'save-status' : null"
				>
					<span v-if="saving" class="saving-indicator">
						<span class="spinner is-active" aria-hidden="true" />
						<span id="save-status">{{
							__('Saving...', 'aialvi-page-ranks')
						}}</span>
					</span>
					<span v-else>
						{{ __('Save Settings', 'aialvi-page-ranks') }}
					</span>
				</button>

				<button
					type="button"
					class="button button-secondary"
					:disabled="saving"
					:aria-label="
						__(
							'Reset all settings to default values',
							'aialvi-page-ranks'
						)
					"
					@click="resetSettings"
				>
					{{ __('Reset to Defaults', 'aialvi-page-ranks') }}
				</button>
			</div>

			<!-- Messages -->
			<div
				v-if="storeSuccess || saveMessage"
				class="notice notice-success"
				role="status"
				aria-live="polite"
			>
				<p>
					<span
						class="dashicons dashicons-yes-alt"
						aria-hidden="true"
					/>
					{{ storeSuccess || saveMessage }}
				</p>
			</div>

			<div
				v-if="storeError || saveError"
				class="notice notice-error"
				role="alert"
				aria-live="assertive"
			>
				<p>
					<span
						class="dashicons dashicons-warning"
						aria-hidden="true"
					/>
					<strong>{{ __('Error:', 'aialvi-page-ranks') }}</strong>
					{{ storeError || saveError }}
				</p>
			</div>
		</form>
	</div>
</template>

<script>
import { computed, reactive, ref, watch, onMounted, onUnmounted } from 'vue';
import { useStore } from 'vuex';

export default {
	name: 'SettingsTab',
	setup() {
		const store = useStore();

		// Local state
		const localSettings = reactive({
			table_rows: 5,
			date_format: 'human',
			notification_emails: [''],
		});

		const validationErrors = reactive({});
		const saving = ref(false);
		const saveMessage = ref('');
		const saveError = ref('');

		// Computed properties
		const loading = computed(() => store.state.loading);
		const storeSuccess = computed(() => store.state.success);
		const storeError = computed(() => store.state.error);

		// Date format examples for UI
		const dateFormatExample = computed(() => {
			const now = new Date();
			return {
				human: now.toLocaleDateString(),
				timestamp: Math.floor(now.getTime() / 1000),
			};
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

		// Initialize local settings from store
		const initializeSettings = () => {
			const storeSettings = store.state.settings;
			localSettings.table_rows = storeSettings.table_rows || 5;
			localSettings.date_format = storeSettings.date_format || 'human';
			localSettings.notification_emails = [
				...(storeSettings.notification_emails || []),
			];

			// Ensure at least one email field
			if (localSettings.notification_emails.length === 0) {
				localSettings.notification_emails = [get_default_admin_email()];
			}
		};

		// Get default admin email
		const get_default_admin_email = () => {
			return window.aialviVuePlugin?.admin_email || '';
		};

		// Watch for store changes
		watch(
			() => store.state.settings,
			(newSettings, oldSettings) => {
				// Handle store changes if needed
			},
			{ deep: true }
		);

		// Validation functions
		const validateAndClampTableRows = event => {
			let value = event.target.value;

			// Allow empty values
			if (value === '' || value === null || value === undefined) {
				return;
			}

			// Convert to integer
			value = parseInt(value, 10);

			// Handle NaN case
			if (isNaN(value)) {
				event.target.value = localSettings.table_rows;
				return;
			}

			// Clamp between 1 and 5
			if (value > 5) {
				value = 5;
			} else if (value < 1) {
				value = 1;
			}

			// Update the local settings and input field
			localSettings.table_rows = value;
			event.target.value = value;

			// Clear any validation errors since we've clamped the value
			delete validationErrors.table_rows;
		};

		const validateEmailField = index => {
			const email = localSettings.notification_emails[index]?.trim();
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			// Clear existing error for this field
			delete validationErrors[`email_${index}`];

			if (email) {
				if (!emailRegex.test(email)) {
					validationErrors[`email_${index}`] = __(
						'Please enter a valid email address.',
						'aialvi-page-ranks'
					);
				}
			}

			// Re-validate all other emails to clear/set duplicate errors
			validateAllEmails();
		};

		const validateAllEmails = () => {
			const emailCounts = {};
			const duplicateEmails = new Set();

			// First pass: count all emails and identify duplicates
			localSettings.notification_emails.forEach((email, index) => {
				const trimmedEmail = email?.trim().toLowerCase();
				if (trimmedEmail) {
					emailCounts[trimmedEmail] =
						(emailCounts[trimmedEmail] || 0) + 1;
					if (emailCounts[trimmedEmail] > 1) {
						duplicateEmails.add(trimmedEmail);
					}
				}
			});

			// Second pass: set duplicate errors for all occurrences of duplicate emails
			localSettings.notification_emails.forEach((email, index) => {
				const trimmedEmail = email?.trim().toLowerCase();
				if (duplicateEmails.has(trimmedEmail)) {
					validationErrors[`email_${index}`] = __(
						'This email address is already added.',
						'aialvi-page-ranks'
					);
				}
			});
		};

		const validateSettings = () => {
			const errors = {};

			// Validate table rows
			if (localSettings.table_rows < 1 || localSettings.table_rows > 5) {
				errors.table_rows = __(
					'Number of rows must be between 1 and 5.',
					'aialvi-page-ranks'
				);
			}

			// Validate emails
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			const validEmails = localSettings.notification_emails.filter(
				email => email && email.trim() !== ''
			);

			if (validEmails.length === 0) {
				errors.notification_emails = __(
					'At least one email address is required.',
					'aialvi-page-ranks'
				);
			} else if (validEmails.length > 5) {
				errors.notification_emails = __(
					'Maximum of 5 email addresses allowed.',
					'aialvi-page-ranks'
				);
			}

			// Check for duplicate emails (case insensitive)
			const emailCounts = {};
			let hasDuplicates = false;

			localSettings.notification_emails.forEach((email, index) => {
				const trimmedEmail = email?.trim().toLowerCase();
				if (trimmedEmail) {
					emailCounts[trimmedEmail] =
						(emailCounts[trimmedEmail] || 0) + 1;
					if (emailCounts[trimmedEmail] > 1) {
						hasDuplicates = true;
					}
				}
			});

			// If there are duplicates, mark all duplicate emails with error
			if (hasDuplicates) {
				errors.notification_emails = __(
					'Duplicate email addresses are not allowed.',
					'aialvi-page-ranks'
				);
			}

			// Clear old errors and set new ones
			Object.keys(validationErrors).forEach(key => {
				delete validationErrors[key];
			});
			Object.assign(validationErrors, errors);

			return Object.keys(errors).length === 0;
		};

		// Check form validity
		const isFormValid = computed(() => {
			const hasTableRowsError =
				localSettings.table_rows < 1 || localSettings.table_rows > 5;

			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			const validEmails = localSettings.notification_emails.filter(
				email => email && email.trim() !== ''
			);

			const hasEmailErrors =
				validEmails.length === 0 || validEmails.length > 5;

			// Check for email format errors
			const hasFormatErrors = localSettings.notification_emails.some(
				(email, index) => {
					return (
						email &&
						email.trim() !== '' &&
						!emailRegex.test(email.trim())
					);
				}
			);

			// Check for duplicate emails (case insensitive)
			const emailCounts = {};
			const hasDuplicates = localSettings.notification_emails.some(
				(email, index) => {
					const trimmedEmail = email?.trim().toLowerCase();
					if (trimmedEmail) {
						emailCounts[trimmedEmail] =
							(emailCounts[trimmedEmail] || 0) + 1;
						return emailCounts[trimmedEmail] > 1;
					}
					return false;
				}
			);

			return (
				!hasTableRowsError &&
				!hasEmailErrors &&
				!hasFormatErrors &&
				!hasDuplicates
			);
		});

		// Email management
		const addEmail = () => {
			if (localSettings.notification_emails.length < 5) {
				localSettings.notification_emails.push('');
			}
		};

		const removeEmail = index => {
			if (localSettings.notification_emails.length > 1) {
				localSettings.notification_emails.splice(index, 1);

				// Clear any validation errors for removed indices
				Object.keys(validationErrors).forEach(key => {
					if (key.startsWith('email_')) {
						const errorIndex = parseInt(key.split('_')[1]);
						if (errorIndex >= index) {
							delete validationErrors[key];
						}
					}
				});

				// Re-validate remaining emails
				validateAllEmails();
			}
		};

		// Debounce instant updates to prevent API call pileups
		const instantUpdateTimeouts = {};

		const updateSettingInstantly = async (key, value) => {
			try {
				// Clear any existing timeout for this key
				if (instantUpdateTimeouts[key]) {
					clearTimeout(instantUpdateTimeouts[key]);
				}

				// Set a new timeout
				instantUpdateTimeouts[key] = setTimeout(async () => {
					// Implementation for instant updates would go here
					// Store instant update or send to API
					await store.dispatch('updateSetting', { [key]: value });
				}, 300); // 300ms debounce
			} catch (error) {
				saveError.value =
					error.message ||
					__('Failed to update setting.', 'aialvi-page-ranks');
			}
		};

		// Save all settings
		const saveSettings = async () => {
			if (!validateSettings()) {
				return;
			}

			saving.value = true;
			saveMessage.value = '';
			saveError.value = '';
			store.dispatch('clearMessages');

			try {
				// Filter out empty emails before sending
				const filteredEmails = localSettings.notification_emails.filter(
					email => email && email.trim() !== ''
				);

				// Update the store state first before calling saveSettings
				// The Vuex saveSettings action reads from state.settings
				store.commit('SET_SETTINGS', {
					table_rows: localSettings.table_rows,
					date_format: localSettings.date_format,
					notification_emails: filteredEmails,
				});

				// Call the Vuex saveSettings action
				await store.dispatch('saveSettings');

				// Update local settings to match what was saved
				localSettings.notification_emails = [...filteredEmails];

				saveMessage.value = __(
					'Settings saved successfully!',
					'aialvi-page-ranks'
				);

				// Clear success message after 3 seconds
				setTimeout(() => {
					saveMessage.value = '';
				}, 3000);
			} catch (error) {
				saveError.value =
					error.message ||
					__('Failed to save settings.', 'aialvi-page-ranks');
			} finally {
				saving.value = false;
			}
		};

		// Reset settings to defaults
		const resetSettings = () => {
			localSettings.table_rows = 5;
			localSettings.date_format = 'human';
			localSettings.notification_emails = [get_default_admin_email()];

			saveMessage.value = '';
			saveError.value = '';
			store.dispatch('clearMessages');

			// Clear validation errors
			Object.keys(validationErrors).forEach(key => {
				delete validationErrors[key];
			});
		};

		onMounted(() => {
			initializeSettings();
		});

		// Cleanup timeouts to prevent memory leaks
		onUnmounted(() => {
			Object.values(instantUpdateTimeouts).forEach(timeout => {
				clearTimeout(timeout);
			});
		});

		return {
			localSettings,
			validationErrors,
			loading,
			saving,
			saveMessage,
			saveError,
			storeSuccess,
			storeError,
			isFormValid,
			dateFormatExample,
			validateAndClampTableRows,
			validateEmailField,
			addEmail,
			removeEmail,
			updateSettingInstantly,
			saveSettings,
			resetSettings,
			__,
			sprintf,
		};
	},
};
</script>

<style scoped>
.settings-tab {
	max-width: 800px;
}

.settings-header {
	margin-bottom: 30px;
	padding-bottom: 20px;
	border-bottom: 1px solid #e0e0e0;
}

.settings-header h2 {
	margin: 0 0 8px 0;
	color: #1d2327;
	font-size: 22px;
}

.settings-header p {
	margin: 0;
	color: #646970;
	font-size: 14px;
}

.settings-form {
	background: #fff;
}

.form-group {
	margin-bottom: 35px;
	padding-bottom: 25px;
	border-bottom: 1px solid #f0f0f1;
	border: none;
	padding: 0 0 25px 0;
	margin: 0 0 35px 0;
}

.form-group:last-of-type {
	border-bottom: none;
}

.form-row {
	display: grid;
	grid-template-columns: 1fr 2fr;
	gap: 30px;
	align-items: start;
}

.form-label label,
.form-label legend {
	display: block;
	font-weight: 600;
	font-size: 14px;
	color: #1d2327;
	margin-bottom: 6px;
}

.form-label .description {
	margin: 0;
	font-size: 13px;
	color: #646970;
	line-height: 1.4;
}

.form-control {
	display: flex;
	flex-direction: column;
}

.regular-text {
	padding: 8px 12px;
	font-size: 14px;
	border: 1px solid #8c8f94;
	border-radius: 4px;
	width: 100%;
	max-width: 300px;
	min-width: 300px;
	transition:
		border-color 0.15s ease-in-out,
		box-shadow 0.15s ease-in-out;
}

.regular-text:focus {
	border-color: #2271b1;
	box-shadow: 0 0 0 1px #2271b1;
	outline: none;
}

.regular-text.error {
	border-color: #d63638;
}

.remove-email-button {
	min-height: 46px !important;
}

.remove-email-button span {
	margin-top: 4px;
}

.error-message {
	color: #d63638;
	font-size: 12px;
	margin-top: 6px;
}

fieldset {
	border: none;
	padding: 0;
	margin: 0;
}

.radio-label {
	display: block !important;
	font-weight: normal !important;
	margin-bottom: 12px !important;
	cursor: pointer;
	font-size: 14px;
}

.radio-label input[type='radio'] {
	margin-right: 8px;
}

.example {
	color: #646970;
	font-size: 12px;
	margin-left: 4px;
}

.email-list-wrapper {
	width: 100%;
}

.email-input-row {
	display: flex;
	align-items: flex-start;
	gap: 12px;
	margin-bottom: 12px;
}

.email-input-row input {
	flex: 1;
}

.email-input-row .button-small {
	min-height: auto;
	padding: 4px 8px;
	font-size: 12px;
	line-height: 1.4;
}

.form-actions {
	margin-top: 40px;
	padding-top: 25px;
	border-top: 1px solid #e0e0e0;
	display: flex;
	gap: 12px;
	align-items: center;
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

.button-primary:disabled {
	background: #a7aaad !important;
	border-color: #a7aaad !important;
	cursor: not-allowed;
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

.button-secondary:disabled {
	background: #f6f7f7 !important;
	border-color: #dcdcde !important;
	color: #a7aaad !important;
	cursor: not-allowed;
}

.saving-indicator {
	display: flex;
	align-items: center;
	gap: 6px;
}

.notice {
	background: #fff;
	border-left: 4px solid #72aee6;
	box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
	margin: 20px 0;
	padding: 12px;
}

.notice p {
	margin: 0;
	display: flex;
	align-items: center;
	gap: 8px;
}

.notice-success {
	border-left-color: #00a32a;
}

.notice-error {
	border-left-color: #d63638;
}

.screen-reader-text {
	clip: rect(1px, 1px, 1px, 1px);
	position: absolute !important;
	height: 1px;
	width: 1px;
	overflow: hidden;
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
}

.dashicons {
	font-size: 16px;
	line-height: 1;
	text-decoration: none;
}

.dashicons-plus {
	margin-top: 6px;
}

/* Responsive design */
@media (max-width: 768px) {
	.form-row {
		grid-template-columns: 1fr;
		gap: 15px;
	}

	.form-actions {
		flex-direction: column;
		align-items: flex-start;
	}
}
</style>
