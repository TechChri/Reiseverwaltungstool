<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WRA_Meta_Boxes {
	public function register_hooks() {
		add_action( 'add_meta_boxes_' . WRA_Post_Type::POST_TYPE, array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . WRA_Post_Type::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'wra-trip-data',
			'Reisedaten & Busfinder',
			array( $this, 'render' ),
			WRA_Post_Type::POST_TYPE,
			'normal',
			'high'
		);
	}

	private function value( $post_id, $key ) {
		return get_post_meta( $post_id, $key, true );
	}

	public function render( $post ) {
		wp_nonce_field( 'wra_save_trip', 'wra_nonce' );
		$status = $this->value( $post->ID, '_wra_status' ) ?: 'bookable';
		$gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) $this->value( $post->ID, '_wra_gallery_ids' ) ) ) );
		$itinerary = json_decode( (string) $this->value( $post->ID, '_wra_itinerary' ), true );
		$itinerary = is_array( $itinerary ) && $itinerary ? $itinerary : array( array( 'title' => '', 'content' => '' ) );
		?>
		<div class="wra-editor">
			<p class="wra-editor__intro">Die redaktionellen Inhalte werden oben im WordPress-Editor gepflegt. Hier stehen Termin, Preis und die Verbindung zur Busfinder-Buchung.</p>

			<section class="wra-editor__section">
				<h3>Termin und Preis</h3>
				<div class="wra-editor__grid">
					<label>Abreise<input type="date" name="wra[_wra_departure_date]" value="<?php echo esc_attr( $this->value( $post->ID, '_wra_departure_date' ) ); ?>"></label>
					<label>Rückreise<input type="date" name="wra[_wra_return_date]" value="<?php echo esc_attr( $this->value( $post->ID, '_wra_return_date' ) ); ?>"></label>
					<label>Dauer<input type="text" name="wra[_wra_duration]" value="<?php echo esc_attr( $this->value( $post->ID, '_wra_duration' ) ); ?>" placeholder="z. B. 4 Tage"></label>
					<label>Preis pro Person (€)<input type="number" min="0" step="0.01" name="wra[_wra_price]" value="<?php echo esc_attr( $this->value( $post->ID, '_wra_price' ) ); ?>"></label>
					<label class="wra-editor__wide">Preiszusatz<input type="text" name="wra[_wra_price_note]" value="<?php echo esc_attr( $this->value( $post->ID, '_wra_price_note' ) ); ?>" placeholder="z. B. ab, Einzelzimmerzuschlag extra"></label>
					<label>Status<select name="wra[_wra_status]">
						<?php foreach ( array( 'bookable', 'on_request', 'sold_out', 'cancelled' ) as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $status, $option ); ?>><?php echo esc_html( wra_status_label( $option ) ); ?></option>
						<?php endforeach; ?>
					</select></label>
				</div>
			</section>

			<section class="wra-editor__section">
				<h3>Busfinder-Buchung</h3>
				<div class="wra-editor__grid">
					<label class="wra-editor__wide">Direkter Buchungslink<input type="url" name="wra[_wra_busfinder_url]" value="<?php echo esc_attr( $this->value( $post->ID, '_wra_busfinder_url' ) ); ?>" placeholder="https://..."></label>
					<label>Busfinder-ID<input type="text" name="wra[_wra_busfinder_id]" value="<?php echo esc_attr( $this->value( $post->ID, '_wra_busfinder_id' ) ); ?>"></label>
				</div>
				<p class="description">Der Link führt vom Reiseangebot direkt zur passenden Buchung. Verfügbarkeit, Kundendaten und Zahlung bleiben bei Busfinder.</p>
			</section>

			<section class="wra-editor__section">
				<div class="wra-editor__section-heading">
					<div><h3>Reiseprogramm</h3><p class="description">Ein Abschnitt pro Reisetag oder Programmpunkt.</p></div>
					<button type="button" class="button button-secondary wra-itinerary-add">Programmpunkt hinzufügen</button>
				</div>
				<div class="wra-itinerary-list">
					<?php foreach ( $itinerary as $index => $item ) : ?>
						<div class="wra-itinerary-item">
							<div class="wra-itinerary-item__header"><strong>Programmpunkt <span><?php echo esc_html( (string) ( $index + 1 ) ); ?></span></strong><button type="button" class="button-link-delete wra-itinerary-remove">Entfernen</button></div>
							<label>Titel<input type="text" name="wra[itinerary][<?php echo esc_attr( (string) $index ); ?>][title]" value="<?php echo esc_attr( $item['title'] ?? '' ); ?>" placeholder="z. B. Tag 1 · Anreise nach Südtirol"></label>
							<label>Beschreibung<textarea rows="5" name="wra[itinerary][<?php echo esc_attr( (string) $index ); ?>][content]" placeholder="Programm, Besichtigungen und Ablauf"><?php echo esc_textarea( $item['content'] ?? '' ); ?></textarea></label>
						</div>
					<?php endforeach; ?>
				</div>
				<script type="text/html" id="tmpl-wra-itinerary-item">
					<div class="wra-itinerary-item">
						<div class="wra-itinerary-item__header"><strong>Neuer Programmpunkt</strong><button type="button" class="button-link-delete wra-itinerary-remove">Entfernen</button></div>
						<label>Titel<input type="text" name="wra[itinerary][__INDEX__][title]" value="" placeholder="z. B. Tag 1 · Anreise nach Südtirol"></label>
						<label>Beschreibung<textarea rows="5" name="wra[itinerary][__INDEX__][content]" placeholder="Programm, Besichtigungen und Ablauf"></textarea></label>
					</div>
				</script>
			</section>

			<section class="wra-editor__section">
				<h3>Leistungen und Zustieg</h3>
				<div class="wra-editor__grid">
					<label>Inkludierte Leistungen<textarea rows="7" name="wra[_wra_included]" placeholder="Eine Leistung pro Zeile"><?php echo esc_textarea( $this->value( $post->ID, '_wra_included' ) ); ?></textarea></label>
					<label>Nicht inkludiert<textarea rows="7" name="wra[_wra_excluded]" placeholder="Eine Leistung pro Zeile"><?php echo esc_textarea( $this->value( $post->ID, '_wra_excluded' ) ); ?></textarea></label>
					<label class="wra-editor__wide">Zustiegsstellen<textarea rows="5" name="wra[_wra_boarding_points]" placeholder="Eine Zustiegsstelle pro Zeile"><?php echo esc_textarea( $this->value( $post->ID, '_wra_boarding_points' ) ); ?></textarea></label>
				</div>
			</section>

			<section class="wra-editor__section">
				<h3>Galerie</h3>
				<input type="hidden" class="wra-gallery-ids" name="wra[_wra_gallery_ids]" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">
				<div class="wra-gallery-preview">
					<?php foreach ( $gallery_ids as $attachment_id ) : echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); endforeach; ?>
				</div>
				<p><button type="button" class="button button-secondary wra-gallery-select">Bilder auswählen</button> <button type="button" class="button-link-delete wra-gallery-clear">Galerie leeren</button></p>
			</section>
		</div>
		<?php
	}

	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['wra_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wra_nonce'] ) ), 'wra_save_trip' ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$data = isset( $_POST['wra'] ) && is_array( $_POST['wra'] ) ? wp_unslash( $_POST['wra'] ) : array();
		$text_fields = array( '_wra_departure_date', '_wra_return_date', '_wra_price_note', '_wra_duration', '_wra_busfinder_id' );
		foreach ( $text_fields as $key ) {
			update_post_meta( $post_id, $key, sanitize_text_field( $data[ $key ] ?? '' ) );
		}

		$price = isset( $data['_wra_price'] ) && is_numeric( $data['_wra_price'] ) ? max( 0, (float) $data['_wra_price'] ) : '';
		update_post_meta( $post_id, '_wra_price', $price );
		update_post_meta( $post_id, '_wra_busfinder_url', esc_url_raw( $data['_wra_busfinder_url'] ?? '' ) );

		$status = sanitize_key( $data['_wra_status'] ?? 'bookable' );
		update_post_meta( $post_id, '_wra_status', in_array( $status, array( 'bookable', 'on_request', 'sold_out', 'cancelled' ), true ) ? $status : 'bookable' );

		foreach ( array( '_wra_included', '_wra_excluded', '_wra_boarding_points' ) as $key ) {
			update_post_meta( $post_id, $key, sanitize_textarea_field( $data[ $key ] ?? '' ) );
		}

		$gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) ( $data['_wra_gallery_ids'] ?? '' ) ) ) );
		update_post_meta( $post_id, '_wra_gallery_ids', implode( ',', array_unique( $gallery_ids ) ) );

		$itinerary = array();
		foreach ( (array) ( $data['itinerary'] ?? array() ) as $item ) {
			$title = sanitize_text_field( $item['title'] ?? '' );
			$content = wp_kses_post( $item['content'] ?? '' );
			if ( '' === $title && '' === trim( wp_strip_all_tags( $content ) ) ) {
				continue;
			}
			$itinerary[] = array( 'title' => $title, 'content' => $content );
		}
		update_post_meta( $post_id, '_wra_itinerary', wp_json_encode( $itinerary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}

	public function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || WRA_Post_Type::POST_TYPE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wra-admin', WRA_URL . 'assets/admin.css', array(), WRA_VERSION );
		wp_enqueue_script( 'wra-admin', WRA_URL . 'assets/admin.js', array( 'jquery' ), WRA_VERSION, true );
	}
}

