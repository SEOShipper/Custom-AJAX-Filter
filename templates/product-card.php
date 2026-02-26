<?php
/**
 * Product card template
 *
 * Used in both initial render and AJAX responses
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();

// Get product label for card tag (e.g., "High Flow", "Industrial")
$product_labels = get_the_terms($post_id, 'product_label');
$label_name = '';
if ($product_labels && !is_wp_error($product_labels)) {
    $label_name = $product_labels[0]->name;
}

// Get meta fields
$flow_rate = get_post_meta($post_id, '_product_flow_rate', true);
$micron = get_post_meta($post_id, '_product_micron', true);
$temp = get_post_meta($post_id, '_product_temp', true);
$description = get_post_meta($post_id, '_product_description', true);

// Get thumbnail
$thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
if (!$thumbnail_url) {
    // Simple gray placeholder as data URI
    $thumbnail_url = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"%3E%3Crect fill="%23f0f0f0" width="400" height="300"/%3E%3Ctext x="50%25" y="50%25" font-family="sans-serif" font-size="16" fill="%239ca3af" text-anchor="middle" dy=".3em"%3ENo Image%3C/text%3E%3C/svg%3E';
}

// Quote URL — fallback href; JS opens Elementor popup when configured
$quote_url = isset($quote_url) ? $quote_url : '/contact/';
$quote_url_with_product = add_query_arg('product', urlencode(get_the_title()), $quote_url);
?>

<article class="apf-product-card" data-id="<?php echo esc_attr($post_id); ?>">

    <!-- Product Image -->
    <div class="apf-card-image">
        <a href="<?php the_permalink(); ?>">
            <img
                src="<?php echo esc_url($thumbnail_url); ?>"
                alt="<?php echo esc_attr(get_the_title()); ?>"
                loading="lazy"
            />
        </a>
    </div>

    <!-- Card Content -->
    <div class="apf-card-content">

        <!-- Product Label Tag -->
        <?php if ($label_name) : ?>
        <span class="apf-category-tag"><?php echo esc_html($label_name); ?></span>
        <?php endif; ?>

        <!-- Title -->
        <h3 class="apf-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <!-- Description or Specifications -->
        <?php if (!empty($show_description) && $description) : ?>
        <p class="apf-card-description"><?php echo esc_html($description); ?></p>
        <?php elseif ($flow_rate || $micron || $temp) : ?>
        <dl class="apf-specs-table">
            <?php if ($flow_rate) : ?>
            <div class="apf-spec-row">
                <dt>Flow Rate:</dt>
                <dd><?php echo esc_html($flow_rate); ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($micron) : ?>
            <div class="apf-spec-row">
                <dt>Micron:</dt>
                <dd><?php echo esc_html($micron); ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($temp) : ?>
            <div class="apf-spec-row">
                <dt>Temp:</dt>
                <dd><?php echo esc_html($temp); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="apf-card-actions">
            <a href="<?php echo esc_url($quote_url_with_product); ?>" class="apf-btn apf-btn-primary apf-quote-btn">
                Get Quote
            </a>
            <a href="<?php the_permalink(); ?>" class="apf-btn apf-btn-secondary">
                View Details
            </a>
        </div>

    </div>

</article>
