<?php
/**
 * Front-end story form markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_can_submit   = one_story_user_can_submit();
$one_msg          = isset( $_GET['story_msg'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['story_msg'] ) ) ) : '';
$one_msg_type     = isset( $_GET['story_type'] ) ? sanitize_key( wp_unslash( $_GET['story_type'] ) ) : '';
$one_story_values = array(
	'featured'         => false,
	'verified'         => false,
	'urgency'          => 'standard',
	'is_donation'      => false,
	'fundraising_goal' => '',
	'amount_raised'    => 0,
	'donor_count'      => 0,
	'end_date'         => '',
	'city'             => '',
	'state_region'     => '',
);
$one_share_url = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/share/' );
?>
<div class="homie-homepage one-story-form-page">
	<header class="one-story-form-header">
		<div class="one-story-form-header__inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="one-story-form-brand">
				<svg class="one-story-form-brand__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M2 3h6a4 4 0 0 1 4 4v14a4 4 0 0 0-4-4H2z" />
					<path d="M22 3h-6a4 4 0 0 0-4 4v14a4 4 0 0 1 4-4h6z" />
				</svg>
				<span><?php esc_html_e( 'Sent One', 'one' ); ?></span>
			</a>
			<?php if ( is_user_logged_in() ) : ?>
				<?php one1_render_user_menu( 'share' ); ?>
			<?php endif; ?>
		</div>
	</header>

	<main class="one-story-form-main">
		<div class="one-story-form-shell">
			<header class="one-story-form-intro">
				<p class="one-story-form-eyebrow"><?php esc_html_e( 'Share your journey', 'one' ); ?></p>
				<h1 class="one-story-form-title"><?php esc_html_e( 'Create a story', 'one' ); ?></h1>
				<p class="one-story-form-lead"><?php esc_html_e( 'Tell your story, add a photo, and optionally launch a donation campaign for your community.', 'one' ); ?></p>
			</header>

			<div class="one-story-form-card">
				<?php if ( $one_msg ) : ?>
					<div class="one-story-form-alert one-story-form-alert--<?php echo esc_attr( 'error' === $one_msg_type ? 'error' : 'success' ); ?>" role="alert">
						<?php echo esc_html( $one_msg ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! $one_can_submit ) : ?>
					<div class="one-story-form-empty">
						<p><?php esc_html_e( 'Your account must be approved before you can publish stories.', 'one' ); ?></p>
						<a class="one-story-form-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'one' ); ?></a>
					</div>
				<?php else : ?>
				<form class="one-story-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( one1_story_form_url() ); ?>" novalidate>
					<?php wp_nonce_field( 'one_story_form', 'one_story_form_nonce' ); ?>

					<section class="one-story-form-block" aria-labelledby="one-story-block-story">
						<h2 id="one-story-block-story" class="one-story-form-block__title">
							<span class="one-story-form-block__step">1</span>
							<?php esc_html_e( 'Your story', 'one' ); ?>
						</h2>

						<div class="one-story-form-field">
							<label for="one-story-title"><?php esc_html_e( 'Title', 'one' ); ?> <span class="one-story-form-required" aria-hidden="true">*</span></label>
							<input type="text" name="one_story_title" id="one-story-title" required placeholder="<?php esc_attr_e( 'Give your story a headline', 'one' ); ?>" />
						</div>

						<div class="one-story-form-field">
							<label for="one-story-content"><?php esc_html_e( 'Description', 'one' ); ?> <span class="one-story-form-required" aria-hidden="true">*</span></label>
							<textarea name="one_story_content" id="one-story-content" rows="7" required placeholder="<?php esc_attr_e( 'Share what happened, what you need, and how the community can help…', 'one' ); ?>"></textarea>
						</div>

						<div class="one-story-form-field">
							<label for="one-story-image"><?php esc_html_e( 'Featured image', 'one' ); ?></label>
							<div class="one-story-file-drop" data-one-story-file-drop>
								<input type="file" name="one_story_featured_image" id="one-story-image" accept="image/jpeg,image/png,image/webp,image/gif" data-one-story-file-input />
								<div class="one-story-file-drop__inner" data-one-story-file-preview>
									<svg class="one-story-file-drop__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
										<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
									</svg>
									<p class="one-story-file-drop__label"><strong><?php esc_html_e( 'Click to upload', 'one' ); ?></strong> <?php esc_html_e( 'or drag and drop', 'one' ); ?></p>
									<p class="one-story-file-drop__hint"><?php esc_html_e( 'PNG, JPG, WebP or GIF', 'one' ); ?></p>
								</div>
							</div>
						</div>
					</section>

					<section class="one-story-form-block one-story-form-block--meta" aria-labelledby="one-story-block-details">
						<h2 id="one-story-block-details" class="one-story-form-block__title">
							<span class="one-story-form-block__step">2</span>
							<?php esc_html_e( 'Details', 'one' ); ?>
						</h2>
						<?php
						$one_story_context = 'front';
						require get_stylesheet_directory() . '/inc/stories/story-meta-fields.php';
						?>
					</section>

					<footer class="one-story-form-actions">
						<?php
						one1_button(
							array(
								'label'   => __( 'Publish story', 'one' ),
								'type'    => 'submit',
								'variant' => 'primary',
								'skin'    => 'story',
								'name'    => 'one_story_submit',
								'value'   => '1',
							)
						);
						?>
						<a class="one-story-form-cancel" href="<?php echo esc_url( $one_share_url ); ?>"><?php esc_html_e( 'Cancel', 'one' ); ?></a>
					</footer>
				</form>
				<?php endif; ?>
			</div>
		</div>
	</main>
</div>
