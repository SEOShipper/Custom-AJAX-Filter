# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**AJAX Product Filter** (v2.0.0) — a WordPress plugin that registers a `product` Custom Post Type with AJAX-based filtering, custom taxonomies, meta fields, and a single product page template.

## Architecture

### Directory Structure
```
ajax-product-filter/
├── ajax-product-filter.php          # Main bootstrap (singleton, hooks, asset enqueueing)
├── includes/
│   ├── class-post-type.php          # CPT registration ('product')
│   ├── class-taxonomies.php         # Taxonomy registration (product_type, product_label, product_brand, etc.)
│   ├── class-meta-fields.php        # Meta boxes: specs, gallery, tabs
│   ├── class-shortcodes.php         # [product_filters] and [product_grid] shortcodes
│   └── class-ajax-handler.php       # AJAX endpoint for filtering
├── templates/
│   ├── single-product.php           # Single product page (3 sections: hero, why choose, results)
│   ├── filter-sidebar.php           # Filter sidebar template
│   ├── product-grid.php             # Product grid wrapper template
│   └── product-card.php             # Individual product card template
├── assets/
│   ├── css/
│   │   ├── filter.css               # Filter/grid frontend styles
│   │   ├── single-product.css       # Single product page styles
│   │   └── meta-box.css             # Admin meta box styles
│   ├── js/
│   │   ├── filter.js                # AJAX filter frontend logic
│   │   ├── single-product.js        # Gallery nav + tab switching
│   │   └── meta-box.js              # Admin gallery picker + tabs repeater
│   └── images/                      # Placeholder directory
```

### Key Classes (all singletons)
- **Ajax_Product_Filter** — Main orchestrator, loads dependencies, registers hooks
- **APF_Post_Type** — Registers `product` CPT (priority 0, WooCommerce conflict guard)
- **APF_Taxonomies** — Registers 6 taxonomies against `product` CPT
- **APF_Meta_Fields** — 3 meta boxes: specs (text fields), gallery (media picker), tabs (JSON repeater)
- **APF_Shortcodes** — `[product_filters]` and `[product_grid]` shortcodes
- **APF_Ajax_Handler** — `wp_ajax_apf_filter_products` endpoint with term count caching

### Post Type & Taxonomies
- **CPT**: `product` (has_archive, show_in_rest, dashicons-cart)
- **Taxonomies** (registered to `product`):
  - `product_type` — Product category (private, not in filters)
  - `product_label` — Display label for cards (private)
  - `product_brand` — Replace brand (public, filterable)
  - `product_application` — Application type (public, filterable)
  - `product_flow_rate` — Flow rate range (public, filterable)
  - `product_micron` — Micron filtering range (public, filterable)

### Meta Fields
- `_product_subtitle` — Subtitle text
- `_product_flow_rate`, `_product_micron`, `_product_temp` — Spec values
- `_product_rating` — Numeric rating (0-5)
- `_product_description` — Card description text
- `_product_gallery` — Comma-separated attachment IDs
- `_product_tabs` — JSON array of `{title, content}` objects

### Single Product Template
Three sections with filter hooks for customization:
1. **Hero** — Breadcrumb, gallery (main+thumbs+arrows), product info, rating, content, action buttons, dynamic tabs
2. **Why Choose** — Static features grid (`apf_why_choose_title`, `apf_why_choose_subtitle` filters)
3. **Real Results** — Stats cards (`apf_results_title`, `apf_results_subtitle` filters)

Template override: theme can provide `single-product.php` to override plugin template.

## Development

### Build System
No build tools. Single source files. Assets versioned via `APF_VERSION` constant.

### Asset Loading
- **Filter pages**: CSS/JS loaded conditionally when `[product_filters]` or `[product_grid]` shortcodes detected
- **Single product**: `single-product.css` + `single-product.js` loaded on `is_singular('product')`
- **Admin**: `meta-box.css` + `meta-box.js` loaded on product edit screens only

### Requirements
- WordPress 4.7+
- PHP 5.3+
- jQuery 1.9.1+

## Shortcode Reference

### `[product_filters]`
| Param | Default | Description |
|-------|---------|-------------|
| `show_search` | `true` | Show search input |
| `show_count` | `false` | Show term counts |
| `collapsed` | `false` | Start filter groups collapsed |

### `[product_grid]`
| Param | Default | Description |
|-------|---------|-------------|
| `columns` | `3` | Grid columns (1-4) |
| `quote_url` | `/contact/` | Quote button URL |
| `title` | empty | Grid heading |
| `product_type` | empty | Pre-filter by product_type slug |
| `product_application` | empty | Pre-filter by application slug |
| `limit` | empty | Max products (hides sort/count when set) |
| `show_description` | `false` | Show description instead of specs |
