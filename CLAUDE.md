# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository contains the **Exopite Multifilter** WordPress plugin - an AJAX-based filtering and sorting system for WordPress posts and custom post types. The plugin is located in `docs/Exopite-Multifilter-Multi-Sorter-WordPress-Plugin-master/exopite-multifilter/`.

## Architecture

### Directory Structure
```
exopite-multifilter/
├── exopite-multifilter.php    # Main plugin bootstrap file
├── includes/
│   ├── class-exopite-multifilter.php        # Core plugin class
│   ├── class-exopite-multifilter-loader.php # Hook/action/filter loader
│   └── class-exopite-multifilter-i18n.php   # Internationalization
├── public/
│   ├── class-exopite-multifilter-public.php # Frontend functionality, shortcode, AJAX handlers
│   ├── js/                                   # JavaScript files (*.dev.js and *.min.js)
│   └── css/                                  # Stylesheets (*.dev.css and *.min.css)
├── admin/
│   └── class-exopite-multifilter-admin.php  # Admin hooks (minimal)
└── vendor/
    └── plugin-update-checker/               # Auto-update system
```

### Key Entry Points
- **Bootstrap**: `exopite-multifilter.php` - Initializes constants, includes the core class, runs the plugin
- **Core Logic**: `includes/class-exopite-multifilter.php` - Loads dependencies, defines hooks
- **Frontend**: `public/class-exopite-multifilter-public.php` - Handles shortcode rendering, AJAX filtering, pagination
- **JavaScript**: `public/js/exopite-multifilter-public.dev.js` - jQuery plugin for AJAX interactions

### Plugin Pattern
Uses WordPress standard hook-based architecture with a Loader class that manages actions, filters, and shortcodes. The main class orchestrates initialization through `load_dependencies()`, `set_locale()`, `define_admin_hooks()`, and `define_public_hooks()` methods.

### JavaScript Architecture
- `exopite-core.dev.js` - Utility functions (debounce, throttle, viewport detection, URL manipulation) and WP-JS-Hooks event system
- `exopite-multifilter-public.dev.js` - jQuery plugin `$.fn.exopiteMultifilter()` handling filter selection, AJAX loading, pagination, search

## Development

### Build System
No build tools configured. Files are maintained as paired development (`*.dev.js`/`*.dev.css`) and minified (`*.min.js`/`*.min.css`) versions. Scripts are versioned automatically based on file modification time.

### Development Mode
Toggle in `public/class-exopite-multifilter-public.php` line 82: `$this->development = false` - when true, loads `.dev` files; when false, loads `.min` files.

### Requirements
- WordPress 4.7+
- PHP 5.3+
- jQuery 1.9.1+

## Plugin Configuration

All configuration is via shortcode parameters (60+ options). No admin UI. Primary shortcode: `[exopite-multifilter]`

### Key Shortcode Parameters
- `post_type` - Post type slug
- `posts_per_page`, `posts_per_row` - Layout control
- `pagination` - 'pagination', 'readmore', 'infinite', or 'none'
- `style` - 'masonry', 'equal-height', 'carousel', 'timeline', or empty
- `include_taxonomies` - Taxonomies to display as filters
- `effect` - Hover effects: 'apollo', 'duke', 'goliath', 'julia', 'lexi', 'ming', 'steve'

## WordPress Hooks for Customization

PHP filters available:
- `exopite-multifilter-thumbnail-*` - Customize thumbnail URLs and links
- `exopite-multifilter-article-*` - Customize article rendering
- `exopite-multifilter-filter-taxonomy-name` - Customize filter taxonomy display

JavaScript hooks (via WP-JS-Hooks):
- `ema-before-send` - Before AJAX request
- `ema-success-animation-end` - After animation completion

## Known Issues

- Masonry-desandro layout has issues with lazy loading
- Mobile pagination displays 6 page numbers which may be too many
- `get_articles` and `exopite_multifilter_shortcode` functions are large and could be refactored
