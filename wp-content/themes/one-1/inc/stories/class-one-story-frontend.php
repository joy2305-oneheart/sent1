<?php
/**
 * Front-end story submission form.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class One_Story_Frontend
 */
class One_Story_Frontend {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'handle_submission' ) );
		add_action( 'wp_ajax_one_story_create', array( __CLASS__, 'ajax_create_story' ) );
		add_action( 'template_redirect', array( __CLASS__, 'require_login' ), 6 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 97 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Create share-story page.
	 */
	public static function ensure_form_page() {
		$page_id = (int) get_option( 'one1_story_form_page_id', 0 );
		if ( $page_id && get_post( $page_id ) ) {
			return;
		}
		$existing = get_page_by_path( 'share-story' );
		if ( $existing instanceof WP_Post ) {
			update_option( 'one1_story_form_page_id', (int) $existing->ID );
			return;
		}
		$new_id = wp_insert_post(
			array(
				'post_title'   => __( 'Share a story', 'one' ),
				'post_name'    => 'share-story',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $new_id ) ) {
			update_option( 'one1_story_form_page_id', (int) $new_id );
		}
	}

	/**
	 * Require login on form page.
	 */
	public static function require_login() {
		if ( ! one1_is_story_form_page() || is_user_logged_in() ) {
			return;
		}
		wp_safe_redirect( one1_login_url( one1_story_form_url() ) );
		exit;
	}

	/**
	 * Template.
	 *
	 * @param string $template Template.
	 */
	public static function template_include( $template ) {
		if ( one1_is_story_form_page() && ! is_admin() ) {
			$custom = get_stylesheet_directory() . '/story-form.php';
			if ( is_readable( $custom ) ) {
				return $custom;
			}
		}
		return $template;
	}

	/**
	 * Body class.
	 *
	 * @param string[] $classes Classes.
	 */
	public static function body_class( $classes ) {
		if ( one1_is_story_form_page() ) {
			$classes[] = 'one-story-form-body';
		}
		return $classes;
	}

	/**
	 * Assets.
	 */
	public static function enqueue_assets() {
		if ( ! one1_is_story_form_page() ) {
			return;
		}
		$base = get_stylesheet_directory_uri() . '/assets/stories';
		wp_enqueue_style(
			'one-homie-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap',
			array(),
			null
		);
		$homie = get_stylesheet_directory_uri() . '/assets/homie/homie-homepage.css';
		wp_enqueue_style( 'one-homie-base', $homie, array(), ONE_STORY_VERSION );
		wp_enqueue_style( 'one-story-form', $base . '/story-form.css', array( 'one-homie-fonts', 'one-homie-base' ), ONE_STORY_VERSION );
		$form_deps = array();
		$places_key = one1_get_places_api_key();
		if ( $places_key !== '' ) {
			wp_enqueue_script(
				'google-maps-places',
				'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode( $places_key ) . '&libraries=places',
				array(),
				null,
				true
			);
			wp_script_add_data( 'google-maps-places', 'async', true );
			$form_deps[] = 'google-maps-places';
		}
		wp_enqueue_script( 'one-story-form', $base . '/story-form.js', $form_deps, ONE_STORY_VERSION, true );
		if ( function_exists( 'one1_enqueue_button_assets' ) ) {
			one1_enqueue_button_assets();
		}
		if ( function_exists( 'one1_enqueue_user_menu_assets' ) ) {
			one1_enqueue_user_menu_assets();
		}
	}

	/**
	 * Handle form POST.
	 */
	public static function handle_submission() {
		if ( ! isset( $_POST['one_story_submit'] ) ) {
			return;
		}
		if ( ! is_user_logged_in() || ! one_story_user_can_submit() ) {
			wp_die( esc_html__( 'You are not allowed to create stories.', 'one' ) );
		}
		if ( ! isset( $_POST['one_story_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one_story_form_nonce'] ) ), 'one_story_form' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'one' ) );
		}

		$fields = one1_story_parse_submission_fields();

		if ( '' === trim( $fields['content'] ) ) {
			self::redirect_with_message( __( 'Please enter a description.', 'one' ), 'error' );
		}

		$status = current_user_can( 'manage_options' ) ? 'publish' : 'publish';

		$post_id = wp_insert_post(
			array(
				'post_type'    => ONE_STORY_POST_TYPE,
				'post_title'   => $fields['title'],
				'post_content' => $fields['content'],
				'post_status'  => $status,
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_with_message( $post_id->get_error_message(), 'error' );
		}

		$data  = one1_story_collect_meta_from_request();
		$valid = one1_story_validate_meta( $data, true );
		if ( is_wp_error( $valid ) ) {
			wp_delete_post( $post_id, true );
			self::redirect_with_message( $valid->get_error_message(), 'error' );
		}

		one1_story_save_meta_and_image( $post_id, $data );

		self::maybe_notify_friends( $post_id );

		$redirect = one1_share_page_url();
		wp_safe_redirect(
			add_query_arg(
				array(
					'story_created' => '1',
					'story_id'      => (int) $post_id,
				),
				$redirect
			)
		);
		exit;
	}

	/**
	 * AJAX: create story from composer modal.
	 */
	public static function ajax_create_story() {
		if ( ! is_user_logged_in() || ! one_story_user_can_submit() ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to create stories.', 'one' ) ) );
		}

		if ( ! isset( $_POST['one_story_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one_story_form_nonce'] ) ), 'one_story_form' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'one' ) ) );
		}

		$fields = one1_story_parse_submission_fields();

		if ( '' === trim( $fields['content'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please write something for your post.', 'one' ) ) );
		}

		$data  = one1_story_collect_meta_from_request();
		$valid = one1_story_validate_meta( $data, false );
		if ( is_wp_error( $valid ) ) {
			wp_send_json_error( array( 'message' => $valid->get_error_message() ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => ONE_STORY_POST_TYPE,
				'post_title'   => $fields['title'],
				'post_content' => $fields['content'],
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		one1_story_save_meta_and_image( $post_id, $data );

		self::maybe_notify_friends( $post_id );

		wp_send_json_success( array( 'post_id' => (int) $post_id ) );
	}

	/**
	 * Redirect with flash message.
	 *
	 * @param string $message Message.
	 * @param string $type    error|success.
	 */
	private static function redirect_with_message( $message, $type ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'story_msg'  => rawurlencode( $message ),
					'story_type' => $type,
				),
				one1_story_form_url()
			)
		);
		exit;
	}

	/**
	 * Send friend email blast when requested on publish.
	 *
	 * @param int $post_id Story post ID.
	 */
	private static function maybe_notify_friends( $post_id ) {
		if ( empty( $_POST['one_story_notify_friends'] ) ) {
			return;
		}
		if ( ! class_exists( 'SIN_Email_Blasts' ) ) {
			return;
		}
		if ( ! function_exists( 'sin_is_pu' ) || ! sin_is_pu( get_current_user_id() ) ) {
			return;
		}
		SIN_Email_Blasts::send_for_story( (int) $post_id );
	}
}
