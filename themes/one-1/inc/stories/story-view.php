<?php
/**
 * Shared story detail view (single page + profile modal).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render story post content (AJAX-safe in modal context).
 *
 * @param WP_Post $post    Post object.
 * @param string  $context single|modal|public.
 */
function one1_render_story_content( $post, $context = 'single' ) {
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$content = $post->post_content;
	if ( function_exists( 'do_blocks' ) ) {
		$content = do_blocks( $content );
	}
	$content = shortcode_unautop( wpautop( wp_kses_post( $content ) ) );

	if ( 'modal' === $context ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses above.
		echo $content;
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filters.
	echo apply_filters( 'the_content', $post->post_content );
}

/**
 * Return rendered story view HTML.
 *
 * @param int    $post_id   Post ID.
 * @param int    $viewer_id Viewer user ID.
 * @param string $context   single|modal|public.
 * @return string
 */
function one1_get_story_view_html( $post_id, $viewer_id, $context = 'single' ) {
	if ( ! function_exists( 'one1_render_story_view' ) ) {
		return '';
	}

	ob_start();
	one1_render_story_view( $post_id, $viewer_id, $context );
	return (string) ob_get_clean();
}

/**
 * Unix timestamp for a story post.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function one1_get_post_timestamp( $post_id ) {
	$post = get_post( (int) $post_id );
	if ( ! $post ) {
		return 0;
	}

	$timestamp = (int) get_post_time( 'U', true, $post );
	if ( $timestamp > 0 ) {
		return $timestamp;
	}

	$timestamp = (int) strtotime( $post->post_date_gmt . ' GMT' );
	if ( $timestamp > 0 ) {
		return $timestamp;
	}

	return (int) strtotime( $post->post_date );
}

/**
 * Render donation block for a story.
 *
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $meta    Story meta.
 * @param string               $context single|modal|public.
 */
function one1_render_story_donation_block( $post_id, $meta, $context = 'single' ) {
	if ( empty( $meta['is_donation'] ) ) {
		return;
	}
	$summary_context = 'public' === $context ? 'public' : ( 'modal' === $context ? 'modal' : 'single' );
	?>
	<div class="one-story-view__donation sent-share-donation">
		<?php one1_render_story_donation_summary( $post_id, $meta, $summary_context ); ?>
	</div>
	<?php
}

/**
 * Render full story view body.
 *
 * @param int    $post_id   Post ID.
 * @param int    $viewer_id Viewer user ID.
 * @param string $context   single|modal|public.
 */
function one1_render_story_view( $post_id, $viewer_id, $context = 'single' ) {
	$post_id   = (int) $post_id;
	$viewer_id = (int) $viewer_id;
	$post      = get_post( $post_id );

	if ( ! $post || ONE_STORY_POST_TYPE !== $post->post_type ) {
		return;
	}

	$is_public = 'public' === $context;

	if ( ! $is_public && function_exists( 'one1_record_story_view' ) ) {
		one1_record_story_view( $post_id, $viewer_id );
	}

	$author_id   = (int) $post->post_author;
	$author      = get_userdata( $author_id );
	$meta        = One_Story_Meta::get_all( $post_id );
	$thumb       = get_the_post_thumbnail_url( $post_id, 'large' );
	$is_donation = ! empty( $meta['is_donation'] );
	$is_own      = ! $is_public && $author_id === $viewer_id;
	$show_insights = ! $is_public && $is_own && function_exists( 'one1_render_story_insights_panel' );

	$wrap_class = 'one-story-view one-story-view__card';
	if ( 'modal' === $context ) {
		$wrap_class .= ' one-story-view--modal';
	}
	if ( $is_public ) {
		$wrap_class .= ' one-story-view--public';
	}

	if ( $show_insights ) :
		?>
		<div class="one-story-view-shell">
		<?php
	endif;
	?>
	<article class="<?php echo esc_attr( $wrap_class ); ?>" data-story-id="<?php echo esc_attr( (string) $post_id ); ?>">
		<header class="one-story-view__header">
			<?php if ( $is_own ) : ?>
				<?php one1_render_story_owner_actions( $post_id ); ?>
			<?php endif; ?>
			<div class="one-story-view__author">
				<?php echo get_avatar( $author_id, 48, '', '', array( 'class' => 'one-story-view__avatar' ) ); ?>
				<div>
					<p class="one-story-view__name"><?php echo esc_html( $author ? $author->display_name : '' ); ?></p>
					<p class="one-story-view__meta sent-share-card__meta">
						<?php
						$post_ts = one1_get_post_timestamp( $post_id );
						if ( $post_ts > 0 ) {
							printf(
								/* translators: 1: human time diff, 2: post type label */
								esc_html__( '%1$s ago · %2$s', 'one' ),
								esc_html( human_time_diff( $post_ts, (int) current_time( 'timestamp', true ) ) ),
								$is_donation ? esc_html__( 'Donation', 'one' ) : ( $is_own ? esc_html__( 'Your journey', 'one' ) : esc_html__( 'Journey', 'one' ) )
							);
						} else {
							echo esc_html( $is_donation ? __( 'Donation', 'one' ) : ( $is_own ? __( 'Your journey', 'one' ) : __( 'Journey', 'one' ) ) );
						}
						if ( ! $is_donation ) {
							one1_render_story_location_inline( $meta );
						}
						?>
					</p>
				</div>
			</div>
		</header>

		<?php one1_render_story_donation_block( $post_id, $meta, $context ); ?>

		<?php if ( one1_story_has_headline( $post_id ) ) : ?>
			<h1 class="one-story-view__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
		<?php endif; ?>

		<?php if ( $thumb ) : ?>
			<figure class="one-story-view__media">
				<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" />
			</figure>
		<?php endif; ?>

		<div class="one-story-view__content entry-content">
			<?php one1_render_story_content( $post, $context ); ?>
		</div>

		<?php if ( ! $is_public ) : ?>
		<div class="one-story-view__engage">
			<?php
			if ( function_exists( 'one1_render_story_view_count' ) ) {
				one1_render_story_view_count( $post_id );
			}
			one1_render_story_support_control( $post_id, $viewer_id );
			?>
		</div>

		<?php one1_render_story_comments( $post_id, $viewer_id ); ?>
		<?php endif; ?>
	</article>
	<?php
	if ( $show_insights ) :
		?>
		<aside class="one-story-view__sidebar" aria-label="<?php esc_attr_e( 'Post insights', 'one' ); ?>">
			<?php one1_render_story_insights_panel( $post_id, $viewer_id ); ?>
		</aside>
		</div>
		<?php
	endif;
}
