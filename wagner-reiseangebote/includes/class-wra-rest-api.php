<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authenticated interface for copying trips from Buspilot into WordPress.
 *
 * Authentication deliberately uses WordPress Application Passwords. Buspilot
 * therefore never needs an administrator password and access can be revoked
 * independently at any time.
 */
final class WRA_REST_API {
	const NAMESPACE = 'wagner-reisen/v1';

	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/importe/buspilot',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'import_trip' ),
				'permission_callback' => array( $this, 'can_write' ),
			)
		);
	}

	public function can_write() {
		return current_user_can( 'edit_posts' );
	}

	public function import_trip( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$payload = is_array( $payload ) ? $payload : array();
		$buspilot_id = sanitize_text_field( $payload['buspilot_id'] ?? '' );

		if ( '' === $buspilot_id ) {
			return new WP_Error( 'wra_missing_buspilot_id', 'Die Buspilot-ID fehlt.', array( 'status' => 400 ) );
		}

		$existing = $buspilot_id ? get_posts(
			array(
				'post_type' => WRA_Post_Type::POST_TYPE,
				'post_status' => 'any',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'meta_key' => '_wra_import_reference',
				'meta_value' => $buspilot_id,
			)
		) : array();

		if ( $existing ) {
			return new WP_Error( 'wra_already_imported', 'Diese Buspilot-Reise wurde bereits nach WordPress importiert. Die vorhandene WordPress-Reise wird nicht überschrieben.', array( 'status' => 409, 'wordpress_id' => (int) $existing[0] ) );
		}

		$title = sanitize_text_field( $payload['title'] ?? '' );
		if ( '' === $title ) {
			return new WP_Error( 'wra_missing_title', 'Für eine neue Reise ist ein Titel erforderlich.', array( 'status' => 400 ) );
		}

		$post_data = array(
			'post_type' => WRA_Post_Type::POST_TYPE,
			'post_title' => $title,
			'post_status' => 'draft',
		);
		if ( array_key_exists( 'description', $payload ) ) {
			$post_data['post_content'] = wp_kses_post( $payload['description'] );
		}
		if ( array_key_exists( 'excerpt', $payload ) ) {
			$post_data['post_excerpt'] = sanitize_textarea_field( $payload['excerpt'] );
		}
		$result = wp_insert_post( $post_data, true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$post_id = (int) $result;

		update_post_meta( $post_id, '_wra_import_source', 'buspilot' );
		update_post_meta( $post_id, '_wra_import_reference', $buspilot_id );
		update_post_meta( $post_id, '_wra_imported_at', current_time( 'mysql' ) );
		$this->update_fields( $post_id, $payload );
		$this->update_terms( $post_id, $payload );

		return new WP_REST_Response(
			array(
				'id' => $post_id,
				'buspilot_id' => $buspilot_id,
				'created' => true,
				'status' => get_post_status( $post_id ),
				'edit_url' => get_edit_post_link( $post_id, 'raw' ),
				'url' => get_permalink( $post_id ),
			),
			201
		);
	}

	private function update_fields( $post_id, array $payload ) {
		$text_fields = array(
			'departure_date' => '_wra_departure_date',
			'return_date' => '_wra_return_date',
			'duration' => '_wra_duration',
			'price_note' => '_wra_price_note',
			'busfinder_id' => '_wra_busfinder_id',
		);
		foreach ( $text_fields as $input => $meta_key ) {
			if ( array_key_exists( $input, $payload ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $payload[ $input ] ) );
			}
		}

		if ( array_key_exists( 'price', $payload ) && is_numeric( $payload['price'] ) ) {
			update_post_meta( $post_id, '_wra_price', max( 0, (float) $payload['price'] ) );
		}
		if ( array_key_exists( 'busfinder_url', $payload ) ) {
			update_post_meta( $post_id, '_wra_busfinder_url', esc_url_raw( $payload['busfinder_url'] ) );
		}
		if ( array_key_exists( 'booking_status', $payload ) ) {
			$status = sanitize_key( $payload['booking_status'] );
			if ( in_array( $status, array( 'bookable', 'on_request', 'sold_out', 'cancelled' ), true ) ) {
				update_post_meta( $post_id, '_wra_status', $status );
			}
		}

		foreach ( array( 'included', 'excluded', 'boarding_points' ) as $input ) {
			if ( ! array_key_exists( $input, $payload ) ) {
				continue;
			}
			$value = is_array( $payload[ $input ] ) ? implode( "\n", array_map( 'sanitize_text_field', $payload[ $input ] ) ) : sanitize_textarea_field( $payload[ $input ] );
			update_post_meta( $post_id, '_wra_' . $input, $value );
		}

		if ( isset( $payload['itinerary'] ) && is_array( $payload['itinerary'] ) ) {
			$itinerary = array();
			foreach ( $payload['itinerary'] as $item ) {
				$title = sanitize_text_field( $item['title'] ?? '' );
				$content = wp_kses_post( $item['content'] ?? '' );
				if ( $title || trim( wp_strip_all_tags( $content ) ) ) {
					$itinerary[] = array( 'title' => $title, 'content' => $content );
				}
			}
			update_post_meta( $post_id, '_wra_itinerary', wp_json_encode( $itinerary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}

		if ( isset( $payload['featured_media'] ) ) {
			$attachment_id = absint( $payload['featured_media'] );
			if ( $attachment_id && 'attachment' === get_post_type( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}
	}

	private function update_terms( $post_id, array $payload ) {
		$taxonomies = array(
			'trip_types' => WRA_Post_Type::TYPE_TAXONOMY,
			'destinations' => WRA_Post_Type::DESTINATION_TAXONOMY,
		);
		foreach ( $taxonomies as $input => $taxonomy ) {
			if ( array_key_exists( $input, $payload ) ) {
				$terms = array_values( array_filter( array_map( 'sanitize_text_field', (array) $payload[ $input ] ) ) );
				wp_set_object_terms( $post_id, $terms, $taxonomy, false );
			}
		}
	}
}
