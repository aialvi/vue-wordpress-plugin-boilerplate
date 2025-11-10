# Vue WordPress Plugin Boilerplate

A modern WordPress plugin boilerplate built with Vue 3, Vite, Vue Router, and
Vuex. This boilerplate provides a solid foundation for creating WordPress admin
plugins with a reactive Vue.js frontend and follows WordPress coding standards.

## Features

- ⚡ **Vue 3** with Composition API support
- 🚀 **Vite** for lightning-fast development with HMR (Hot Module Replacement)
- 📦 **Webpack** as an alternative build option
- 🛣️ **Vue Router** for single-page application routing
- 🗃️ **Vuex** for state management
- 📊 **Chart.js** integration with vue-chartjs
- 🔐 **WordPress Security** - Nonces, capability checks, and sanitization
- 📝 **WordPress Coding Standards** - PHPCS with WPCS, VIP standards
- 🎨 **Code Linting** - ESLint for JavaScript/Vue, PHPCS for PHP
- 🪝 **Git Hooks** - Pre-commit hooks with Husky and lint-staged
- 🌐 **REST API** integration with Axios
- 📦 **PSR-4 Autoloading** for PHP classes
- 🎯 **Component-based architecture** for both admin and public interfaces

## Tech Stack

### Frontend

- Vue 3.4+
- Vue Router 4
- Vuex 4
- Vite 5
- Axios
- Chart.js & vue-chartjs

### Backend

- PHP 7.4+
- WordPress REST API
- Composer for dependency management

### Development Tools

- @wordpress/scripts for linting and formatting
- ESLint with Vue plugin
- PHPCS with WordPress Coding Standards
- Husky for Git hooks
- Vite for dev server and building

## Getting Started

### Prerequisites

- Node.js (v16 or higher)
- Composer
- WordPress installation (local or remote)
- PHP 7.4 or higher

### Installation

1. **Clone or download this repository** into your WordPress plugins directory:

   ```bash
   cd wp-content/plugins/
   git clone <repository-url> your-plugin-name
   cd your-plugin-name
   ```

2. **Install JavaScript dependencies:**

   ```bash
   npm install
   ```

3. **Install PHP dependencies:**

   ```bash
   composer install
   composer dump-autoload
   ```

4. **Customize the plugin** (see Configuration section below)

5. **Start development:**

   ```bash
   npm run dev
   ```

6. **Activate the plugin** through the WordPress admin panel

## Configuration

After cloning, you'll want to customize the boilerplate for your plugin:

### 1. Update Plugin Headers

Edit `aialvi-vue-plugin.php`:

```php
/**
 * Plugin Name: Your Plugin Name
 * Description: Your plugin description
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: your-plugin-slug
 */
```

### 2. Update Constants

In `aialvi-vue-plugin.php`, update the constant prefix:

```php
define( 'YOUR_PLUGIN_VERSION', '1.0.0' );
define( 'YOUR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'YOUR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
```

### 3. Update Package Files

Edit `package.json`:

- Change `name`, `description`, `author`
- Update `text-domain` references

Edit `composer.json`:

- Update `name`, `description`
- Update namespace in `autoload.psr-4`

### 4. Rename Classes and Namespaces

- Rename all `AIALVI_*` class prefixes
- Update namespace in `composer.json` and PHP files
- Run `composer dump-autoload` after namespace changes

### 5. Update Build Configuration

Edit `vite.config.js`:

- Update `output.name` to match your plugin name
- Adjust paths if needed

## Development Workflow

### Development Mode

Start the Vite development server with hot module replacement:

```bash
npm run dev
```

The dev server runs on `http://localhost:3000` with HMR enabled. Changes to Vue
components will update instantly without page refresh.

### Building for Production

Build optimized production files:

```bash
npm run build
```

This creates minified, production-ready files in the `dist/` directory.

### Alternative: Webpack Build

If you prefer Webpack:

```bash
npm run build:webpack
```

### Create Distribution Package

Build and create a clean zip file for distribution:

```bash
npm run build:zip
```

This excludes development files and creates a production-ready zip in the
`release/` directory.

## Available Scripts

### JavaScript/Vue

- `npm run dev` - Start Vite development server
- `npm run build` - Build for production with Vite
- `npm run build:webpack` - Build with Webpack
- `npm run build:zip` - Build and create distribution zip
- `npm run lint:js` - Lint JavaScript/Vue files
- `npm run lint:js:fix` - Fix JavaScript/Vue linting issues
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting

### PHP

- `npm run lint:php` - Lint PHP files with PHPCS
- `npm run lint:php:fix` - Fix PHP coding standards issues
- `composer run lint:phpcs` - Lint PHP (via Composer)
- `composer run fix:phpcs` - Fix PHP issues (via Composer)

### Combined

- `npm run lint` - Lint both JavaScript and PHP
- `npm run lint:fix` - Fix all linting issues

## Project Structure

```
your-plugin/
├── aialvi-vue-plugin.php          # Main plugin file
├── includes/                       # Core plugin classes
│   └── class-aialvi-vue-plugin.php
├── src/
│   ├── admin/                     # Admin interface
│   │   ├── main.js               # Admin entry point
│   │   ├── components/           # Vue components
│   │   │   ├── App.vue
│   │   │   ├── GraphTab.vue
│   │   │   ├── SettingsTab.vue
│   │   │   └── TableTab.vue
│   │   ├── router/               # Vue Router configuration
│   │   └── store/                # Vuex store
│   ├── public/                    # Public-facing interface
│   │   ├── main.js
│   │   └── components/
│   └── php/                       # PHP backend classes
│       ├── class-aialvi-vue-plugin-admin.php
│       ├── class-aialvi-vue-plugin-public.php
│       └── class-aialvi-vue-plugin-security.php
├── dist/                          # Built files (generated)
├── vendor/                        # PHP dependencies
├── node_modules/                  # Node dependencies
├── scripts/                       # Build scripts
│   └── create-clean-zip.js
├── languages/                     # Translation files
├── vite.config.js                 # Vite configuration
├── webpack.config.js              # Webpack configuration
├── package.json                   # Node dependencies
└── composer.json                  # PHP dependencies
```

## Key Concepts

### Vue Components

Components are located in `src/admin/components/`. The main `App.vue` serves as
the root component with routing enabled.

### Routing

Vue Router is configured in `src/admin/router/index.js`. Add new routes for
different admin pages:

```javascript
const routes = [
  { path: '/', component: TableTab },
  { path: '/graph', component: GraphTab },
  { path: '/settings', component: SettingsTab },
];
```

### State Management

Vuex store is configured in `src/admin/store/index.js` for centralized state
management.

### WordPress Integration

The plugin enqueues scripts conditionally and includes:

- Nonce verification for security
- REST API endpoints
- Proper WordPress hooks
- Admin menu registration
- Asset loading with version numbers

### Security

The boilerplate includes:

- `class-aialvi-vue-plugin-security.php` for nonce handling
- Capability checks (`manage_options`)
- Input sanitization and validation
- Escaped output in templates

## Linting and Code Quality

### Pre-commit Hooks

Husky and lint-staged automatically run linters before commits:

- JavaScript/Vue files: ESLint + Prettier
- PHP files: PHPCS with WordPress standards
- JSON/Markdown: Prettier formatting

### PHP Coding Standards

The boilerplate follows:

- WordPress Coding Standards (WPCS)
- WordPress VIP Coding Standards
- PHP Compatibility checks

### JavaScript Standards

- ESLint with Vue plugin
- WordPress scripts configuration
- Prettier for consistent formatting

## Building Features

### Adding a New Admin Page

1. Create a new component in `src/admin/components/`
2. Add route in `src/admin/router/index.js`
3. Update navigation in your components
4. Register any new REST API endpoints in PHP

### Adding REST API Endpoints

Create endpoints in `src/php/class-*-admin.php`:

```php
public function register_routes() {
    register_rest_route( 'your-plugin/v1', '/endpoint', array(
        'methods' => 'GET',
        'callback' => array( $this, 'get_data' ),
        'permission_callback' => array( $this, 'check_permissions' )
    ));
}
```

### Using Vuex Store

```javascript
// In your component
import { useStore } from 'vuex';

const store = useStore();
store.dispatch('fetchData');
```

## Production Deployment

1. **Build the plugin:**

   ```bash
   npm run build:zip
   ```

2. **Test the zip file** on a staging environment

3. **Upload to WordPress.org** or distribute via your preferred method

4. Files included in distribution:
   - Main plugin file
   - `/includes/` directory
   - `/src/php/` directory
   - `/dist/` directory (built assets)
   - `/languages/` directory
   - `readme.txt` for WordPress.org

5. Files excluded from distribution:
   - `/node_modules/`
   - `/src/admin/` and `/src/public/` (source files)
   - Development configuration files
   - `.git/` directory

## Troubleshooting

### Vite Dev Server Issues

If HMR isn't working:

1. Check that port 3000 is available
2. Verify `SCRIPT_DEBUG` is enabled in `wp-config.php`
3. Check browser console for CORS errors

### Build Errors

If build fails:

1. Delete `node_modules/` and `dist/`
2. Run `npm install` again
3. Clear npm cache: `npm cache clean --force`

### PHP Errors

1. Check PHP version compatibility (7.4+)
2. Run `composer dump-autoload`
3. Check error logs in WordPress debug mode

## Contributing

Feel free to fork this boilerplate and adapt it to your needs. Contributions are
welcome!

## License

This boilerplate is licensed under the MIT License. You can use it for any
purpose, including commercial projects.

## Credits

Created by [Aminul Islam Alvi](https://aialvi.me)

## Support

For issues, questions, or contributions, please use the repository's issue
tracker.

---

**Happy coding!**
