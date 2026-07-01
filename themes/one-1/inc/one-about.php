<?php
/**
 * Member About page — banner, journey, posts, circle, contact.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONE1_BANNER_ATTACHMENT_META', 'one1_banner_attachment_id' );
define( 'ONE1_ABOUT_JOURNEY_META', 'one1_about_journey' );
define( 'ONE1_ABOUT_JOURNEY_MAX_LENGTH', 500 );

/**
 * Register About user meta.
 */
function one1_register_about_user_meta() {
	register_meta(
		'user',
		ONE1_ABOUT_JOURNEY_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => static function () {
				return is_user_logged_in();
			},
		)
	);

	register_meta(
		'user',
		ONE1_BANNER_ATTACHMENT_META,
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static function () {
				return is_user_logged_in();
			},
		)
	);
}
add_action( 'init', 'one1_register_about_user_meta' );

/**
 * Allow media uploads during About AJAX for member roles without upload_files.
 *
 * @param array<string, bool> $allcaps All capabilities.
 * @param array<int, string>  $caps    Required capabilities.
 */
function one1_about_grant_upload_cap( $allcaps, $caps ) {
	if ( empty( $GLOBALS['one1_about_allow_upload'] ) ) {
		return $allcaps;
	}
	if ( in_array( 'upload_files', $caps, true ) ) {
		$allcaps['upload_files'] = true;
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'one1_about_grant_upload_cap', 10, 2 );

/**
 * About page ID.
 */
function one1_about_page_id() {
	return (int) get_option( 'one1_about_page_id', 0 );
}

/**
 * About page URL.
 */
function one1_about_page_url() {
	$page_id = one1_about_page_id();
	if ( $page_id ) {
		$url = get_permalink( $page_id );
		if ( $url ) {
			return $url;
		}
	}
	$page = get_page_by_path( 'about' );
	if ( $page ) {
		return get_permalink( $page );
	}
	return home_url( '/about/' );
}

/**
 * Whether current request is the About page.
 */
function one1_is_about_page() {
	if ( ! is_page() ) {
		return false;
	}
	$page_id = one1_about_page_id();
	if ( $page_id && (int) get_queried_object_id() === $page_id ) {
		return true;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && 'about' === $post->post_name;
}

/**
 * Create About page if missing.
 */
function one1_ensure_about_page() {
	$page_id = one1_about_page_id();
	if ( $page_id && get_post( $page_id ) ) {
		return;
	}
	$existing = get_page_by_path( 'about' );
	if ( $existing instanceof WP_Post ) {
		update_option( 'one1_about_page_id', (int) $existing->ID );
		return;
	}
	$new_id = wp_insert_post(
		array(
			'post_title'   => __( 'About', 'one' ),
			'post_name'    => 'about',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		),
		true
	);
	if ( ! is_wp_error( $new_id ) ) {
		update_option( 'one1_about_page_id', (int) $new_id );
	}
}

/**
 * Default banner image URL (theme asset).
 */
function one1_default_banner_url() {
	return get_stylesheet_directory_uri() . '/assets/homie/images/charity.jpg';
}

/**
 * Get custom banner attachment ID.
 *
 * @param int $user_id User ID.
 */
function one1_get_banner_attachment_id( $user_id ) {
	return max( 0, (int) get_user_meta( (int) $user_id, ONE1_BANNER_ATTACHMENT_META, true ) );
}

/**
 * Get banner image URL (custom or default).
 *
 * @param int    $user_id User ID.
 * @param string $size    Image size.
 */
function one1_get_banner_url( $user_id, $size = 'large' ) {
	$attachment_id = one1_get_banner_attachment_id( $user_id );
	if ( $attachment_id <= 0 ) {
		return one1_default_banner_url();
	}
	$url = wp_get_attachment_image_url( $attachment_id, $size );
	return is_string( $url ) && $url !== '' ? $url : one1_default_banner_url();
}

/**
 * Whether the user has a custom banner.
 *
 * @param int $user_id User ID.
 */
function one1_has_custom_banner( $user_id ) {
	return one1_get_banner_attachment_id( $user_id ) > 0;
}

/**
 * Get stored about journey text.
 *
 * @param int $user_id User ID.
 */
function one1_get_about_journey( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return '';
	}
	return sanitize_textarea_field( (string) get_user_meta( $user_id, ONE1_ABOUT_JOURNEY_META, true ) );
}

/**
 * Default journey placeholder by role.
 *
 * @param int $user_id User ID.
 */
function one1_get_about_journey_display( $user_id ) {
	$journey = one1_get_about_journey( $user_id );
	if ( $journey !== '' ) {
		return $journey;
	}
	$is_friend = function_exists( 'sin_is_friend' ) && sin_is_friend( $user_id );
	if ( $is_friend ) {
		return __( 'Share what brought you to Sent One and the journeys you are following in your circle.', 'one' );
	}
	return __( 'Tell your circle about your journey — what you are walking through and why you chose to share it here.', 'one' );
}

/**
 * Require login on About page.
 */
function one1_about_page_access() {
	if ( ! one1_is_about_page() || is_admin() ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}
	wp_safe_redirect( one1_login_url( one1_about_page_url() ) );
	exit;
}
add_action( 'template_redirect', 'one1_about_page_access', 5 );

/**
 * Swap template for About page.
 *
 * @param string $template Template path.
 */
function one1_about_template_include( $template ) {
	if ( one1_is_about_page() && ! is_admin() ) {
		$custom = get_stylesheet_directory() . '/about.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'one1_about_template_include', 95 );

/**
 * Enqueue About page assets.
 */
function one1_about_enqueue_assets() {
	if ( ! one1_is_about_page() || is_admin() ) {
		return;
	}

	$ver  = '1.7.7';
	$base = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'one-share-fonts',
		'https://fonts.googleapis.com/css2?family=Libre+Caslon+Text:ital,wght@0,400;0,700;1,400&family=Manrope:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-share-material-icons',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'one-share-feed',
		$base . '/assets/sharing/sharing-feed.css',
		array( 'one-share-fonts' ),
		$ver
	);

	wp_enqueue_style(
		'one-single-story',
		$base . '/assets/sharing/one-single-story.css',
		array( 'one-share-feed' ),
		$ver
	);

	wp_enqueue_style(
		'one-profile',
		$base . '/assets/profile/one-profile.css',
		array( 'one-share-feed' ),
		$ver
	);

	one1_enqueue_button_assets( array( 'one-share-feed' ) );
	one1_enqueue_user_menu_assets();
	one1_enqueue_connections_assets();

	wp_enqueue_style(
		'one-about',
		$base . '/assets/profile/one-about.css',
		array( 'one-share-feed', 'one-profile', 'one-connections' ),
		$ver
	);

	one1_enqueue_story_view_scripts( $ver, $base );

	wp_enqueue_script(
		'one-share-feed',
		$base . '/assets/sharing/one-share-feed.js',
		array(),
		$ver,
		true
	);

	wp_enqueue_script(
		'one-profile',
		$base . '/assets/profile/one-profile.js',
		array( 'one-connections', 'one-share-feed', 'one-story-view', 'one-donation-form' ),
		$ver,
		true
	);

	wp_localize_script(
		'one-profile',
		'oneProfileConfig',
		one1_profile_modal_config()
	);

	wp_enqueue_script(
		'one-about',
		$base . '/assets/profile/one-about.js',
		array(),
		$ver,
		true
	);

	wp_localize_script(
		'one-about',
		'oneAboutEdit',
		one1_about_edit_config()
	);
}
add_action( 'wp_enqueue_scripts', 'one1_about_enqueue_assets', 27 );

/**
 * Whether the current user may edit their About page.
 */
function one1_can_edit_about() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user_id = get_current_user_id();
	if ( function_exists( 'sin_is_network_approved' ) && ! sin_is_network_approved( $user_id ) ) {
		return false;
	}
	return true;
}

/**
 * Handle About page updates (journey text, banner upload/delete).
 */
function one1_ajax_update_about() {
	if ( ! one1_can_edit_about() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in to edit your About page.', 'one' ) ), 403 );
	}

	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'one1_update_about' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'one' ) ), 403 );
	}

	$user_id = get_current_user_id();
	$GLOBALS['one1_about_allow_upload'] = true;

	if ( isset( $_POST['journey'] ) ) {
		$journey = sanitize_textarea_field( wp_unslash( $_POST['journey'] ) );
		if ( mb_strlen( $journey ) > ONE1_ABOUT_JOURNEY_MAX_LENGTH ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: max characters */
						__( 'Journey text must be %d characters or fewer.', 'one' ),
						ONE1_ABOUT_JOURNEY_MAX_LENGTH
					),
				),
				400
			);
		}
		update_user_meta( $user_id, ONE1_ABOUT_JOURNEY_META, $journey );
	}

	if ( ! empty( $_POST['remove_banner'] ) && '1' === (string) $_POST['remove_banner'] ) {
		$result = one1_delete_banner( $user_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
	}

	if ( ! empty( $_FILES['banner']['name'] ) ) {
		$result = one1_handle_banner_upload( $user_id );
		if ( is_wp_error( $result ) ) {
			unset( $GLOBALS['one1_about_allow_upload'] );
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
	}

	unset( $GLOBALS['one1_about_allow_upload'] );

	$banner_url = one1_get_banner_url( $user_id, 'large' );
	if ( one1_has_custom_banner( $user_id ) ) {
		$banner_url = add_query_arg( 'v', (string) time(), $banner_url );
	}

	wp_send_json_success(
		array(
			'journey'           => one1_get_about_journey( $user_id ),
			'journey_display'   => one1_get_about_journey_display( $user_id ),
			'banner_url'        => $banner_url,
			'has_custom_banner' => one1_has_custom_banner( $user_id ),
		)
	);
}
add_action( 'wp_ajax_one1_update_about', 'one1_ajax_update_about' );

/**
 * Upload and attach a new banner image.
 *
 * @param int $user_id User ID.
 * @return int|WP_Error Attachment ID or error.
 */
function one1_handle_banner_upload( $user_id ) {
	if ( empty( $_FILES['banner']['name'] ) || empty( $_FILES['banner']['tmp_name'] ) ) {
		return new WP_Error( 'invalid_file', __( 'Invalid upload.', 'one' ) );
	}

	if ( ! is_uploaded_file( $_FILES['banner']['tmp_name'] ) ) {
		return new WP_Error( 'invalid_file', __( 'Invalid upload.', 'one' ) );
	}

	$max_bytes = 4 * MB_IN_BYTES;
	if ( ! empty( $_FILES['banner']['size'] ) && (int) $_FILES['banner']['size'] > $max_bytes ) {
		return new WP_Error( 'file_too_large', __( 'Banner image must be 4 MB or smaller.', 'one' ) );
	}

	$allowed = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
	);

	$checked = wp_check_filetype_and_ext(
		$_FILES['banner']['tmp_name'],
		$_FILES['banner']['name'],
		$allowed
	);
	if ( empty( $checked['type'] ) || ! in_array( $checked['type'], $allowed, true ) ) {
		return new WP_Error( 'invalid_type', __( 'Please upload a JPEG, PNG, or WebP image.', 'one' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_handle_upload(
		'banner',
		0,
		array(
			'post_author' => (int) $user_id,
		),
		array(
			'test_form' => false,
			'mimes'     => $allowed,
		)
	);

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	$old_id = one1_get_banner_attachment_id( $user_id );
	update_user_meta( $user_id, ONE1_BANNER_ATTACHMENT_META, (int) $attachment_id );

	if ( $old_id > 0 && $old_id !== (int) $attachment_id ) {
		wp_delete_attachment( $old_id, true );
	}

	return (int) $attachment_id;
}

/**
 * Remove custom banner and revert to default.
 *
 * @param int $user_id User ID.
 * @return true|WP_Error
 */
function one1_delete_banner( $user_id ) {
	$old_id = one1_get_banner_attachment_id( $user_id );
	delete_user_meta( $user_id, ONE1_BANNER_ATTACHMENT_META );
	if ( $old_id > 0 ) {
		wp_delete_attachment( $old_id, true );
	}
	return true;
}

/**
 * About edit config for JS.
 *
 * @return array<string, mixed>
 */
function one1_about_edit_config() {
	return array(
		'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
		'nonce'         => wp_create_nonce( 'one1_update_about' ),
		'maxJourneyLen' => ONE1_ABOUT_JOURNEY_MAX_LENGTH,
		'defaultBanner' => one1_default_banner_url(),
		'i18n'          => array(
			'save'          => __( 'Save', 'one' ),
			'cancel'        => __( 'Cancel', 'one' ),
			'edit'          => __( 'Edit', 'one' ),
			'error'         => __( 'Something went wrong. Please try again.', 'one' ),
			'success'       => __( 'About page updated.', 'one' ),
			'bannerChanged' => __( 'Banner updated.', 'one' ),
			'bannerRemoved' => __( 'Banner removed.', 'one' ),
			'changeBanner'  => __( 'Change banner', 'one' ),
			'uploadBanner'  => __( 'Upload banner', 'one' ),
			'removeBanner'  => __( 'Remove banner', 'one' ),
			'confirmRemove' => __( 'Remove your custom banner and use the default?', 'one' ),
		),
	);
}

/**
 * Show About fields on the WordPress user profile screen (admin).
 *
 * @param WP_User $user User object.
 */
function one1_about_admin_user_fields( $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$journey       = one1_get_about_journey( (int) $user->ID );
	$attachment_id = one1_get_banner_attachment_id( (int) $user->ID );
	$banner_url    = one1_get_banner_url( (int) $user->ID, 'medium' );
	?>
	<h2><?php esc_html_e( 'Sent One — About page', 'one' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<label for="one1_about_journey_admin"><?php esc_html_e( 'Journey text', 'one' ); ?></label>
			</th>
			<td>
				<textarea
					name="one1_about_journey_admin"
					id="one1_about_journey_admin"
					class="large-text"
					rows="5"
					maxlength="<?php echo esc_attr( (string) ONE1_ABOUT_JOURNEY_MAX_LENGTH ); ?>"
				><?php echo esc_textarea( $journey ); ?></textarea>
				<p class="description">
					<?php
					printf(
						/* translators: %d: max characters */
						esc_html__( 'Shown on the member About page. Max %d characters.', 'one' ),
						(int) ONE1_ABOUT_JOURNEY_MAX_LENGTH
					);
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Banner image', 'one' ); ?></th>
			<td>
				<?php if ( $attachment_id > 0 ) : ?>
					<p>
						<img src="<?php echo esc_url( $banner_url ); ?>" alt="" style="max-width:320px;height:auto;border-radius:8px;" />
					</p>
					<p class="description">
						<?php
						printf(
							/* translators: %d: attachment ID */
							esc_html__( 'Attachment ID: %d (members can replace this from their About page).', 'one' ),
							(int) $attachment_id
						);
						?>
					</p>
					<label>
						<input type="checkbox" name="one1_about_remove_banner_admin" value="1" />
						<?php esc_html_e( 'Remove custom banner', 'one' ); ?>
					</label>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No custom banner — the default theme image is used.', 'one' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'one1_about_admin_user_fields' );
add_action( 'edit_user_profile', 'one1_about_admin_user_fields' );

/**
 * Save About fields from the WordPress user profile screen (admin).
 *
 * @param int $user_id User ID.
 */
function one1_about_admin_save_user_fields( $user_id ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}

	if ( isset( $_POST['one1_about_journey_admin'] ) ) {
		$journey = sanitize_textarea_field( wp_unslash( $_POST['one1_about_journey_admin'] ) );
		if ( mb_strlen( $journey ) > ONE1_ABOUT_JOURNEY_MAX_LENGTH ) {
			$journey = mb_substr( $journey, 0, ONE1_ABOUT_JOURNEY_MAX_LENGTH );
		}
		update_user_meta( $user_id, ONE1_ABOUT_JOURNEY_META, $journey );
	}

	if ( ! empty( $_POST['one1_about_remove_banner_admin'] ) ) {
		one1_delete_banner( $user_id );
	}
}
add_action( 'personal_options_update', 'one1_about_admin_save_user_fields' );
add_action( 'edit_user_profile_update', 'one1_about_admin_save_user_fields' );
