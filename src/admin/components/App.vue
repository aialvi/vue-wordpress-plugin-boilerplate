<template>
	<div class="aialvi-admin-app">
		<div class="admin-header">
			<h1>{{ title }}</h1>
			<p class="description">{{ description }}</p>
		</div>

		<div class="admin-content">
			<p>Content</p>
			<!-- TODO: content will be here -->
		</div>
	</div>
</template>

<script>
import { ref } from "vue";

export default {
	name: "App",
	setup() {
		const title = ref("Aminul's Vue Plugin");
		const description = ref(
			"A powerful WordPress plugin built with Vue.js",
		);


		return {
			title,
			description,
		};
	},
};
</script>

<style scoped>
.aialvi-admin-app {
	max-width: 1200px;
	margin: 0;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
		sans-serif;
}

.admin-header {
	border-bottom: 1px solid #ddd;
	padding-bottom: 20px;
	margin-bottom: 30px;
}

.admin-header h1 {
	color: #23282d;
	font-size: 24px;
	margin: 0 0 10px 0;
}

.description {
	color: #666;
	font-size: 14px;
	margin: 0;
}

.admin-content {
	background: #fff;
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.admin-content p {
	font-size: 16px;
	color: #333;
}
</style>
