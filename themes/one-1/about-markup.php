<?php
/**
 * About page markup — member details, banner, journey, posts, circle, contact.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_about_user     = wp_get_current_user();
$one_about_uid      = (int) $one_about_user->ID;
$one_can_share      = function_exists( 'sin_is_network_approved' ) && sin_is_network_approved( $one_about_uid );
$one_is_pu          = function_exists( 'sin_is_pu' ) && sin_is_pu( $one_about_uid );
$one_is_friend      = function_exists( 'sin_is_friend' ) && sin_is_friend( $one_about_uid );
$one_nav_active     = 'about';
$one_share_url      = one1_share_page_url();
$one_profile_url    = one1_profile_page_url();
$one_invite_url     = function_exists( 'one1_invite_page_url' ) ? one1_invite_page_url() : home_url( '/invite/' );
$one_banner_url     = one1_get_banner_url( $one_about_uid, 'large' );
$one_has_banner     = one1_has_custom_banner( $one_about_uid );
$one_journey        = one1_get_about_journey( $one_about_uid );
$one_journey_display = one1_get_about_journey_display( $one_about_uid );
$one_posts          = one1_user_stories_query( $one_about_uid, 1, 6 );
$one_followers_n    = one1_count_followers( $one_about_uid );
$one_following_n    = one1_count_following( $one_about_uid );
$one_circle         = $one_is_pu && function_exists( 'one1_get_circle_friends' ) ? one1_get_circle_friends( $one_about_uid ) : array();
$one_following_ids  = one1_get_following_ids( $one_about_uid );
$one_profile_uid    = $one_about_uid;
?>
<div class="sent-share-page one-about-page">
	<?php require get_stylesheet_directory() . '/inc/share/sent-share-header.php'; ?>

	<div class="sent-share-layout sent-share-layout--app sent-share-layout--no-rail">
		<?php require get_stylesheet_directory() . '/inc/share/sent-share-nav.php'; ?>

		<main class="sent-share-main one-about-main">
			<?php if ( ! $one_can_share ) : ?>
				<section class="sent-share-notice">
					<h1 class="sent-share-notice__title"><?php esc_html_e( 'Almost there', 'one' ); ?></h1>
					<p class="sent-share-notice__text">
						<?php esc_html_e( 'Your account is still being set up. Please contact the site administrator if this persists.', 'one' ); ?>
					</p>
				</section>
			<?php else : ?>
				<section class="one-about-hero" aria-label="<?php esc_attr_e( 'Profile banner', 'one' ); ?>">
					<div class="one-about-hero__frame" data-one-about-hero>
						<div class="one-about-hero__media">
							<img
								src="<?php echo esc_url( $one_banner_url ); ?>"
								alt=""
								class="one-about-hero__img"
								data-one-about-banner-img
								loading="eager"
							/>
							<div class="one-about-hero__overlay" aria-hidden="true"></div>
						</div>

						<div class="one-about-hero__identity">
							<div class="one-about-hero__avatar-wrap">
								<?php echo get_avatar( $one_about_uid, 96, '', '', array( 'class' => 'one-about-hero__avatar' ) ); ?>
							</div>
							<div class="one-about-hero__meta">
								<h1 class="one-about-hero__name"><?php echo esc_html( $one_about_user->display_name ); ?></h1>
								<p class="one-about-hero__tagline">
									<?php
									echo $one_is_pu
										? esc_html__( 'Sharing my journey on Sent One', 'one' )
										: esc_html__( 'Walking with my circle on Sent One', 'one' );
									?>
								</p>
							</div>
						</div>

						<div class="one-about-hero__controls">
							<label class="one-about-hero__btn one-about-hero__btn--upload">
								<input type="file" accept="image/jpeg,image/png,image/webp" class="one-about-hero__file-input" data-one-about-banner-input />
								<span class="material-symbols-outlined" aria-hidden="true">add_photo_alternate</span>
								<span data-one-about-upload-label><?php echo $one_has_banner ? esc_html__( 'Change banner', 'one' ) : esc_html__( 'Upload banner', 'one' ); ?></span>
							</label>
							<button
								type="button"
								class="one-about-hero__btn one-about-hero__btn--remove"
								data-one-about-banner-remove
								<?php echo $one_has_banner ? '' : 'hidden'; ?>
							>
								<span class="material-symbols-outlined" aria-hidden="true">delete</span>
								<?php esc_html_e( 'Remove', 'one' ); ?>
							</button>
						</div>
					</div>
				</section>

				<div class="one-about-grid">
					<section class="one-about-card one-about-card--journey" data-one-about-journey-card>
						<header class="one-about-card__head">
							<div class="one-about-card__icon" aria-hidden="true">
								<span class="material-symbols-outlined">auto_stories</span>
							</div>
							<div>
								<h2 class="one-about-card__title"><?php esc_html_e( 'My journey in Sent One', 'one' ); ?></h2>
								<p class="one-about-card__subtitle"><?php esc_html_e( 'What you are walking through and why you share here', 'one' ); ?></p>
							</div>
							<button type="button" class="one-about-card__edit-btn" data-one-about-journey-edit aria-label="<?php esc_attr_e( 'Edit journey', 'one' ); ?>">
								<span class="material-symbols-outlined" aria-hidden="true">edit</span>
							</button>
						</header>
						<div class="one-about-card__body">
							<p class="one-about-journey__text<?php echo $one_journey === '' ? ' is-placeholder' : ''; ?>" data-one-about-journey-display><?php echo esc_html( $one_journey_display ); ?></p>
							<div class="one-about-journey__form" data-one-about-journey-form hidden>
								<textarea
									class="one-about-journey__input"
									data-one-about-journey-input
									maxlength="<?php echo esc_attr( (string) ONE1_ABOUT_JOURNEY_MAX_LENGTH ); ?>"
									rows="5"
									aria-label="<?php esc_attr_e( 'My journey in Sent One', 'one' ); ?>"
								><?php echo esc_textarea( $one_journey ); ?></textarea>
								<p class="one-about-journey__count">
									<span data-one-about-journey-count><?php echo esc_html( (string) mb_strlen( $one_journey ) ); ?></span>/<?php echo esc_html( (string) ONE1_ABOUT_JOURNEY_MAX_LENGTH ); ?>
								</p>
								<div class="one-about-journey__actions">
									<button type="button" class="one-about-btn one-about-btn--ghost" data-one-about-journey-cancel><?php esc_html_e( 'Cancel', 'one' ); ?></button>
									<button type="button" class="one-about-btn one-about-btn--primary" data-one-about-journey-save>
										<span class="material-symbols-outlined" aria-hidden="true">save</span>
										<?php esc_html_e( 'Save', 'one' ); ?>
									</button>
								</div>
							</div>
						</div>
					</section>

					<section class="one-about-card one-about-card--posts">
						<header class="one-about-card__head">
							<div class="one-about-card__icon" aria-hidden="true">
								<span class="material-symbols-outlined">grid_view</span>
							</div>
							<div>
								<h2 class="one-about-card__title"><?php esc_html_e( 'My posts', 'one' ); ?></h2>
								<p class="one-about-card__subtitle">
									<?php
									printf(
										/* translators: %d: post count */
										esc_html( _n( '%d story shared', '%d stories shared', (int) $one_posts->found_posts, 'one' ) ),
										(int) $one_posts->found_posts
									);
									?>
								</p>
							</div>
							<?php if ( $one_is_pu && $one_posts->found_posts > 0 ) : ?>
								<a href="<?php echo esc_url( $one_profile_url ); ?>" class="one-about-card__link">
									<?php esc_html_e( 'View all', 'one' ); ?>
									<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
								</a>
							<?php endif; ?>
						</header>
						<div class="one-about-card__body">
							<?php if ( ! $one_posts->have_posts() ) : ?>
								<div class="one-about-posts__empty">
									<p><?php esc_html_e( 'No posts yet. When you share moments with your circle, they will appear here.', 'one' ); ?></p>
									<?php if ( $one_is_pu ) : ?>
										<button type="button" class="one-about-btn one-about-btn--primary" data-one-open-composer>
											<span class="material-symbols-outlined" aria-hidden="true">edit_square</span>
											<?php esc_html_e( 'Create a post', 'one' ); ?>
										</button>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<div class="one-about-posts__grid" role="list">
									<?php
									while ( $one_posts->have_posts() ) :
										$one_posts->the_post();
										one1_render_profile_post_cell( get_the_ID() );
									endwhile;
									wp_reset_postdata();
									?>
								</div>
							<?php endif; ?>
						</div>
					</section>

					<section class="one-about-card one-about-card--circle">
						<header class="one-about-card__head">
							<div class="one-about-card__icon" aria-hidden="true">
								<span class="material-symbols-outlined">groups</span>
							</div>
							<div>
								<h2 class="one-about-card__title"><?php esc_html_e( 'My circle', 'one' ); ?></h2>
								<p class="one-about-card__subtitle"><?php esc_html_e( 'The people connected with you on Sent One', 'one' ); ?></p>
							</div>
						</header>
						<div class="one-about-card__body">
							<div class="one-about-circle__stats">
								<?php if ( $one_is_pu ) : ?>
								<button type="button" class="one-about-circle__stat" data-one-open-connections-drawer data-tab="followers">
									<strong><?php echo esc_html( (string) $one_followers_n ); ?></strong>
									<span><?php esc_html_e( 'Followers', 'one' ); ?></span>
								</button>
								<?php endif; ?>
								<button type="button" class="one-about-circle__stat" data-one-open-connections-drawer data-tab="following">
									<strong><?php echo esc_html( (string) $one_following_n ); ?></strong>
									<span><?php esc_html_e( 'Following', 'one' ); ?></span>
								</button>
							</div>

							<?php if ( $one_is_pu && ! empty( $one_circle ) ) : ?>
								<ul class="one-about-circle__list">
									<?php foreach ( array_slice( $one_circle, 0, 8 ) as $one_friend ) : ?>
										<?php
										$fid = isset( $one_friend['id'] ) ? (int) $one_friend['id'] : 0;
										if ( $fid <= 0 ) {
											continue;
										}
										$fname = isset( $one_friend['display_name'] ) ? $one_friend['display_name'] : '';
										$fnick = isset( $one_friend['nickname'] ) && $one_friend['nickname'] !== '' ? $one_friend['nickname'] : '';
										?>
										<li class="one-about-circle__person">
											<?php echo get_avatar( $fid, 44, '', '', array( 'class' => 'one-about-circle__avatar' ) ); ?>
											<div class="one-about-circle__person-meta">
												<span class="one-about-circle__person-name"><?php echo esc_html( $fname ); ?></span>
												<?php if ( $fnick !== '' ) : ?>
													<span class="one-about-circle__person-nick"><?php echo esc_html( $fnick ); ?></span>
												<?php endif; ?>
											</div>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php elseif ( ! $one_is_pu && ! empty( $one_following_ids ) ) : ?>
								<ul class="one-about-circle__list">
									<?php foreach ( array_slice( $one_following_ids, 0, 8 ) as $one_fid ) : ?>
										<?php
										$one_fuser = get_userdata( (int) $one_fid );
										if ( ! $one_fuser ) {
											continue;
										}
										?>
										<li class="one-about-circle__person">
											<?php echo get_avatar( (int) $one_fid, 44, '', '', array( 'class' => 'one-about-circle__avatar' ) ); ?>
											<div class="one-about-circle__person-meta">
												<span class="one-about-circle__person-name"><?php echo esc_html( $one_fuser->display_name ); ?></span>
											</div>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="one-about-circle__empty">
									<?php
									echo $one_is_pu
										? esc_html__( 'Invite people to your circle to start building your community.', 'one' )
										: esc_html__( 'People who invited you and walk with you will appear here.', 'one' );
									?>
								</p>
							<?php endif; ?>

							<div class="one-about-circle__actions">
								<button type="button" class="one-about-btn one-about-btn--ghost" data-one-open-connections-drawer>
									<span class="material-symbols-outlined" aria-hidden="true">hub</span>
									<?php esc_html_e( 'View connections', 'one' ); ?>
								</button>
								<?php if ( $one_is_pu ) : ?>
									<a href="<?php echo esc_url( $one_invite_url ); ?>" class="one-about-btn one-about-btn--primary">
										<span class="material-symbols-outlined" aria-hidden="true">mail</span>
										<?php esc_html_e( 'Invite to circle', 'one' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</section>

					<section class="one-about-card one-about-card--contact">
						<header class="one-about-card__head">
							<div class="one-about-card__icon" aria-hidden="true">
								<span class="material-symbols-outlined">mail</span>
							</div>
							<div>
								<h2 class="one-about-card__title"><?php esc_html_e( 'Contact', 'one' ); ?></h2>
								<p class="one-about-card__subtitle"><?php esc_html_e( 'How your circle can reach you', 'one' ); ?></p>
							</div>
						</header>
						<div class="one-about-card__body">
							<a class="one-about-contact__email" href="mailto:<?php echo esc_attr( $one_about_user->user_email ); ?>">
								<span class="material-symbols-outlined" aria-hidden="true">alternate_email</span>
								<span class="one-about-contact__email-text"><?php echo esc_html( $one_about_user->user_email ); ?></span>
							</a>
						</div>
					</section>
				</div>
			<?php endif; ?>
		</main>
	</div>

	<?php if ( $one_can_share ) : ?>
		<?php
		require get_stylesheet_directory() . '/inc/profile/connections-drawer.php';
		require get_stylesheet_directory() . '/inc/profile/post-detail-modal.php';
		?>
	<?php endif; ?>
</div>
<?php require get_stylesheet_directory() . '/inc/share/sent-share-mobile-nav.php'; ?>
