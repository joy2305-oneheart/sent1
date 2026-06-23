<?php
/**
 * Register Stories custom post type and capabilities.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class One_Story_CPT
 */
class One_Story_CPT {

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( 'One_Story_Meta', 'register' ), 11 );
		add_filter( 'user_has_cap', array( __CLASS__, 'grant_story_caps' ), 10, 4 );
	}

	/**
	 * Register CPT.
	 */
	public static function register() {
		$labels = array(
			'name'               => __( 'Stories', 'one' ),
			'singular_name'      => __( 'Story', 'one' ),
			'add_new'            => __( 'Add New', 'one' ),
			'add_new_item'       => __( 'Add New Story', 'one' ),
			'edit_item'          => __( 'Edit Story', 'one' ),
			'new_item'           => __( 'New Story', 'one' ),
			'view_item'          => __( 'View Story', 'one' ),
			'search_items'       => __( 'Search Stories', 'one' ),
			'not_found'          => __( 'No stories found.', 'one' ),
			'not_found_in_trash' => __( 'No stories found in Trash.', 'one' ),
			'menu_name'          => __( 'Stories', 'one' ),
		);

		register_post_type(
			ONE_STORY_POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-book-alt',
				'rewrite'             => array( 'slug' => 'stories' ),
				'supports'            => array( 'title', 'editor', 'thumbnail', 'author', 'excerpt' ),
				'capability_type'     => array( 'story', 'stories' ),
				'map_meta_cap'        => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
			)
		);
	}

	/**
	 * Grant story capabilities to approved network users and staff.
	 *
	 * @param bool[]   $allcaps All caps.
	 * @param string[] $caps    Caps.
	 * @param array    $args    Args.
	 * @param WP_User  $user    User.
	 * @return bool[]
	 */
	public static function grant_story_caps( $allcaps, $caps, $args, $user ) {
		unset( $caps, $args );

		static $granting = false;
		if ( $granting ) {
			return $allcaps;
		}

		if ( ! $user instanceof WP_User ) {
			return $allcaps;
		}
		$uid = (int) $user->ID;
		if ( $uid <= 0 ) {
			return $allcaps;
		}

		$granting = true;

		// Never call user_can() / current_user_can() here — that re-enters this filter and exhausts memory.
		$is_admin = in_array( 'administrator', (array) $user->roles, true );
		if ( $is_admin || ( function_exists( 'sin_is_staff_user' ) && sin_is_staff_user( $uid ) ) ) {
			$grant = array(
				'edit_stories',
				'edit_others_stories',
				'publish_stories',
				'read_private_stories',
				'delete_stories',
				'delete_others_stories',
				'delete_published_stories',
				'delete_private_stories',
				'edit_published_stories',
				'edit_private_stories',
			);
			foreach ( $grant as $cap ) {
				$allcaps[ $cap ] = true;
			}
			$granting = false;
			return $allcaps;
		}

		if ( function_exists( 'sin_is_pu' ) && sin_is_pu( $uid ) ) {
			$allcaps['edit_stories']             = true;
			$allcaps['publish_stories']          = true;
			$allcaps['delete_stories']           = true;
			$allcaps['delete_published_stories'] = true;
			$allcaps['edit_published_stories']   = true;
		}

		$granting = false;
		return $allcaps;
	}
}
