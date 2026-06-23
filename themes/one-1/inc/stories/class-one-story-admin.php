<?php
/**
 * Admin meta boxes for Stories.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class One_Story_Admin
 */
class One_Story_Admin {

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . ONE_STORY_POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'one_story_details',
			__( 'Story details', 'one' ),
			array( __CLASS__, 'render_meta_box' ),
			ONE_STORY_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'one_story_admin_save', 'one_story_admin_nonce' );
		$one_story_values  = One_Story_Meta::get_all( (int) $post->ID );
		$one_story_context = 'admin';
		require __DIR__ . '/story-meta-fields.php';
	}

	/**
	 * Save post meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_post( $post_id, $post ) {
		unset( $post );
		if ( ! isset( $_POST['one_story_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['one_story_admin_nonce'] ) ), 'one_story_admin_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$data = self::collect_posted_data();
		One_Story_Meta::save_from_array( $post_id, $data );

		if ( current_user_can( 'manage_options' ) && isset( $_POST['one_story_amount_raised'] ) ) {
			update_post_meta( $post_id, 'one_story_amount_raised', max( 0, (float) wp_unslash( $_POST['one_story_amount_raised'] ) ) );
		}
		if ( current_user_can( 'manage_options' ) && isset( $_POST['one_story_donor_count'] ) ) {
			update_post_meta( $post_id, 'one_story_donor_count', max( 0, (int) wp_unslash( $_POST['one_story_donor_count'] ) ) );
		}
	}

	/**
	 * Collect posted field values.
	 *
	 * @return array<string, mixed>
	 */
	public static function collect_posted_data() {
		$post_type = isset( $_POST['one_story_post_type'] ) ? sanitize_key( wp_unslash( $_POST['one_story_post_type'] ) ) : '';
		$is_donation = ( 'donation' === $post_type ) || ! empty( $_POST['one_story_is_donation'] );

		return array(
			'featured'          => ! empty( $_POST['one_story_featured'] ),
			'verified'          => ! empty( $_POST['one_story_verified'] ),
			'urgency'           => isset( $_POST['one_story_urgency'] ) ? sanitize_text_field( wp_unslash( $_POST['one_story_urgency'] ) ) : 'standard',
			'is_donation'       => $is_donation,
			'comments_enabled'  => ! empty( $_POST['one_story_comments_enabled'] ),
			'hide_likes'        => ! empty( $_POST['one_story_hide_likes'] ),
			'fundraising_goal'  => isset( $_POST['one_story_fundraising_goal'] ) ? wp_unslash( $_POST['one_story_fundraising_goal'] ) : 0,
			'end_date'          => isset( $_POST['one_story_end_date'] ) ? wp_unslash( $_POST['one_story_end_date'] ) : '',
			'city'              => isset( $_POST['one_story_city'] ) ? wp_unslash( $_POST['one_story_city'] ) : '',
			'state_region'      => isset( $_POST['one_story_state_region'] ) ? wp_unslash( $_POST['one_story_state_region'] ) : '',
			'location_label'    => isset( $_POST['one_story_location_label'] ) ? wp_unslash( $_POST['one_story_location_label'] ) : '',
			'location_place_id' => isset( $_POST['one_story_location_place_id'] ) ? wp_unslash( $_POST['one_story_location_place_id'] ) : '',
			'upi_id'            => isset( $_POST['one_story_upi_id'] ) ? wp_unslash( $_POST['one_story_upi_id'] ) : '',
			'payment_link'      => isset( $_POST['one_story_payment_link'] ) ? wp_unslash( $_POST['one_story_payment_link'] ) : '',
		);
	}

	/**
	 * Admin scripts.
	 *
	 * @param string $hook Hook.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ONE_STORY_POST_TYPE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style(
			'one-story-fields-admin',
			get_stylesheet_directory_uri() . '/assets/stories/story-form.css',
			array(),
			ONE_STORY_VERSION
		);
		wp_enqueue_script(
			'one-story-admin',
			get_stylesheet_directory_uri() . '/assets/stories/story-form.js',
			array(),
			ONE_STORY_VERSION,
			true
		);
	}
}
