<?php
/**
 * Shared story form fields (full page + composer modal).
 *
 * @package one
 *
 * @var array<string, mixed> $one_story_values Field values.
 * @var string               $one_story_context front|composer|admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_story_values  = isset( $one_story_values ) ? $one_story_values : array();
$one_story_context = isset( $one_story_context ) ? $one_story_context : 'front';
$one_is_composer   = ( 'composer' === $one_story_context );
$one_is_donation   = ! empty( $one_story_values['is_donation'] );
$one_post_type     = $one_is_donation ? 'donation' : 'journey';
$one_uid           = get_current_user_id();
$one_field_prefix  = $one_is_composer ? 'one-composer' : 'one-story';
?>

<div class="one-story-compose-fields" data-one-story-compose-fields>
	<?php if ( $one_is_composer && $one_uid ) : ?>
		<div class="one-composer-user">
			<?php echo get_avatar( $one_uid, 48, '', '', array( 'class' => 'one-composer-user__avatar' ) ); ?>
			<span class="one-composer-user__name"><?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
		</div>
	<?php endif; ?>

	<div class="one-story-form-field one-story-form-field--composer-text">
		<?php if ( ! $one_is_composer ) : ?>
			<label for="<?php echo esc_attr( $one_field_prefix ); ?>-title"><?php esc_html_e( 'Title', 'one' ); ?> <span class="one-story-form-required" aria-hidden="true">*</span></label>
		<?php endif; ?>
		<input
			type="text"
			name="one_story_title"
			id="<?php echo esc_attr( $one_field_prefix ); ?>-title"
			class="one-composer-title-input"
			<?php echo $one_is_composer ? '' : 'required'; ?>
			placeholder="<?php echo esc_attr( $one_is_composer ? __( 'Add a headline (optional)', 'one' ) : __( 'Give your story a headline', 'one' ) ); ?>"
		/>
	</div>

	<div class="one-story-form-field">
		<?php if ( ! $one_is_composer ) : ?>
			<label for="<?php echo esc_attr( $one_field_prefix ); ?>-content"><?php esc_html_e( 'Description', 'one' ); ?> <span class="one-story-form-required" aria-hidden="true">*</span></label>
		<?php endif; ?>
		<textarea
			name="one_story_content"
			id="<?php echo esc_attr( $one_field_prefix ); ?>-content"
			class="one-composer-textarea"
			rows="<?php echo $one_is_composer ? '4' : '7'; ?>"
			required
			placeholder="<?php esc_attr_e( "What's on your journey today?", 'one' ); ?>"
		></textarea>
	</div>

	<div class="one-story-form-field one-story-form-field--media">
		<?php if ( ! $one_is_composer ) : ?>
			<label for="<?php echo esc_attr( $one_field_prefix ); ?>-image"><?php esc_html_e( 'Featured image', 'one' ); ?></label>
		<?php endif; ?>
		<div class="one-story-file-drop<?php echo $one_is_composer ? ' one-story-file-drop--composer' : ''; ?>" data-one-story-file-drop>
			<input type="file" name="one_story_featured_image" id="<?php echo esc_attr( $one_field_prefix ); ?>-image" accept="image/jpeg,image/png,image/webp,image/gif" data-one-story-file-input />
			<div class="one-story-file-drop__inner" data-one-story-file-preview>
				<?php if ( $one_is_composer ) : ?>
					<span class="material-symbols-outlined one-story-file-drop__icon-composer" aria-hidden="true">add_photo_alternate</span>
					<p class="one-story-file-drop__label"><?php esc_html_e( 'Add photo', 'one' ); ?></p>
				<?php else : ?>
					<svg class="one-story-file-drop__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
						<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
					</svg>
					<p class="one-story-file-drop__label"><strong><?php esc_html_e( 'Click to upload', 'one' ); ?></strong> <?php esc_html_e( 'or drag and drop', 'one' ); ?></p>
					<p class="one-story-file-drop__hint"><?php esc_html_e( 'PNG, JPG, WebP or GIF', 'one' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="one-story-post-type" data-one-story-post-type role="group" aria-label="<?php esc_attr_e( 'Post type', 'one' ); ?>">
		<label class="one-story-post-type__pill<?php echo 'journey' === $one_post_type ? ' is-active' : ''; ?>">
			<input type="radio" name="one_story_post_type" value="journey" data-one-story-post-type-input <?php checked( $one_post_type, 'journey' ); ?> />
			<span><?php esc_html_e( 'Journey', 'one' ); ?></span>
		</label>
		<label class="one-story-post-type__pill<?php echo 'donation' === $one_post_type ? ' is-active' : ''; ?>">
			<input type="radio" name="one_story_post_type" value="donation" data-one-story-post-type-input <?php checked( $one_post_type, 'donation' ); ?> />
			<span><?php esc_html_e( 'Donation', 'one' ); ?></span>
		</label>
		<input type="hidden" name="one_story_is_donation" value="<?php echo $one_is_donation ? '1' : '0'; ?>" data-one-story-is-donation-hidden />
	</div>

	<?php if ( $one_is_composer ) : ?>
		<div class="one-story-donation-wrap" data-one-story-donation-wrap <?php echo $one_is_donation ? '' : 'hidden'; ?>>
			<?php
			$one_story_context = 'composer';
			require get_stylesheet_directory() . '/inc/stories/story-meta-fields.php';
			?>
		</div>
	<?php endif; ?>
</div>
