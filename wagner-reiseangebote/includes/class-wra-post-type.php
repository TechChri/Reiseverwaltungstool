<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WRA_Post_Type {
	const POST_TYPE = 'wagner_reise';
	const TYPE_TAXONOMY = 'wra_reiseart';
	const DESTINATION_TAXONOMY = 'wra_reiseziel';

	public function register_hooks() {
		add_action( 'init', array( $this, 'register_content_types' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
	}

	public function register_content_types() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name' => 'Reiseangebote',
					'singular_name' => 'Reiseangebot',
					'add_new' => 'Neue Reise',
					'add_new_item' => 'Neue Reise anlegen',
					'edit_item' => 'Reise bearbeiten',
					'new_item' => 'Neue Reise',
					'view_item' => 'Reise ansehen',
					'search_items' => 'Reisen durchsuchen',
					'not_found' => 'Keine Reisen gefunden',
					'all_items' => 'Alle Reisen',
					'menu_name' => 'Reiseangebote',
				),
				'public' => true,
				'show_in_rest' => true,
				'menu_position' => 24,
				'has_archive' => 'reiseangebote',
				'rewrite' => array( 'slug' => 'reise', 'with_front' => false ),
				'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
				'taxonomies' => array( self::TYPE_TAXONOMY, self::DESTINATION_TAXONOMY ),
				'menu_icon' => 'dashicons-location-alt',
			)
		);

		register_taxonomy(
			self::TYPE_TAXONOMY,
			self::POST_TYPE,
			array(
				'labels' => array(
					'name' => 'Reisearten',
					'singular_name' => 'Reiseart',
					'menu_name' => 'Reisearten',
					'add_new_item' => 'Neue Reiseart',
					'edit_item' => 'Reiseart bearbeiten',
				),
				'public' => true,
				'show_in_rest' => true,
				'hierarchical' => true,
				'rewrite' => array( 'slug' => 'reiseart', 'with_front' => false ),
			)
		);

		register_taxonomy(
			self::DESTINATION_TAXONOMY,
			self::POST_TYPE,
			array(
				'labels' => array(
					'name' => 'Reiseziele',
					'singular_name' => 'Reiseziel',
					'menu_name' => 'Reiseziele',
					'add_new_item' => 'Neues Reiseziel',
					'edit_item' => 'Reiseziel bearbeiten',
				),
				'public' => true,
				'show_in_rest' => true,
				'hierarchical' => true,
				'rewrite' => array( 'slug' => 'reiseziel', 'with_front' => false ),
			)
		);

		$this->register_meta();
	}

	private function register_meta() {
		$fields = array(
			'_wra_departure_date' => 'string',
			'_wra_return_date' => 'string',
			'_wra_price' => 'number',
			'_wra_price_note' => 'string',
			'_wra_duration' => 'string',
			'_wra_status' => 'string',
			'_wra_busfinder_url' => 'string',
			'_wra_busfinder_id' => 'string',
			'_wra_import_source' => 'string',
			'_wra_import_reference' => 'string',
			'_wra_imported_at' => 'string',
			'_wra_included' => 'string',
			'_wra_excluded' => 'string',
			'_wra_boarding_points' => 'string',
			'_wra_gallery_ids' => 'string',
			'_wra_itinerary' => 'string',
			'_wra_legacy_tour_id' => 'integer',
		);

		foreach ( $fields as $key => $type ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type' => $type,
					'single' => true,
					'show_in_rest' => true,
					'auth_callback' => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	public function columns( $columns ) {
		$columns['wra_departure'] = 'Abreise';
		$columns['wra_price'] = 'Preis';
		$columns['wra_status'] = 'Status';
		$columns['wra_booking'] = 'Busfinder';
		return $columns;
	}

	public function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'wra_departure':
				echo esc_html( wra_format_date( get_post_meta( $post_id, '_wra_departure_date', true ) ) ?: '–' );
				break;
			case 'wra_price':
				$price = get_post_meta( $post_id, '_wra_price', true );
				echo '' !== (string) $price ? esc_html( number_format_i18n( (float) $price, 2 ) . ' €' ) : '–';
				break;
			case 'wra_status':
				echo esc_html( wra_status_label( get_post_meta( $post_id, '_wra_status', true ) ) );
				break;
			case 'wra_booking':
				$url = get_post_meta( $post_id, '_wra_busfinder_url', true );
				echo $url ? '<span class="dashicons dashicons-yes-alt" aria-label="Busfinder-Link vorhanden"></span>' : '–';
				break;
		}
	}

	public function title_placeholder( $placeholder, $post ) {
		return self::POST_TYPE === $post->post_type ? 'Reisetitel, z. B. Advent in Salzburg' : $placeholder;
	}
}

function wra_format_date( $date ) {
	if ( ! $date ) {
		return '';
	}
	$timestamp = strtotime( $date . ' 12:00:00' );
	return $timestamp ? wp_date( 'd.m.Y', $timestamp, wp_timezone() ) : '';
}

function wra_status_label( $status ) {
	$labels = array(
		'bookable' => 'Buchbar',
		'on_request' => 'Auf Anfrage',
		'sold_out' => 'Ausgebucht',
		'cancelled' => 'Abgesagt',
	);
	return $labels[ $status ] ?? $labels['bookable'];
}

