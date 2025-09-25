const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const { resolve } = require('path');
const CopyPlugin = require('copy-webpack-plugin');
const unixify = require('unixify');
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
	...defaultConfig,
	entry: {
		admin: './src/admin/main.js',
		public: './src/public/main.js',
	},
	output: {
		...defaultConfig.output,
		path: resolve(__dirname, 'dist'),
		filename: '[name].bundle.js',
		publicPath: '/wp-content/plugins/aialvi-page-ranks/dist/',
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: ['.js', '.vue', '.json'],
		alias: {
			...defaultConfig.resolve.alias,
			vue: 'vue/dist/vue.esm-bundler.js',
			'@': resolve(__dirname, 'src'),
		},
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.vue$/,
				loader: 'vue-loader',
			},
			{
				test: /\.scss$/,
				use: ['style-loader', 'css-loader', 'sass-loader'],
			},
			{
				test: /\.css$/,
				use: ['style-loader', 'css-loader'],
			},
		],
	},
	plugins: [
		...defaultConfig.plugins,
		new VueLoaderPlugin(),
		new CopyPlugin({
			patterns: [
				{
					from: unixify(resolve(__dirname, 'assets')),
					to: unixify(resolve(__dirname, 'dist/assets')),
					noErrorOnMissing: true,
				},
			],
		}),
	],
	externals: {
		...defaultConfig.externals,
		jquery: 'jQuery',
	},
};
