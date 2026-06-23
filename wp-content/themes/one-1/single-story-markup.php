<?php
/**
 * Single story page markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_story_uid       = get_current_user_id();
$one_can_share       = function_exists( 'sin_is_network_approved' ) && sin_is_network_approved( $one_story_uid );
$one_followers_count = one1_count_followers( $one_story_uid );
$one_following_count = one1_count_following( $one_story_uid );
$one_share_url       = one1_share_page_url();
$one_nav_active      = '';

$post_id  = get_the_ID();
$can_view = one1_can_user_view_story( $post_id, $one_story_uid );
?>
<div class="sent-share-page one-story-single-page">
	<?php require get_stylesheet_directory() . '/inc/share/sent-share-header.php'; ?>

	<div class="sent-share-layout sent-share-layout--app sent-share-layout--no-rail">
		<?php require get_stylesheet_directory() . '/inc/share/sent-share-nav.php'; ?>

		<main class="sent-share-main one-story-single-main">
			<?php if ( ! is_user_logged_in() ) : ?>
				<section class="sent-share-notice">
					<h1 class="sent-share-notice__title"><?php esc_html_e( 'Sign in to view this post', 'one' ); ?></h1>
					<p class="sent-share-notice__text">
						<?php esc_html_e( 'Stories are shared within your circle. Log in to continue reading.', 'one' ); ?>
					</p>
					<?php
					one1_button(
						array(
							'url'     => one1_login_url( get_permalink( $post_id ) ),
							'label'   => __( 'Log in', 'one' ),
							'variant' => 'primary',
							'skin'    => 'share',
						)
					);
					?>
				</section>
			<?php elseif ( ! $can_view ) : ?>
				<section class="sent-share-notice">
					<h1 class="sent-share-notice__title"><?php esc_html_e( 'This post is not available', 'one' ); ?></h1>
					<p class="sent-share-notice__text">
						<?php esc_html_e( 'You can only view stories from people in your circle.', 'one' ); ?>
					</p>
					<?php
					one1_button(
						array(
							'url'     => $one_share_url,
							'label'   => __( 'Back to feed', 'one' ),
							'variant' => 'primary',
							'skin'    => 'share',
						)
					);
					?>
				</section>
			<?php else : ?>
				<nav class="one-story-single-back" aria-label="<?php esc_attr_e( 'Breadcrumb', 'one' ); ?>">
					<a href="<?php echo esc_url( $one_share_url ); ?>" class="one-story-single-back__link">
						<span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
						<?php esc_html_e( 'Back to feed', 'one' ); ?>
					</a>
				</nav>

				<div class="one-story-single-shell">
					<?php one1_render_story_view( $post_id, $one_story_uid, 'single' ); ?>
				</div>
			<?php endif; ?>
		</main>
	</div>

	<?php require get_stylesheet_directory() . '/inc/share/sent-share-mobile-nav.php'; ?>

	<?php if ( $one_can_share ) : ?>
		<?php
		$one_profile_uid = $one_story_uid;
		require get_stylesheet_directory() . '/inc/profile/connections-drawer.php';
		?>
	<?php endif; ?>
</div>
