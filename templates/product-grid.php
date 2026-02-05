<?php
/**
 * Product grid template
 *
 * @var array $atts Shortcode attributes
 * @var WP_Query $products Product query
 */

if (!defined('ABSPATH')) {
    exit;
}

$columns = intval($atts['columns']);
$quote_url = esc_url($atts['quote_url']);
$product_type = isset($atts['product_type']) ? $atts['product_type'] : '';
$product_application = isset($atts['product_application']) ? $atts['product_application'] : '';
$limit = isset($atts['limit']) ? $atts['limit'] : '';
$show_count = !empty($atts['show_count']);
$show_sort = !empty($atts['show_sort']);
$show_header = !empty($atts['title']) || $show_count || $show_sort;
?>

<div class="apf-product-grid-wrapper" data-columns="<?php echo esc_attr($columns); ?>" data-quote-url="<?php echo esc_attr($quote_url); ?>" data-product-type="<?php echo esc_attr($product_type); ?>" data-product-application="<?php echo esc_attr($product_application); ?>" data-limit="<?php echo esc_attr($limit); ?>">

    <?php if ($show_header) : ?>
    <!-- Grid Header -->
    <div class="apf-grid-header">
        <div class="apf-grid-info">
            <?php if (!empty($atts['title'])) : ?>
            <h2 class="apf-grid-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            <?php if ($show_count) : ?>
            <span class="apf-product-count">
                <span class="apf-count-number"><?php echo $products->found_posts; ?></span>
            </span>
            <?php endif; ?>
        </div>

        <?php if ($show_sort) : ?>
        <div class="apf-sort-wrapper">
            <label for="apf-sort-select" class="apf-sort-label">Sort by:</label>
            <select id="apf-sort-select" class="apf-sort-select">
                <option value="featured">Featured</option>
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
            </select>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Product Grid -->
    <div class="apf-product-grid apf-columns-<?php echo esc_attr($columns); ?>">
        <?php if ($products->have_posts()) : ?>
            <?php while ($products->have_posts()) : $products->the_post(); ?>
                <?php include APF_PLUGIN_DIR . 'templates/product-card.php'; ?>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="apf-no-results">
                <p>No products found.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Loading Overlay -->
    <div class="apf-loading-overlay">
        <div class="apf-spinner"></div>
    </div>

</div>
