<?php
/**
 * Single Product Template
 *
 * Sections:
 *   1. Breadcrumb Bar – Full-width gray bar
 *   2. Hero – Gallery + product info side-by-side
 *   3. Tabs – Full-width section with tabbed content
 *   4. Why Choose – 4 feature cards with green icons and bullet lists
 *   5. Real Results – Case study image cards with blue accent areas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id  = get_the_ID();
	$subtitle = get_post_meta( $post_id, '_product_subtitle', true );

	// Gallery
	$gallery_ids = get_post_meta( $post_id, '_product_gallery', true );
	$gallery     = array();
	if ( ! empty( $gallery_ids ) ) {
		foreach ( explode( ',', $gallery_ids ) as $aid ) {
			$aid  = intval( $aid );
			$full = wp_get_attachment_image_url( $aid, 'large' );
			$thumb = wp_get_attachment_image_url( $aid, 'thumbnail' );
			$alt  = get_post_meta( $aid, '_wp_attachment_image_alt', true );
			if ( $full ) {
				$gallery[] = array(
					'full'  => $full,
					'thumb' => $thumb ? $thumb : $full,
					'alt'   => $alt ? $alt : get_the_title(),
				);
			}
		}
	}

	// Fallback: use featured image if no gallery
	if ( empty( $gallery ) ) {
		$feat = get_the_post_thumbnail_url( $post_id, 'large' );
		if ( $feat ) {
			$gallery[] = array(
				'full'  => $feat,
				'thumb' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ),
				'alt'   => get_the_title(),
			);
		}
	}

	// Tabs
	$tabs_json = get_post_meta( $post_id, '_product_tabs', true );
	$tabs      = ! empty( $tabs_json ) ? json_decode( $tabs_json, true ) : array();
	if ( ! is_array( $tabs ) ) {
		$tabs = array();
	}

	// Product type for breadcrumb
	$product_types = get_the_terms( $post_id, 'product_type' );
	$type_name     = '';
	$type_link     = '';
	if ( $product_types && ! is_wp_error( $product_types ) ) {
		$type_name = $product_types[0]->name;
		$type_link = get_term_link( $product_types[0] );
		if ( is_wp_error( $type_link ) ) {
			$type_link = '';
		}
	}

	// Quote URL — use Elementor popup if configured, otherwise link to contact page
	$popup_url = APF_Settings::get_popup_url();
	$quote_url = $popup_url ? $popup_url : add_query_arg( 'product', rawurlencode( get_the_title() ), '/contact/' );
?>

<div class="apf-sp">

<!-- ================================================================
     BREADCRUMB BAR
     ================================================================ -->
<div class="apf-sp-breadcrumb-bar">
	<div class="apf-sp-container">
		<nav class="apf-sp-breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span class="apf-sp-sep">/</span>
			<?php if ( $type_name ) : ?>
				<?php if ( $type_link ) : ?>
				<a href="<?php echo esc_url( $type_link ); ?>"><?php echo esc_html( $type_name ); ?></a>
				<?php else : ?>
				<span><?php echo esc_html( $type_name ); ?></span>
				<?php endif; ?>
				<span class="apf-sp-sep">/</span>
			<?php else : ?>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'product' ) ); ?>">Products</a>
				<span class="apf-sp-sep">/</span>
			<?php endif; ?>
			<span class="apf-sp-crumb-current"><?php echo esc_html( get_the_title() ); ?></span>
		</nav>
	</div>
</div>

<!-- ================================================================
     SECTION 1: HERO
     ================================================================ -->
<section class="apf-sp-hero">
	<div class="apf-sp-container">

		<!-- Hero Grid: Gallery + Info -->
		<div class="apf-sp-hero-grid">

			<!-- ====== Gallery ====== -->
			<div class="apf-sp-gallery" data-count="<?php echo count( $gallery ); ?>">
				<?php if ( ! empty( $gallery ) ) : ?>
				<div class="apf-sp-gallery-main">
					<?php if ( count( $gallery ) > 1 ) : ?>
					<button type="button" class="apf-sp-arrow apf-sp-arrow-prev" aria-label="Previous image">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<?php endif; ?>

					<div class="apf-sp-viewport">
						<?php foreach ( $gallery as $i => $img ) : ?>
						<img
							class="apf-sp-slide<?php echo 0 === $i ? ' active' : ''; ?>"
							src="<?php echo esc_url( $img['full'] ); ?>"
							alt="<?php echo esc_attr( $img['alt'] ); ?>"
							loading="<?php echo 0 === $i ? 'eager' : 'lazy'; ?>"
						/>
						<?php endforeach; ?>
					</div>

					<?php if ( count( $gallery ) > 1 ) : ?>
					<button type="button" class="apf-sp-arrow apf-sp-arrow-next" aria-label="Next image">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
					<?php endif; ?>
				</div>

				<?php if ( count( $gallery ) > 1 ) : ?>
				<div class="apf-sp-thumbs">
					<?php foreach ( $gallery as $i => $img ) : ?>
					<button
						type="button"
						class="apf-sp-thumb<?php echo 0 === $i ? ' active' : ''; ?>"
						data-index="<?php echo esc_attr( $i ); ?>"
						aria-label="View image <?php echo esc_attr( $i + 1 ); ?>"
					>
						<img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" />
					</button>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php else : ?>
				<!-- Placeholder -->
				<div class="apf-sp-gallery-empty">
					<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#c8ced3" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
				</div>
				<?php endif; ?>
			</div>

			<!-- ====== Product Info ====== -->
			<div class="apf-sp-info">
				<?php if ( $subtitle ) : ?>
				<p class="apf-sp-subtitle"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>

				<h1 class="apf-sp-title"><?php the_title(); ?></h1>

				<div class="apf-sp-body">
					<?php the_content(); ?>
				</div>

				<!-- CTA Buttons -->
				<div class="apf-sp-cta">
					<a href="<?php echo esc_url( $quote_url ); ?>" class="apf-sp-btn apf-sp-btn--green">Get Quote</a>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="apf-sp-btn apf-sp-btn--outline">Contact Us</a>
				</div>
			</div>

		</div><!-- .apf-sp-hero-grid -->

	</div>
</section>

<!-- ================================================================
     SECTION: TABS (full-width)
     ================================================================ -->
<?php if ( ! empty( $tabs ) ) : ?>
<section class="apf-sp-tabs-section">
	<div class="apf-sp-container">
		<div class="apf-sp-tabs-nav" role="tablist">
			<?php foreach ( $tabs as $i => $tab ) : ?>
			<button
				type="button"
				class="apf-sp-tabs-btn<?php echo 0 === $i ? ' active' : ''; ?>"
				role="tab"
				aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				aria-controls="apf-tp-<?php echo esc_attr( $i ); ?>"
				data-tab="<?php echo esc_attr( $i ); ?>"
			>
				<?php echo esc_html( $tab['title'] ); ?>
			</button>
			<?php endforeach; ?>
		</div>

		<?php foreach ( $tabs as $i => $tab ) : ?>
		<div
			class="apf-sp-tabs-panel<?php echo 0 === $i ? ' active' : ''; ?>"
			id="apf-tp-<?php echo esc_attr( $i ); ?>"
			role="tabpanel"
			<?php echo 0 !== $i ? 'hidden' : ''; ?>
		>
			<?php echo wp_kses_post( $tab['content'] ); ?>
		</div>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<!-- ================================================================
     SECTION 2: WHY CHOOSE
     ================================================================ -->
<?php
$apf_why_tpl = APF_Settings::get_why_choose_template_id();
if ( $apf_why_tpl && class_exists( '\Elementor\Plugin' ) ) :
	echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( intval( $apf_why_tpl ) );
else :
$why_title    = apply_filters( 'apf_why_choose_title', 'Why Choose Pullner Filter?' );
$why_subtitle = apply_filters( 'apf_why_choose_subtitle', 'More than just a supplier - we\'re your technical partner in filtration solutions' );
$why_features = apply_filters( 'apf_why_choose_features', array(
	array(
		'icon'    => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>',
		'title'   => 'Laboratory and Testing Facilities',
		'desc'    => 'In-house testing capabilities for materials and quality assurance with advanced equipment and certified technicians.',
		'bullets' => array( 'Particle counting analysis', 'Pressure drop testing', 'Material compatibility testing' ),
	),
	array(
		'icon'    => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
		'title'   => 'Design and R&D Services',
		'desc'    => 'Position as technical partner, not just supplier. Custom solutions for your unique challenges.',
		'bullets' => array( 'Custom filter design', 'System optimization', 'Technical consultation' ),
	),
	array(
		'icon'    => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
		'title'   => 'Free Services',
		'desc'    => 'Comprehensive free services to support your filtration needs without any hidden costs.',
		'bullets' => array( 'Free testing for filtration systems', 'Free custom filtration design', 'Up to 2 free samples' ),
	),
	array(
		'icon'    => '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
		'title'   => 'Global Manufacturing',
		'desc'    => 'State-of-the-art manufacturing facilities with worldwide delivery capabilities and local support.',
		'bullets' => array( 'ISO 9001 certified facilities', 'Global logistics network', 'Local technical support' ),
	),
) );
?>
<section class="apf-sp-why apf-sp-reveal">
	<div class="apf-sp-container">
		<h2 class="apf-sp-sec-title"><?php echo esc_html( $why_title ); ?></h2>
		<p class="apf-sp-sec-sub"><?php echo esc_html( $why_subtitle ); ?></p>

		<div class="apf-sp-features">
			<?php foreach ( $why_features as $f ) : ?>
			<div class="apf-sp-fcard">
				<div class="apf-sp-fcard-icon">
					<?php echo $f['icon']; ?>
				</div>
				<h3 class="apf-sp-fcard-title"><?php echo esc_html( $f['title'] ); ?></h3>
				<p class="apf-sp-fcard-desc"><?php echo esc_html( $f['desc'] ); ?></p>
				<?php if ( ! empty( $f['bullets'] ) ) : ?>
				<ul class="apf-sp-fcard-list">
					<?php foreach ( $f['bullets'] as $bullet ) : ?>
					<li><?php echo esc_html( $bullet ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ================================================================
     SECTION 3: REAL RESULTS / CASE STUDIES
     ================================================================ -->
<?php
$apf_cases_tpl = APF_Settings::get_case_studies_template_id();
if ( $apf_cases_tpl && class_exists( '\Elementor\Plugin' ) ) :
	echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( intval( $apf_cases_tpl ) );
else :
$results_title    = apply_filters( 'apf_results_title', 'Real Results with Pullner High Flow Filters: Case Studies' );
$results_subtitle = apply_filters( 'apf_results_subtitle', 'See how our high-capacity cartridges help global clients cut costs, boost flow rates, and improve filtration performance across industries.' );

$case_studies = apply_filters( 'apf_case_studies', array(
	array(
		'image' => '',
		'title' => 'High-Performance Filtration in Action',
		'desc'  => 'See how our filtration system improved efficiency, reduced downtime, and delivered cleaner results for real-world industrial operations.',
		'url'   => '#',
	),
	array(
		'image' => '',
		'title' => 'Proven Filtration Results',
		'desc'  => 'A real case study showing how our filtration solution solved performance challenges and boosted reliability for our client.',
		'url'   => '#',
	),
	array(
		'image' => '',
		'title' => 'Filtration That Delivers',
		'desc'  => 'Discover how our system transformed filtration performance and met demanding operational requirements.',
		'url'   => '#',
	),
	array(
		'image' => '',
		'title' => 'Engineering Cleaner Outcomes',
		'desc'  => 'Explore how our filtration system helped a client achieve higher purity, better flow rates, and long-term operational stability.',
		'url'   => '#',
	),
) );
?>
<section class="apf-sp-results apf-sp-reveal">
	<div class="apf-sp-container">
		<h2 class="apf-sp-sec-title"><?php echo esc_html( $results_title ); ?></h2>
		<p class="apf-sp-sec-sub"><?php echo esc_html( $results_subtitle ); ?></p>

		<div class="apf-sp-cases">
			<?php foreach ( $case_studies as $cs ) : ?>
			<a href="<?php echo esc_url( $cs['url'] ); ?>" class="apf-sp-case">
				<div class="apf-sp-case-img">
					<?php if ( ! empty( $cs['image'] ) ) : ?>
					<img src="<?php echo esc_url( $cs['image'] ); ?>" alt="<?php echo esc_attr( $cs['title'] ); ?>" loading="lazy" />
					<?php else : ?>
					<div class="apf-sp-case-placeholder">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
					</div>
					<?php endif; ?>
				</div>
				<div class="apf-sp-case-body">
					<div class="apf-sp-case-header">
						<h3 class="apf-sp-case-title"><?php echo esc_html( $cs['title'] ); ?></h3>
						<span class="apf-sp-case-arrow">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
						</span>
					</div>
					<div class="apf-sp-case-accent">
						<p class="apf-sp-case-desc"><?php echo esc_html( $cs['desc'] ); ?></p>
					</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

</div><!-- .apf-sp -->

<?php
endwhile;

get_footer();
