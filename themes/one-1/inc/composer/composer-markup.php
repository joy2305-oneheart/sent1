<?php
/**
 * Global post composer modal markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	'location_label'   => '',
	'location_place_id' => '',
);
$one_uid  = get_current_user_id();
$one_user = wp_get_current_user();
?>
<div class="one-composer" data-one-composer hidden aria-hidden="true">
	<div class="one-composer__backdrop" data-one-composer-close tabindex="-1"></div>

	<div
		class="one-composer__sheet"
		role="dialog"
		aria-modal="true"
		aria-labelledby="one-composer-title"
	>
		<div class="one-composer__handle" aria-hidden="true"></div>

		<header class="one-composer__header">
			<div class="one-composer__header-main">
				<?php echo get_avatar( $one_uid, 44, '', '', array( 'class' => 'one-composer__avatar' ) ); ?>
				<div>
					<h2 id="one-composer-title" class="one-composer__title"><?php esc_html_e( 'Create post', 'one' ); ?></h2>
					<p class="one-composer__subtitle"><?php echo esc_html( $one_user->display_name ); ?></p>
				</div>
			</div>
			<button type="button" class="one-composer__close" data-one-composer-close aria-label="<?php esc_attr_e( 'Close', 'one' ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">close</span>
			</button>
		</header>

		<form class="one-composer__form" data-one-composer-form enctype="multipart/form-data" novalidate>
			<?php wp_nonce_field( 'one_story_form', 'one_story_form_nonce' ); ?>
			<input type="hidden" name="one_story_post_id" value="" data-one-composer-post-id />

			<div class="one-composer__scroll">
				<div class="one-composer__compose">
					<label class="one-composer__visually-hidden" for="one-composer-content"><?php esc_html_e( 'Post text', 'one' ); ?></label>
					<textarea
						name="one_story_content"
						id="one-composer-content"
						class="one-composer__textarea"
						rows="5"
						required
						placeholder="<?php esc_attr_e( "What's on your journey today?", 'one' ); ?>"
					></textarea>
				</div>

				<div class="one-composer__media" data-one-composer-media>
					<div class="one-composer__media-empty" data-one-story-file-drop>
						<input type="file" name="one_story_featured_image" id="one-composer-image" accept="image/jpeg,image/png,image/webp,image/gif" data-one-story-file-input class="one-composer__file-input" />
						<span class="material-symbols-outlined" aria-hidden="true">add_photo_alternate</span>
						<span><?php esc_html_e( 'Add a photo', 'one' ); ?></span>
					</div>
					<div class="one-composer__media-preview" data-one-story-file-preview hidden></div>
				</div>

				<div class="one-composer__headline-block">
					<label class="one-composer__field-label" for="one-composer-title-input"><?php esc_html_e( 'Headline (optional)', 'one' ); ?></label>
					<input
						type="text"
						name="one_story_title"
						id="one-composer-title-input"
						class="one-composer__input one-composer__input--headline"
						placeholder="<?php esc_attr_e( 'Give your post a title', 'one' ); ?>"
					/>
				</div>

				<?php require get_stylesheet_directory() . '/inc/composer/composer-location-field.php'; ?>

				<label class="one-composer__donation-toggle">
					<span class="one-composer__donation-toggle-text">
						<strong><?php esc_html_e( 'Donation post', 'one' ); ?></strong>
						<small><?php esc_html_e( 'Add a fundraising goal for your circle.', 'one' ); ?></small>
					</span>
					<input type="checkbox" name="one_story_is_donation" value="1" data-one-story-donation-toggle />
					<span class="one-composer__switch" aria-hidden="true"></span>
				</label>

				<div class="one-composer__donation" data-one-story-donation-wrap hidden>
					<?php require get_stylesheet_directory() . '/inc/composer/composer-donation-fields.php'; ?>
				</div>

				<div class="one-composer__settings">
					<p class="one-composer__settings-heading"><?php esc_html_e( 'Post settings', 'one' ); ?></p>

					<label class="one-composer__settings-toggle">
						<span class="one-composer__settings-toggle-text">
							<strong><?php esc_html_e( 'Allow comments', 'one' ); ?></strong>
							<small><?php esc_html_e( 'Let circle members comment on this post.', 'one' ); ?></small>
						</span>
						<input type="checkbox" name="one_story_comments_enabled" value="1" data-one-composer-comments-toggle checked />
						<span class="one-composer__switch" aria-hidden="true"></span>
					</label>

					<label class="one-composer__settings-toggle">
						<span class="one-composer__settings-toggle-text">
							<strong><?php esc_html_e( 'Hide likes from others', 'one' ); ?></strong>
							<small><?php esc_html_e( 'Only you will see who supported this post.', 'one' ); ?></small>
						</span>
						<input type="checkbox" name="one_story_hide_likes" value="1" data-one-composer-hide-likes-toggle />
						<span class="one-composer__switch" aria-hidden="true"></span>
					</label>

					<?php if ( function_exists( 'sin_is_pu' ) && sin_is_pu( $one_uid ) ) : ?>
					<label class="one-composer__settings-toggle" data-one-composer-notify-friends-wrap>
						<span class="one-composer__settings-toggle-text">
							<strong><?php esc_html_e( 'Notify my friends by email', 'one' ); ?></strong>
							<small><?php esc_html_e( 'Send an update to everyone in your circle when you publish.', 'one' ); ?></small>
						</span>
						<input type="checkbox" name="one_story_notify_friends" value="1" data-one-composer-notify-friends-toggle checked />
						<span class="one-composer__switch" aria-hidden="true"></span>
					</label>
					<?php endif; ?>
				</div>
			</div>

			<footer class="one-composer__footer">
				<p class="one-composer__error" data-one-composer-error hidden role="alert"></p>
				<button type="submit" class="one-composer__publish" data-one-composer-submit>
					<?php esc_html_e( 'Publish', 'one' ); ?>
				</button>
			</footer>
		</form>
	</div>
</div>
