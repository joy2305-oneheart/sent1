<?php
/**
 * Sharing feed layout markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_share_home   = home_url( '/' );
$one_share_url    = one1_share_page_url();
$one_share_user   = wp_get_current_user();
$one_share_uid    = (int) $one_share_user->ID;
$one_invite_url   = function_exists( 'one1_invite_page_url' ) ? one1_invite_page_url() : home_url( '/invite/' );
$one_nav_active   = 'share';
$one_new_post_url = function_exists( 'one1_story_form_url' ) ? one1_story_form_url() : home_url( '/share-story/' );
$one_can_share = function_exists( 'sin_is_network_approved' ) && sin_is_network_approved( $one_share_uid );
$one_is_pu     = function_exists( 'sin_is_pu' ) && sin_is_pu( $one_share_uid );

$one_profile_url = function_exists( 'one1_profile_page_url' ) ? one1_profile_page_url() : home_url( '/profile/' );

$one_feed = one1_share_feed_query( 1, 10 );
?>
<div class="sent-share-page">
	<?php require get_stylesheet_directory() . '/inc/share/sent-share-header.php'; ?>

	<div class="sent-share-layout sent-share-layout--app">
		<?php require get_stylesheet_directory() . '/inc/share/sent-share-nav.php'; ?>

		<main class="sent-share-main">
			<?php if ( ! $one_can_share ) : ?>
				<section class="sent-share-notice">
					<h1 class="sent-share-notice__title"><?php esc_html_e( 'Almost there', 'one' ); ?></h1>
					<p class="sent-share-notice__text">
						<?php esc_html_e( 'Your account is still being set up. Please contact the site administrator if this persists.', 'one' ); ?>
					</p>
					<?php
					one1_button(
						array(
							'url'     => $one_share_home,
							'label'   => __( 'Back to home', 'one' ),
							'variant' => 'primary',
							'skin'    => 'share',
						)
					);
					?>
				</section>
			<?php else : ?>
				<?php if ( isset( $_GET['story_created'] ) && '1' === $_GET['story_created'] ) : ?>
					<div class="sent-share-notice sent-share-notice--success" role="status">
						<?php esc_html_e( 'Your story was published successfully.', 'one' ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $one_is_pu ) : ?>
				<div class="sent-share-compose-cta">
					<div>
						<h2 class="sent-share-compose-cta__title"><?php esc_html_e( 'Share an update', 'one' ); ?></h2>
						<p class="sent-share-compose-cta__text"><?php esc_html_e( 'Post a moment, photo, or milestone for the people walking with you.', 'one' ); ?></p>
					</div>
					<?php
					one1_button(
						array(
							'label'         => __( 'New post', 'one' ),
							'variant'       => 'primary',
							'skin'          => 'share',
							'icon'          => 'material:edit',
							'icon_position' => 'before',
							'attrs'         => array( 'data-one-open-composer' => 'true' ),
						)
					);
					?>
				</div>
				<?php endif; ?>

				<div class="sent-share-feed">
					<?php if ( ! $one_feed->have_posts() ) : ?>
						<article class="sent-share-card sent-share-card--empty">
							<p class="sent-share-card__empty-title"><?php esc_html_e( 'No posts yet', 'one' ); ?></p>
							<p class="sent-share-card__empty-text">
								<?php
								echo esc_html(
									$one_is_pu
										? __( 'When you or someone in your circle shares an update, it will appear here.', 'one' )
										: __( 'When someone you follow shares an update, it will appear here.', 'one' )
								);
								?>
							</p>
							<?php if ( $one_is_pu ) : ?>
							<?php
							one1_button(
								array(
									'label'   => __( 'Write your first post', 'one' ),
									'variant' => 'outline',
									'skin'    => 'share',
									'attrs'   => array( 'data-one-open-composer' => 'true' ),
								)
							);
							?>
							<?php endif; ?>
						</article>
					<?php else : ?>
						<?php
						while ( $one_feed->have_posts() ) :
							$one_feed->the_post();
							one1_render_share_story_card( get_the_ID(), $one_share_uid );
						endwhile;
						wp_reset_postdata();
						?>
					<?php endif; ?>
				</div>

			<?php endif; ?>
		</main>

		<aside class="sent-share-sidebar sent-share-sidebar--right" aria-label="<?php esc_attr_e( 'Sidebar', 'one' ); ?>">
			<?php if ( $one_is_pu ) : ?>
				<section class="sent-share-widget">
					<div class="sent-share-widget__head">
						<h2 class="sent-share-widget__title"><?php esc_html_e( 'Quick actions', 'one' ); ?></h2>
						<span class="material-symbols-outlined sent-share-widget__icon" aria-hidden="true">bolt</span>
					</div>
					<?php
					one1_button(
						array(
							'label'   => __( 'Post an update', 'one' ),
							'variant' => 'primary',
							'skin'    => 'share',
							'block'   => true,
							'attrs'   => array( 'data-one-open-composer' => 'true' ),
						)
					);
					one1_button(
						array(
							'url'     => $one_invite_url,
							'label'   => __( 'Invite someone', 'one' ),
							'variant' => 'outline',
							'skin'    => 'share',
							'block'   => true,
						)
					);
					?>
				</section>

			<?php endif; ?>
		</aside>
	</div>

	<?php require get_stylesheet_directory() . '/inc/share/sent-share-mobile-nav.php'; ?>

</div>
