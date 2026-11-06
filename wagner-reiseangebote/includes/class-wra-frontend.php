<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WRA_Frontend {
	public function register_hooks() {
		add_filter( 'template_include', array( $this, 'template' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'pre_get_posts', array( $this, 'filters' ) );
		add_action( 'wp_head', array( $this, 'structured_data' ) );
	}

	public function template( $template ) {
		if ( is_singular( WRA_Post_Type::POST_TYPE ) ) {
			return WRA_DIR . 'templates/single-wagner_reise.php';
		}
		if ( is_post_type_archive( WRA_Post_Type::POST_TYPE ) || is_tax( array( WRA_Post_Type::TYPE_TAXONOMY, WRA_Post_Type::DESTINATION_TAXONOMY ) ) ) {
			return WRA_DIR . 'templates/archive-wagner_reise.php';
		}
		return $template;
	}

	public function assets() {
		if ( is_singular( WRA_Post_Type::POST_TYPE ) || is_post_type_archive( WRA_Post_Type::POST_TYPE ) || is_tax( array( WRA_Post_Type::TYPE_TAXONOMY, WRA_Post_Type::DESTINATION_TAXONOMY ) ) ) {
			wp_enqueue_style( 'wra-front', WRA_URL . 'assets/front.css', array(), WRA_VERSION );
		}
	}

	public function filters( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( WRA_Post_Type::POST_TYPE ) ) {
			return;
		}
		$query->set( 'posts_per_page', 12 );
		$query->set( 'meta_key', '_wra_departure_date' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );

		$meta_query = array();
		$month = isset( $_GET['reisemonat'] ) ? sanitize_text_field( wp_unslash( $_GET['reisemonat'] ) ) : '';
		if ( preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ) ) {
			$start = $month . '-01';
			$end = gmdate( 'Y-m-d', strtotime( $start . ' +1 month' ) );
			$meta_query[] = array(
				'relation' => 'AND',
				array(
					'key' => '_wra_departure_date',
					'value' => $start,
					'compare' => '>=',
					'type' => 'DATE',
				),
				array(
					'key' => '_wra_departure_date',
					'value' => $end,
					'compare' => '<',
					'type' => 'DATE',
				),
			);
		}
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}

		$tax_query = array();
		$type = isset( $_GET['reiseart'] ) ? sanitize_title( wp_unslash( $_GET['reiseart'] ) ) : '';
		if ( $type ) {
			$tax_query[] = array( 'taxonomy' => WRA_Post_Type::TYPE_TAXONOMY, 'field' => 'slug', 'terms' => $type );
		}
		$destination = isset( $_GET['reiseziel'] ) ? sanitize_title( wp_unslash( $_GET['reiseziel'] ) ) : '';
		if ( $destination ) {
			$tax_query[] = array( 'taxonomy' => WRA_Post_Type::DESTINATION_TAXONOMY, 'field' => 'slug', 'terms' => $destination );
		}
		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
	}

	public function structured_data() {
		if ( ! is_singular( WRA_Post_Type::POST_TYPE ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		$schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'TouristTrip',
			'name' => get_the_title( $post_id ),
			'description' => wp_strip_all_tags( get_the_excerpt( $post_id ) ?: get_post_field( 'post_content', $post_id ) ),
			'url' => get_permalink( $post_id ),
			'provider' => array( '@type' => 'Organization', 'name' => 'Wagner Reisen', 'url' => home_url( '/' ) ),
		);
		$image = get_the_post_thumbnail_url( $post_id, 'large' );
		if ( $image ) {
			$schema['image'] = array( $image );
		}
		$price = get_post_meta( $post_id, '_wra_price', true );
		if ( '' !== (string) $price ) {
			$schema['offers'] = array(
				'@type' => 'Offer',
				'price' => (float) $price,
				'priceCurrency' => 'EUR',
				'url' => get_post_meta( $post_id, '_wra_busfinder_url', true ) ?: get_permalink( $post_id ),
				'availability' => 'sold_out' === get_post_meta( $post_id, '_wra_status', true ) ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
			);
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}

function wra_lines( $value ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $value ) ) ) );
}

