#!/usr/bin/env node

/**
 * Create a clean ZIP file for WordPress.org submission
 *
 * This script creates a ZIP file that excludes hidden files, development files,
 * and other files that are not needed for the production plugin while keeping
 * the source code for transparency.
 */

import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Get plugin info from package.json
const packageJsonPath = path.join(path.dirname(__dirname), 'package.json');
const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
const pluginName = packageJson.name;

// Create release directory and file names
const releaseDir = 'release';
const tempDir = `${pluginName}`;
const zipFileName = `${pluginName}.zip`;
const zipFilePath = path.join(releaseDir, zipFileName);

console.log('🏗️  Creating clean plugin ZIP for WordPress.org submission...');
console.log(`📦 Plugin: ${pluginName}`);

// Files and directories to exclude
const excludePatterns = [
	// Hidden files
	'.DS_Store',
	'.editorconfig',
	'.gitignore',
	'.gitattributes',
	'.git',
	'.github',

	// Development files
	'node_modules',
	'npm-debug.log*',
	'yarn-debug.log*',
	'yarn-error.log*',
	'.npm',
	'.yarn',
	'.husky',

	// IDE files
	'.vscode',
	'.idea',
	'*.swp',
	'*.swo',
	'*~',

	// OS files
	'Thumbs.db',
	'ehthumbs.db',
	'Desktop.ini',

	// Build scripts (keep source, exclude build tools)
	'webpack.config*.js',
	'vite.config.js',
	'postcss.config.js',
	'tailwind.config.js',
	'scripts',

	// Package files
	'package*.json',
	'composer.lock',
	'pnpm-lock.yaml',
	'yarn.lock',

	// Release directory to avoid recursive inclusion
	'release',

	// Any existing zip files
	'*.zip',

	// Temporary files
	'tmp',
	'temp',
	'.tmp',
	'.temp',
];

try {
	// Change to the project root directory
	const projectRoot = path.dirname(__dirname);
	process.chdir(projectRoot);

	// Create release directory if it doesn't exist
	if (!fs.existsSync(releaseDir)) {
		console.log('📁 Creating release directory...');
		fs.mkdirSync(releaseDir);
	}

	// Clean up any existing temp directory
	if (fs.existsSync(tempDir)) {
		console.log('🧹 Cleaning up existing temp directory...');
		execSync(`rm -rf "${tempDir}"`, { stdio: 'inherit' });
	}

	// Create temp directory
	console.log('📁 Creating temporary directory...');
	fs.mkdirSync(tempDir);

	// Copy files excluding patterns
	console.log('📋 Copying files...');

	// Build rsync exclude arguments
	const excludeArgs = excludePatterns
		.map(pattern => `--exclude='${pattern}'`)
		.join(' ');

	// Copy all files except excluded ones
	execSync(`rsync -av ${excludeArgs} --exclude='${tempDir}' ./ ${tempDir}/`, {
		stdio: 'inherit',
		cwd: process.cwd(),
	});

	// Remove any hidden files that might have slipped through
	console.log('🔍 Removing any remaining hidden files...');
	try {
		execSync(`find "${tempDir}" -name ".*" -type f -delete`, {
			stdio: 'pipe',
		});
		execSync(`find "${tempDir}" -name ".*" -type d -empty -delete`, {
			stdio: 'pipe',
		});
	} catch (e) {
		// Ignore errors - some systems might not have find command
	}

	// Create the ZIP file in the release directory
	console.log('🗜️  Creating ZIP file...');
	execSync(`zip -r "${zipFilePath}" "${tempDir}"`, {
		stdio: 'inherit',
	});

	// Clean up temp directory
	console.log('🧹 Cleaning up...');
	execSync(`rm -rf "${tempDir}"`, { stdio: 'inherit' });

	// Get file size
	const stats = fs.statSync(zipFilePath);
	const fileSizeInMB = (stats.size / (1024 * 1024)).toFixed(2);

	console.log('✅ ZIP file created successfully!');
	console.log(`📦 File: ${zipFilePath}`);
	console.log(`📏 Size: ${fileSizeInMB} MB`);
	console.log('');
	console.log('🚀 This ZIP file is ready for WordPress.org submission!');
	console.log('');
	console.log("📋 What's included:");
	console.log('   ✅ All PHP source code');
	console.log('   ✅ Built CSS and JS files');
	console.log('   ✅ Vue.js components and source files');
	console.log('   ✅ Language files');
	console.log('   ✅ Documentation (README.md, readme.txt)');
	console.log('   ✅ License and plugin headers');
	console.log('   ✅ Vendor dependencies (composer autoload)');
	console.log('');
	console.log("🚫 What's excluded:");
	console.log('   ❌ Hidden files (.DS_Store, .gitignore, etc.)');
	console.log('   ❌ Development dependencies (node_modules)');
	console.log('   ❌ Build configuration files (webpack, vite, etc.)');
	console.log('   ❌ Development scripts and tools');
	console.log('   ❌ Package manager lock files');
	console.log('   ❌ Git hooks and Husky files');
	console.log('');
	console.log(`📁 Release saved to: ${releaseDir}/${zipFileName}`);
} catch (error) {
	console.error('❌ Error creating ZIP file:', error.message);

	// Clean up on error
	if (fs.existsSync(tempDir)) {
		execSync(`rm -rf "${tempDir}"`, { stdio: 'inherit' });
	}

	process.exit(1);
}
