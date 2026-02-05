<?php
/**
 * Filter sidebar template
 *
 * @var array $atts Shortcode attributes
 */

if (!defined('ABSPATH')) {
    exit;
}

$filter_data = APF_Shortcodes::get_filter_data();
$collapsed_class = $atts['collapsed'] ? 'apf-collapsed' : '';
?>

<aside class="apf-filter-sidebar" data-show-count="<?php echo $atts['show_count'] ? 'true' : 'false'; ?>">

    <!-- Mobile Toggle Button -->
    <button type="button" class="apf-mobile-toggle" aria-label="Toggle filters">
        <svg class="apf-icon-filter" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="4" y1="6" x2="20" y2="6"></line>
            <line x1="4" y1="12" x2="20" y2="12"></line>
            <line x1="4" y1="18" x2="20" y2="18"></line>
            <circle cx="8" cy="6" r="2" fill="currentColor"></circle>
            <circle cx="16" cy="12" r="2" fill="currentColor"></circle>
            <circle cx="10" cy="18" r="2" fill="currentColor"></circle>
        </svg>
        <span>Filters</span>
    </button>

    <!-- Filter Panel -->
    <div class="apf-filter-panel">

        <!-- Close button for mobile -->
        <button type="button" class="apf-panel-close" aria-label="Close filters">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Search Box -->
        <?php if ($atts['show_search']) : ?>
        <div class="apf-search-box">
            <input
                type="text"
                class="apf-search-input"
                placeholder="Search Products..."
                aria-label="Search products"
            />
        </div>
        <?php endif; ?>

        <!-- Filter Groups -->
        <div class="apf-filter-groups">
            <?php foreach ($filter_data as $taxonomy => $data) : ?>
            <div class="apf-filter-group <?php echo esc_attr($collapsed_class); ?>" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                <button type="button" class="apf-filter-header" aria-expanded="<?php echo $atts['collapsed'] ? 'false' : 'true'; ?>">
                    <span class="apf-filter-title"><?php echo esc_html($data['label']); ?></span>
                    <svg class="apf-chevron" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                <div class="apf-filter-content">
                    <?php foreach ($data['terms'] as $term) : ?>
                    <label class="apf-checkbox-label">
                        <input
                            type="checkbox"
                            class="apf-checkbox"
                            name="<?php echo esc_attr($taxonomy); ?>[]"
                            value="<?php echo esc_attr($term->slug); ?>"
                        />
                        <span class="apf-checkbox-custom"></span>
                        <span class="apf-checkbox-text"><?php echo esc_html($term->name); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Clear All Button -->
        <button type="button" class="apf-clear-all">
            Clear All Filters
        </button>

    </div>

    <!-- Mobile Overlay -->
    <div class="apf-overlay"></div>

</aside>
