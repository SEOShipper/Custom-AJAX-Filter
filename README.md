# AJAX Product Filter

A WordPress plugin for AJAX-based product filtering on pages with custom taxonomies and meta fields.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Default terms are created automatically on activation

## Taxonomies

The plugin registers 7 taxonomies for the 'page' post type:

| Taxonomy | Admin Label | Filter | Purpose |
|----------|-------------|--------|---------|
| `page_type` | Page Type | No | Internal flag (assign "product" to filterable pages) |
| `product_type` | Product Type | No | Product category for filtering by page (e.g., "High Flow Filter Cartridge") |
| `product_label` | Product Label | No | Optional card tag display (e.g., "High Flow", "Industrial") |
| `product_brand` | Replace Brand | Yes | Filter by brand |
| `product_application` | Application | Yes | Filter by application |
| `product_flow_rate` | Flow Rate | Yes | Filter by flow rate |
| `product_micron` | Micron Filtering | Yes | Filter by micron |

### Default Terms Created on Activation

**Product Types:**
- High Flow Filter Cartridge
- Pleated Filter Cartridge
- String Wound Filter Cartridge
- Membrane Filter Cartridge
- Melt Blown Filter Cartridge
- Stainless Steel Filter Cartridge
- Stainless Steel Filter Housing
- FRP Membrane Housing
- Photoresist Filter Element

**Brands:** PALL, 3M CUNO, Integris, Parker, Graver, Pentair, Millipore, Sartorius, Roki

**Applications:** Petrochemical, Water Treatment, Food & Beverage, Pharmaceutical

**Flow Rates:** 0-5 GPM, 5-50 GPM, 50-100 GPM, 100-200 GPM, 200-500 GPM

**Micron Ratings:** 1-5 Microns, 5-25 Microns, 25-100 Microns

## Meta Fields

Product pages can have these custom fields (editable in the "Product Specifications" meta box):

- **Flow Rate** - e.g., "50-200 GPM"
- **Micron Rating** - e.g., "1-100 Microns"
- **Max Temperature** - e.g., "180°F"

## Usage

### Setting Up a Product Page

1. Create/edit a WordPress Page
2. In the "Page Type" taxonomy, check "product"
3. Assign a "Product Type" (e.g., "High Flow Filter Cartridge")
4. Optionally assign a "Product Label" for the card tag display
5. Assign appropriate filter taxonomies (Brand, Application, etc.)
6. Fill in Product Specifications meta fields
7. Set featured image for the card thumbnail
8. Use the Page Attributes > Order field for sort position

### Shortcodes

#### [product_filters]

Displays the filter sidebar.

```
[product_filters
  show_search="true"
  show_count="true"
  collapsed="false"
  cta_title="Need Help Selecting?"
  cta_text="Our engineers can help you choose the right filter"
  cta_url="/contact/"
  cta_button="Talk to an Engineer"
]
```

**Attributes:**
| Attribute | Description | Default |
|-----------|-------------|---------|
| `show_search` | Show search box | `true` |
| `show_count` | Show term counts | `true` |
| `collapsed` | Start sections collapsed | `false` |
| `cta_title` | CTA box heading | "Need Help Selecting?" |
| `cta_text` | CTA box description | (see above) |
| `cta_url` | CTA button link | `/contact/` |
| `cta_button` | CTA button text | "Talk to an Engineer" |

#### [product_grid]

Displays the product grid.

```
[product_grid
  columns="3"
  show_sort="true"
  quote_url="/contact/"
  title="High Flow Filter Cartridges"
  product_type="high-flow-filter-cartridge"
]
```

**Attributes:**
| Attribute | Description | Default |
|-----------|-------------|---------|
| `columns` | Number of columns (1-4) | `3` |
| `show_sort` | Show sort dropdown | `true` |
| `quote_url` | URL for "Get Quote" buttons | `/contact/` |
| `title` | Grid heading text | (empty) |
| `product_type` | Filter by product type slug | (empty = show all) |

### Category Page Example

For a category page like "High Flow Filter Cartridges", use the `product_type` attribute to show only products in that category:

```html
<div class="apf-filter-layout">
  [product_filters]
  [product_grid product_type="high-flow-filter-cartridge" title="High Flow Filter Cartridges"]
</div>
```

### Product Type Slugs

WordPress auto-generates slugs from term names:

| Term Name | Slug |
|-----------|------|
| High Flow Filter Cartridge | `high-flow-filter-cartridge` |
| Pleated Filter Cartridge | `pleated-filter-cartridge` |
| String Wound Filter Cartridge | `string-wound-filter-cartridge` |
| Membrane Filter Cartridge | `membrane-filter-cartridge` |
| Melt Blown Filter Cartridge | `melt-blown-filter-cartridge` |
| Stainless Steel Filter Cartridge | `stainless-steel-filter-cartridge` |
| Stainless Steel Filter Housing | `stainless-steel-filter-housing` |
| FRP Membrane Housing | `frp-membrane-housing` |
| Photoresist Filter Element | `photoresist-filter-element` |

You can also find slugs in WordPress admin: Pages → Product Type → click on a term.

### All Products Page Example

To show all products without filtering by type:

```html
<div class="apf-filter-layout">
  [product_filters]
  [product_grid title="All Products" columns="3"]
</div>
```

## URL Parameters

Filters sync with the URL for bookmarkable/shareable filtered views:

```
/products/?brand=pall,3m-cuno&application=petrochemical&sort=name-asc&s=high+flow
```

## Sort Options

- `featured` - Menu Order (Page Attributes > Order field)
- `name-asc` - Alphabetical A-Z
- `name-desc` - Alphabetical Z-A

## Requirements

- WordPress 5.0+
- PHP 7.0+
- jQuery (included with WordPress)

## Changelog

### 1.0.0
- Initial release
