<?php
/**
 * Profile page markup — same shell as Share (header + sidebar).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_profile_user    = wp_get_current_user();
$one_profile_uid     = (int) $one_profile_user->ID;
$one_posts           = one1_user_stories_query( $one_profile_uid, 1, 24 );
$one_can_share       = function_exists( 'sin_is_network_approved' ) && sin_is_network_approved( $one_profile_uid );
$one_is_pu           = function_exists( 'sin_is_pu' ) && sin_is_pu( $one_profile_uid );
$one_is_friend       = function_exists( 'sin_is_friend' ) && sin_is_friend( $one_profile_uid );
$one_nav_active      = 'profile';
$one_share_url       = one1_share_page_url();
$one_followers_n     = one1_count_followers( $one_profile_uid );
$one_following_n     = one1_count_following( $one_profile_uid );
$one_followers_count = $one_followers_n;
$one_following_count = $one_following_n;
$one_profile_bio     = one1_get_profile_bio( $one_profile_uid );
$one_profile_bio_display = $one_profile_bio !== '' ? $one_profile_bio : (
	$one_is_friend
		? __( 'Following journeys that matter to you.', 'one' )
		: __( 'Your journey, shared with your circle.', 'one' )
);
?>
<div class="sent-share-page one-profile-page">
	<?php require get_stylesheet_directory() . '/inc/share/sent-share-header.php'; ?>

	<div class="sent-share-layout sent-share-layout--app sent-share-layout--no-rail">
		<?php require get_stylesheet_directory() . '/inc/share/sent-share-nav.php'; ?>

		<main class="sent-share-main one-profile-main">
			<?php if ( ! $one_can_share ) : ?>
				<section class="sent-share-notice">
					<h1 class="sent-share-notice__title"><?php esc_html_e( 'Almost there', 'one' ); ?></h1>
					<p class="sent-share-notice__text">
						<?php esc_html_e( 'Your account is still being set up. Please contact the site administrator if this persists.', 'one' ); ?>
					</p>
				</section>
			<?php else : ?>
				<section class="one-profile-card" data-one-profile-card>
					<?php if ( $one_is_pu ) : ?>
					<div class="one-profile-card__menu">
						<div class="one-profile-menu" data-one-profile-menu>
							<button
								type="button"
								class="one-profile-menu__trigger"
								data-one-profile-menu-toggle
								aria-expanded="false"
								aria-haspopup="true"
								aria-label="<?php esc_attr_e( 'Profile actions', 'one' ); ?>"
							>
								<span class="material-symbols-outlined" aria-hidden="true">more_vert</span>
							</button>
							<div class="one-profile-menu__panel" data-one-profile-menu-panel hidden role="menu">
								<button type="button" class="one-profile-menu__item" role="menuitem" data-one-open-composer data-one-profile-menu-default>
									<span class="material-symbols-outlined" aria-hidden="true">edit_square</span>
									<?php esc_html_e( 'New post', 'one' ); ?>
								</button>
								<button type="button" class="one-profile-menu__item" role="menuitem" data-one-profile-edit-toggle data-one-profile-menu-default>
									<span class="material-symbols-outlined" aria-hidden="true">manage_accounts</span>
									<?php esc_html_e( 'Edit profile', 'one' ); ?>
								</button>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<div class="one-profile-card__identity">
						<div class="one-profile-card__avatar-wrap">
							<?php echo get_avatar( $one_profile_uid, 96, '', '', array( 'class' => 'one-profile-card__avatar', 'extra_attr' => 'data-one-profile-avatar="1"' ) ); ?>
							<label class="one-profile-card__avatar-edit" data-one-profile-edit-only hidden>
								<input type="file" accept="image/jpeg,image/png,image/webp" data-one-profile-avatar-input hidden />
								<span class="material-symbols-outlined" aria-hidden="true">photo_camera</span>
								<span class="screen-reader-text"><?php esc_html_e( 'Change profile photo', 'one' ); ?></span>
							</label>
						</div>
						<div class="one-profile-card__meta">
							<h1 class="one-profile-card__name"><?php echo esc_html( $one_profile_user->display_name ); ?></h1>
							<p class="one-profile-card__bio" data-one-profile-bio-display><?php echo esc_html( $one_profile_bio_display ); ?></p>
							<div class="one-profile-card__edit-form" data-one-profile-edit-form hidden>
								<label class="one-profile-card__edit-label" for="one-profile-bio-input"><?php esc_html_e( 'Status', 'one' ); ?></label>
								<textarea
									id="one-profile-bio-input"
									class="one-profile-card__bio-input"
									data-one-profile-bio-input
									maxlength="<?php echo esc_attr( (string) ONE1_PROFILE_BIO_MAX_LENGTH ); ?>"
									rows="3"
								><?php echo esc_textarea( $one_profile_bio ); ?></textarea>
								<p class="one-profile-card__bio-count">
									<span data-one-profile-bio-count><?php echo esc_html( (string) mb_strlen( $one_profile_bio ) ); ?></span>/<?php echo esc_html( (string) ONE1_PROFILE_BIO_MAX_LENGTH ); ?>
								</p>
								<button type="button" class="one-profile-card__save" data-one-profile-edit-save>
									<span class="material-symbols-outlined" aria-hidden="true">save</span>
									<?php esc_html_e( 'Save profile', 'one' ); ?>
								</button>
							</div>
							<div class="one-profile-card__stats">
								<?php if ( $one_is_pu ) : ?>
								<button type="button" class="one-profile-stat" data-one-open-connections-drawer data-tab="followers">
									<strong><?php echo esc_html( (string) $one_followers_n ); ?></strong>
									<span><?php esc_html_e( 'Followers', 'one' ); ?></span>
								</button>
								<?php endif; ?>
								<button type="button" class="one-profile-stat" data-one-open-connections-drawer data-tab="following">
									<strong><?php echo esc_html( (string) $one_following_n ); ?></strong>
									<span><?php esc_html_e( 'Following', 'one' ); ?></span>
								</button>
								<?php if ( $one_is_pu ) : ?>
								<div class="one-profile-stat one-profile-stat--static" aria-label="<?php esc_attr_e( 'Total posts', 'one' ); ?>">
									<strong><?php echo esc_html( (string) $one_posts->found_posts ); ?></strong>
									<span><?php esc_html_e( 'Posts', 'one' ); ?></span>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</section>

				<?php if ( $one_is_pu ) : ?>
				<section class="one-profile-posts" aria-labelledby="one-profile-posts-title">
					<div class="one-profile-posts__head">
						<h2 id="one-profile-posts-title" class="one-profile-posts__title"><?php esc_html_e( 'Your posts', 'one' ); ?></h2>
						<p class="one-profile-posts__hint"><?php esc_html_e( 'Tap a post to open it', 'one' ); ?></p>
					</div>

					<?php if ( ! $one_posts->have_posts() ) : ?>
						<div class="one-profile-posts__empty">
							<div class="one-profile-posts__empty-icon" aria-hidden="true">
								<span class="material-symbols-outlined">grid_view</span>
							</div>
							<p><?php esc_html_e( 'No posts yet. Share your first moment with your circle.', 'one' ); ?></p>
							<button type="button" class="one-profile-card__btn one-profile-card__btn--primary" data-one-open-composer>
								<?php esc_html_e( 'Create your first post', 'one' ); ?>
							</button>
						</div>
					<?php else : ?>
						<div class="one-profile-posts__grid" role="list">
							<?php
							while ( $one_posts->have_posts() ) :
								$one_posts->the_post();
								one1_render_profile_post_cell( get_the_ID() );
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					<?php endif; ?>
				</section>
				<?php endif; ?>
			<?php endif; ?>
		</main>
	</div>

	<?php if ( $one_can_share ) : ?>
		<?php
		$one_profile_uid = $one_profile_uid;
		require get_stylesheet_directory() . '/inc/profile/connections-drawer.php';
		require get_stylesheet_directory() . '/inc/profile/post-detail-modal.php';
		?>
	<?php endif; ?>
</div>
<?php require get_stylesheet_directory() . '/inc/share/sent-share-mobile-nav.php'; ?>
