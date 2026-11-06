<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) : the_post();
	$post_id = get_the_ID();
	$departure = get_post_meta( $post_id, '_wra_departure_date', true );
	$return = get_post_meta( $post_id, '_wra_return_date', true );
	$duration = get_post_meta( $post_id, '_wra_duration', true );
	$price = get_post_meta( $post_id, '_wra_price', true );
	$status = get_post_meta( $post_id, '_wra_status', true );
	$booking_url = get_post_meta( $post_id, '_wra_busfinder_url', true );
	$hero_style = has_post_thumbnail() ? ' style="background-image:url(' . esc_url( get_the_post_thumbnail_url( $post_id, 'full' ) ) . ')"' : '';
	?>
	<main class="wra-page">
		<header class="wra-single-hero"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="wra-shell">
				<span class="wra-kicker"><?php echo esc_html( wra_status_label( $status ) ); ?></span>
				<h1><?php the_title(); ?></h1>
				<div class="wra-trip-meta"><span><?php echo esc_html( wra_format_date( $departure ) ); ?><?php if ( $return ) : ?> – <?php echo esc_html( wra_format_date( $return ) ); endif; ?></span><?php if ( $duration ) : ?><span><?php echo esc_html( $duration ); ?></span><?php endif; ?></div>
			</div>
		</header>
		<section class="wra-single-main">
			<div class="wra-shell wra-single-layout">
				<article>
					<div class="wra-content"><?php the_content(); ?></div>
					<?php $itinerary = json_decode( (string) get_post_meta( $post_id, '_wra_itinerary', true ), true ); if ( is_array( $itinerary ) && $itinerary ) : ?>
						<section class="wra-info-section"><h2>Reiseprogramm</h2><div class="wra-itinerary"><?php foreach ( $itinerary as $item ) : ?><article class="wra-itinerary__item"><h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3><div><?php echo wpautop( wp_kses_post( $item['content'] ?? '' ) ); ?></div></article><?php endforeach; ?></div></section>
					<?php endif; ?>
					<?php foreach ( array( '_wra_included' => 'Inkludierte Leistungen', '_wra_excluded' => 'Nicht inkludiert', '_wra_boarding_points' => 'Zustiegsstellen' ) as $key => $heading ) : $lines = wra_lines( get_post_meta( $post_id, $key, true ) ); if ( $lines ) : ?>
						<section class="wra-info-section"><h2><?php echo esc_html( $heading ); ?></h2><ul><?php foreach ( $lines as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?></ul></section>
					<?php endif; endforeach; ?>
					<?php $gallery_ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_wra_gallery_ids', true ) ) ) ); if ( $gallery_ids ) : ?><div class="wra-gallery"><?php foreach ( $gallery_ids as $attachment_id ) : echo wp_get_attachment_image( $attachment_id, 'large' ); endforeach; ?></div><?php endif; ?>
				</article>
				<aside><div class="wra-booking-card"><span class="wra-kicker">Reisepreis</span><div class="wra-price"><?php echo '' !== (string) $price ? esc_html( number_format_i18n( (float) $price, 2 ) . ' €' ) : esc_html( wra_status_label( $status ) ); ?></div><?php if ( get_post_meta( $post_id, '_wra_price_note', true ) ) : ?><p><?php echo esc_html( get_post_meta( $post_id, '_wra_price_note', true ) ); ?></p><?php endif; ?><?php if ( $booking_url && 'sold_out' !== $status && 'cancelled' !== $status ) : ?><a class="wra-button" href="<?php echo esc_url( $booking_url ); ?>" rel="noopener">Bei Busfinder buchen</a><?php endif; ?></div></aside>
			</div>
		</section>
	</main>
	<?php
endwhile;
get_footer();

