<?php
/**
 * Story view analytics and engagement helpers.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONE1_STORY_VIEWS_DB_VERSION', '1' );

/**
 * Story views table name.
 */
function one1_story_views_table() {
	global $wpdb;
	return $wpdb->prefix . 'one_story_views';
}

/**
 * Create or upgrade the views table.
 */
function one1_story_views_install_table() {
	if ( get_option( 'one1_story_views_db_version', '' ) === ONE1_STORY_VIEWS_DB_VERSION ) {
		return;
	}

	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table           = one1_story_views_table();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		story_id bigint(20) unsigned NOT NULL DEFAULT 0,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		viewed_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		UNIQUE KEY story_user (story_id, user_id),
		KEY story_id (story_id)
	) {$charset_collate};";

	dbDelta( $sql );

	update_option( 'one1_story_views_db_version', ONE1_STORY_VIEWS_DB_VERSION, false );
}
add_action( 'after_setup_theme', 'one1_story_views_install_table', 15 );

/**
 * Whether comments are enabled for a story.
 *
 * @param int $post_id Post ID.
 */
function one1_story_comments_enabled( $post_id ) {
	if ( ! metadata_exists( 'post', $post_id, 'one_story_comments_enabled' ) ) {
		return true;
	}
	return (bool) get_post_meta( $post_id, 'one_story_comments_enabled', true );
}

/**
 * Whether likes/support UI is hidden from non-owners.
 *
 * @param int $post_id Post ID.
 */
function one1_story_hide_likes( $post_id ) {
	return (bool) get_post_meta( $post_id, 'one_story_hide_likes', true );
}

/**
 * Record a unique view for a story by a user.
 *
 * @param int $post_id Story post ID.
 * @param int $user_id Viewer user ID.
 */
function one1_record_story_view( $post_id, $user_id ) {
	$post_id = (int) $post_id;
	$user_id = (int) $user_id;

	if ( $post_id <= 0 || $user_id <= 0 ) {
		return;
	}

	if ( ! function_exists( 'one1_can_user_view_story' ) || ! one1_can_user_view_story( $post_id, $user_id ) ) {
		return;
	}

	$post = get_post( $post_id );
	if ( $post && (int) $post->post_author === $user_id ) {
		return;
	}

	global $wpdb;
	$table = one1_story_views_table();
	$now   = current_time( 'mysql', true );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (story_id, user_id, viewed_at) VALUES (%d, %d, %s)
			ON DUPLICATE KEY UPDATE viewed_at = VALUES(viewed_at)",
			$post_id,
			$user_id,
			$now
		)
	);
}

/**
 * Total unique view count for a story.
 *
 * @param int $post_id Post ID.
 */
function one1_get_story_view_count( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return 0;
	}

	global $wpdb;
	$table = one1_story_views_table();

	$post       = get_post( $post_id );
	$author_id  = $post ? (int) $post->post_author : 0;

	if ( $author_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE story_id = %d AND user_id != %d",
				$post_id,
				$author_id
			)
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE story_id = %d", $post_id ) );
	}

	return max( 0, (int) $count );
}

/**
 * Viewer rows for a story (most recent first).
 *
 * @param int $post_id        Post ID.
 * @param int $limit          Max rows.
 * @param int $exclude_user_id Optional user ID to omit (e.g. post author).
 * @return array<int, array{user_id: int, viewed_at: string}>
 */
function one1_get_story_viewer_rows( $post_id, $limit = 50, $exclude_user_id = 0 ) {
	$post_id         = (int) $post_id;
	$limit           = max( 1, min( 100, (int) $limit ) );
	$exclude_user_id = (int) $exclude_user_id;
	if ( $post_id <= 0 ) {
		return array();
	}

	global $wpdb;
	$table = one1_story_views_table();

	if ( $exclude_user_id > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, viewed_at FROM {$table} WHERE story_id = %d AND user_id != %d ORDER BY viewed_at DESC LIMIT %d",
				$post_id,
				$exclude_user_id,
				$limit
			),
			ARRAY_A
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, viewed_at FROM {$table} WHERE story_id = %d ORDER BY viewed_at DESC LIMIT %d",
				$post_id,
				$limit
			),
			ARRAY_A
		);
	}

	if ( ! is_array( $rows ) ) {
		return array();
	}

	return array_values(
		array_map(
			static function ( $row ) {
				return array(
					'user_id'    => (int) ( $row['user_id'] ?? 0 ),
					'viewed_at'  => (string) ( $row['viewed_at'] ?? '' ),
				);
			},
			$rows
		)
	);
}

/**
 * Supporter user objects for a story.
 *
 * @param int $post_id Post ID.
 * @return WP_User[]
 */
function one1_get_story_supporter_users( $post_id ) {
	if ( ! function_exists( 'one1_get_story_supporter_ids' ) ) {
		return array();
	}

	$ids   = one1_get_story_supporter_ids( $post_id );
	$users = array();

	foreach ( $ids as $uid ) {
		$user = get_userdata( (int) $uid );
		if ( $user instanceof WP_User ) {
			$users[] = $user;
		}
	}

	return $users;
}

/**
 * Render public view count badge.
 *
 * @param int $post_id Post ID.
 */
function one1_render_story_view_count( $post_id ) {
	$count = one1_get_story_view_count( $post_id );
	?>
	<span class="one-story-view-count" title="<?php esc_attr_e( 'Views', 'one' ); ?>">
		<span class="material-symbols-outlined one-story-view-count__icon" aria-hidden="true">visibility</span>
		<span class="one-story-view-count__num"><?php echo esc_html( (string) $count ); ?></span>
		<span class="screen-reader-text"><?php esc_html_e( 'views', 'one' ); ?></span>
	</span>
	<?php
}

/**
 * Insights label: email local-part (before @), with sensible fallbacks.
 *
 * @param WP_User $user User object.
 */
function one1_story_insights_user_label( $user ) {
	if ( ! $user instanceof WP_User ) {
		return '';
	}

	$email = (string) $user->user_email;
	if ( $email !== '' && false !== strpos( $email, '@' ) ) {
		return strstr( $email, '@', true );
	}

	if ( $email !== '' ) {
		return $email;
	}

	return $user->user_login ? $user->user_login : $user->display_name;
}

/**
 * Render author-only insights panel (for right sidebar).
 *
 * @param int $post_id   Post ID.
 * @param int $viewer_id Viewer user ID.
 */
function one1_render_story_insights_panel( $post_id, $viewer_id ) {
	$post_id   = (int) $post_id;
	$viewer_id = (int) $viewer_id;
	$post      = get_post( $post_id );

	if ( ! $post || (int) $post->post_author !== $viewer_id ) {
		return;
	}

	$author_id  = (int) $post->post_author;
	$view_count = one1_get_story_view_count( $post_id );
	$viewers    = one1_get_story_viewer_rows( $post_id, 50, $author_id );
	$supporters = one1_get_story_supporter_users( $post_id );
	?>
	<section class="one-story-insights" aria-label="<?php esc_attr_e( 'Post insights', 'one' ); ?>">
		<h3 class="one-story-insights__title"><?php esc_html_e( 'Insights', 'one' ); ?></h3>
		<p class="one-story-insights__stat">
			<?php
			printf(
				/* translators: %d: view count */
				esc_html( _n( '%d view', '%d views', $view_count, 'one' ) ),
				(int) $view_count
			);
			?>
		</p>

		<div class="one-story-insights__block">
			<h4 class="one-story-insights__label"><?php esc_html_e( 'Who viewed', 'one' ); ?></h4>
			<?php if ( empty( $viewers ) ) : ?>
				<p class="one-story-insights__empty"><?php esc_html_e( 'No views yet.', 'one' ); ?></p>
			<?php else : ?>
				<ul class="one-story-insights__list">
					<?php foreach ( $viewers as $row ) : ?>
						<?php
						$user = get_userdata( (int) $row['user_id'] );
						if ( ! $user ) {
							continue;
						}
						$viewed_ts = $row['viewed_at'] ? strtotime( $row['viewed_at'] . ' UTC' ) : 0;
						?>
						<li class="one-story-insights__item">
							<?php echo get_avatar( $user->ID, 32, '', '', array( 'class' => 'one-story-insights__avatar' ) ); ?>
							<div class="one-story-insights__item-body">
								<span class="one-story-insights__name"><?php echo esc_html( one1_story_insights_user_label( $user ) ); ?></span>
								<?php if ( $viewed_ts > 0 ) : ?>
									<time class="one-story-insights__time" datetime="<?php echo esc_attr( gmdate( 'c', $viewed_ts ) ); ?>">
										<?php
										printf(
											/* translators: %s: human-readable time */
											esc_html__( '%s ago', 'one' ),
											esc_html( human_time_diff( $viewed_ts, (int) current_time( 'timestamp', true ) ) )
										);
										?>
									</time>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="one-story-insights__block">
			<h4 class="one-story-insights__label"><?php esc_html_e( 'Who supported', 'one' ); ?></h4>
			<p class="one-story-insights__hint"><?php esc_html_e( 'Circle members who tapped Support on this post.', 'one' ); ?></p>
			<?php if ( empty( $supporters ) ) : ?>
				<p class="one-story-insights__empty"><?php esc_html_e( 'No support yet.', 'one' ); ?></p>
			<?php else : ?>
				<ul class="one-story-insights__list">
					<?php foreach ( $supporters as $user ) : ?>
						<li class="one-story-insights__item">
							<?php echo get_avatar( $user->ID, 32, '', '', array( 'class' => 'one-story-insights__avatar' ) ); ?>
							<span class="one-story-insights__name"><?php echo esc_html( one1_story_insights_user_label( $user ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
