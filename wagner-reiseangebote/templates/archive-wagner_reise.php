<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$types = get_terms( array( 'taxonomy' => WRA_Post_Type::TYPE_TAXONOMY, 'hide_empty' => false ) );
$destinations = get_terms( array( 'taxonomy' => WRA_Post_Type::DESTINATION_TAXONOMY, 'hide_empty' => false ) );
$selected_month = isset( $_GET['reisemonat'] ) ? sanitize_text_field( wp_unslash( $_GET['reisemonat'] ) ) : '';
$selected_type = isset( $_GET['reiseart'] ) ? sanitize_title( wp_unslash( $_GET['reiseart'] ) ) : '';
$selected_destination = isset( $_GET['reiseziel'] ) ? sanitize_title( wp_unslash( $_GET['reiseziel'] ) ) : '';
?>
<main class="wra-page">
	<header class="wra-archive-hero">
		<div class="wra-shell">
			<span class="wra-kicker">Wagner Reisen · Admont</span>
			<h1>Reisen entdecken</h1>
		</div>
	</header>
	<section class="wra-archive-main">
		<div class="wra-shell">
			<form class="wra-filters" method="get" action="<?php echo esc_url( get_post_type_archive_link( WRA_Post_Type::POST_TYPE ) ); ?>">
				<label>Reisemonat<select name="reisemonat"><option value="">Alle Monate</option>
					<?php $month = new DateTimeImmutable( 'first day of this month', wp_timezone() ); for ( $i = 0; $i < 24; $i++ ) : $value = $month->format( 'Y-m' ); ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_month, $value ); ?>><?php echo esc_html( wp_date( 'F Y', $month->getTimestamp(), wp_timezone() ) ); ?></option>
					<?php $month = $month->modify( '+1 month' ); endfor; ?>
				</select></label>
				<label>Reiseart<select name="reiseart"><option value="">Alle Reisearten</option><?php foreach ( is_wp_error( $types ) ? array() : $types as $type ) : ?><option value="<?php echo esc_attr( $type->slug ); ?>" <?php selected( $selected_type, $type->slug ); ?>><?php echo esc_html( $type->name ); ?></option><?php endforeach; ?></select></label>
				<label>Reiseziel<select name="reiseziel"><option value="">Alle Reiseziele</option><?php foreach ( is_wp_error( $destinations ) ? array() : $destinations as $destination ) : ?><option value="<?php echo esc_attr( $destination->slug ); ?>" <?php selected( $selected_destination, $destination->slug ); ?>><?php echo esc_html( $destination->name ); ?></option><?php endforeach; ?></select></label>
				<button type="submit">Reisen finden</button>
			</form>

			<?php if ( have_posts() ) : ?>
				<div class="wra-grid">
					<?php while ( have_posts() ) : the_post(); $post_id = get_the_ID(); ?>
						<article <?php post_class( 'wra-card' ); ?>>
							<a class="wra-card__image" href="<?php the_permalink(); ?>"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?></a>
							<div class="wra-card__body">
								<div class="wra-card__meta"><span><?php echo esc_html( wra_format_date( get_post_meta( $post_id, '_wra_departure_date', true ) ) ); ?></span><span><?php echo esc_html( get_post_meta( $post_id, '_wra_duration', true ) ); ?></span></div>
								<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<?php the_excerpt(); ?>
								<div class="wra-card__footer"><span class="wra-price"><?php $price = get_post_meta( $post_id, '_wra_price', true ); echo '' !== (string) $price ? esc_html( 'ab ' . number_format_i18n( (float) $price, 2 ) . ' €' ) : esc_html( wra_status_label( get_post_meta( $post_id, '_wra_status', true ) ) ); ?></span><a class="wra-button" href="<?php the_permalink(); ?>">Details</a></div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php the_posts_pagination(); ?>
			<?php else : ?>
				<p>Für diese Auswahl wurden keine Reisen gefunden.</p>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>

