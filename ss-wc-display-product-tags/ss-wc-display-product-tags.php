<?php
/**
 * Plugin Name: WooCommerce - Display Products by Tags
 * Plugin URI: http://www.sebs-studio.com
 * Description: Display WooCommerce products by tags using a shortcode. e.g. [product_tag tags="shoes,socks"]
 * Version: 1.0.0
 * Author: Sebastien Dumont
 * Author URI: http://www.sebastiendumont.com
 * Requires at least: 4.2
 * Tested up to: 4.2.2
 */

/*
 * Display WooCommerce Products by tags
 *
 * e.g. [product_tag tags="shoes,socks"]
 */
function ss_wc_product_tag_shortcode( $atts ) {
	global $woocommerce, $woocommerce_loop;

	// Get attribuets
	$atts = shortcode_atts( array(
		'per_page' => '12',
		'columns'  => '4',
		'orderby'  => 'title',
		'order'    => 'desc',
		'tags'     => '',  // Slugs
		'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
	), $atts );

	if ( ! $atts['tags'] ) {
		return '';
	}

	// Default ordering args
	$ordering_args = $woocommerce->query->get_catalog_ordering_args( $atts['orderby'], $atts['order'] );
	$meta_query    = $woocommerce->query->get_meta_query();

	$args = array(
		'post_type'				=> 'product',
		'post_status' 			=> 'publish',
		'ignore_sticky_posts'	=> 1,
		'orderby' 				=> $ordering_args['orderby'],
		'order' 				=> $ordering_args['order'],
		'posts_per_page' 		=> $atts['per_page'],
		'meta_query' 			=> $meta_query,
		'tax_query' 			=> array(
			array(
				'taxonomy' 		=> 'product_tag',
				'terms' 		=> array_map( 'sanitize_title', explode( ',', $atts['tags'] ) ),
				'field' 		=> 'slug',
				'operator' 		=> $atts['operator']
			)
		)
	);

	if ( isset( $ordering_args['meta_key'] ) ) {
		$args['meta_key'] = $ordering_args['meta_key'];
	}

	ob_start();

	$products = new WP_Query( apply_filters( 'ss_wc_shortcode_products_query', $args, $atts ) );

	$woocommerce_loop['columns'] = $atts['columns'];

	if ( $products->have_posts() ) : ?>

		<?php do_action( 'ss_wc_shortcode_before_product_tag_loop' ); ?>

		<?php woocommerce_product_loop_start(); ?>

			<?php while ( $products->have_posts() ) : $products->the_post(); ?>

				<?php wc_get_template_part( 'content', 'product' ); ?>

			<?php endwhile; // end of the loop. ?>

		<?php woocommerce_product_loop_end(); ?>

		<?php do_action( 'ss_wc_shortcode_after_product_tag_loop' ); ?>

	<?php endif;

	woocommerce_reset_loop();
	wp_reset_postdata();

	$return = '<div class="woocommerce columns-' . $atts['columns'] . '">' . ob_get_clean() . '</div>';

	// Remove ordering query arguments
	$woocommerce->query->remove_ordering_args();

	return $return;
}

add_shortcode( 'product_tag', 'ss_wc_product_tag_shortcode' );