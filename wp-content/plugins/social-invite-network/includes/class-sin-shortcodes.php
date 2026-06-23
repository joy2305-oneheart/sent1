<?php
/**
 * Shortcodes: register, invite, feed, dashboard.
 *
 * @package Social_Invite_Network
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SIN_Shortcodes
 */
class SIN_Shortcodes {

	/**
	 * Init.
	 */
	public static function init() {
		add_shortcode( 'sin_register', array( __CLASS__, 'shortcode_register' ) );
		add_shortcode( 'sin_invite_form', array( __CLASS__, 'shortcode_invite_form' ) );
		add_shortcode( 'sin_feed', array( __CLASS__, 'shortcode_feed' ) );
		add_shortcode( 'sin_dashboard', array( __CLASS__, 'shortcode_dashboard' ) );
		add_action( 'init', array( __CLASS__, 'handle_invite_form_post' ) );
	}

	/**
	 * Registration shortcode.
	 *
	 * @param array $atts Attributes.
	 */
	public static function shortcode_register( $atts ) {
		if ( is_user_logged_in() ) {
			return '<p class="sin-muted">' . esc_html__( 'You are already logged in.', 'social-invite-network' ) . '</p>';
		}
		sin_enqueue_frontend_styles();

		$ref      = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
		$pu_token = isset( $_GET['pu_token'] ) ? sanitize_text_field( wp_unslash( $_GET['pu_token'] ) ) : '';

		if ( ! SIN_Registration::signup_is_allowed( $pu_token, $ref ) ) {
			return '<p class="sin-muted">' . esc_html__( 'Registration requires an invitation.', 'social-invite-network' ) . '</p>';
		}

		ob_start();
		if ( isset( $_GET['sin_reg_error'] ) ) {
			echo '<div class="sin-alert sin-alert--error" role="alert">' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['sin_reg_error'] ) ) ) ) . '</div>';
		}
		$action = esc_url( get_permalink() );
		?>
		<form class="sin-form" method="post" action="<?php echo esc_url( $action ); ?>">
			<?php wp_nonce_field( 'sin_register', 'sin_register_nonce' ); ?>
			<input type="hidden" name="invite_code" value="<?php echo esc_attr( $ref ); ?>" />
			<input type="hidden" name="pu_token" value="<?php echo esc_attr( $pu_token ); ?>" />
			<p>
				<label for="sin_name"><?php esc_html_e( 'Name', 'social-invite-network' ); ?></label>
				<input type="text" name="sin_name" id="sin_name" required />
			</p>
			<p>
				<label for="sin_email"><?php esc_html_e( 'Email', 'social-invite-network' ); ?></label>
				<input type="email" name="sin_email" id="sin_email" required />
			</p>
			<p>
				<label for="sin_password"><?php esc_html_e( 'Password', 'social-invite-network' ); ?></label>
				<input type="password" name="sin_password" id="sin_password" minlength="8" required />
			</p>
			<p>
				<label for="sin_password_confirm"><?php esc_html_e( 'Confirm password', 'social-invite-network' ); ?></label>
				<input type="password" name="sin_password_confirm" id="sin_password_confirm" minlength="8" required />
			</p>
			<p><button type="submit" name="sin_register_submit" value="1" class="sin-button"><?php esc_html_e( 'Register', 'social-invite-network' ); ?></button></p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Invite form shortcode.
	 *
	 * @param array $atts Attributes.
	 */
	public static function shortcode_invite_form( $atts ) {
		if ( ! is_user_logged_in() || ! sin_is_pu( get_current_user_id() ) ) {
			return '';
		}
		$url = SIN_Invitations::invite_page_url();
		return '<p class="sin-muted">' . wp_kses_post(
			sprintf(
				/* translators: %s: URL to invite page */
				__( 'Invitations are sent from the <a href="%s">invite page</a>.', 'social-invite-network' ),
				esc_url( $url )
			)
		) . '</p>';
	}

	/**
	 * Process invite form.
	 */
	public static function handle_invite_form_post() {
		if ( ! isset( $_POST['sin_invite_form'] ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		$uid = get_current_user_id();
		if ( ! sin_is_pu( $uid ) ) {
			return;
		}
		if ( ! isset( $_POST['sin_invite_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sin_invite_nonce'] ) ), 'sin_invite' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'social-invite-network' ) );
		}
		$email = isset( $_POST['sin_invite_email'] ) ? sanitize_email( wp_unslash( $_POST['sin_invite_email'] ) ) : '';
		SIN_Invitations::set_flash_and_redirect( $uid, SIN_Invitations::submit_invite_for_user( $uid, $email ) );
	}

	/**
	 * Feed shortcode (network posts removed; stories feed lives in the theme).
	 *
	 * @param array $atts Attributes.
	 */
	public static function shortcode_feed( $atts ) {
		if ( ! is_user_logged_in() || ! sin_is_network_approved( get_current_user_id() ) ) {
			return '<p class="sin-muted">' . esc_html__( 'You must be logged in with an approved account to view the feed.', 'social-invite-network' ) . '</p>';
		}
		$url = function_exists( 'one1_share_page_url' ) ? one1_share_page_url() : home_url( '/' );
		return '<p class="sin-muted">' . wp_kses_post(
			sprintf(
				/* translators: %s: sharing feed URL */
				__( 'The member feed is on the <a href="%s">sharing page</a>.', 'social-invite-network' ),
				esc_url( $url )
			)
		) . '</p>';
	}

	/**
	 * Dashboard shortcode.
	 *
	 * @param array $atts Attributes.
	 */
	public static function shortcode_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p class="sin-muted">' . esc_html__( 'Please log in.', 'social-invite-network' ) . '</p>';
		}
		$uid = get_current_user_id();
		if ( ! sin_is_network_approved( $uid ) ) {
			return '<p class="sin-muted">' . esc_html__( 'Your member tools will be available once your account is active.', 'social-invite-network' ) . '</p>';
		}
		if ( ! sin_is_pu( $uid ) ) {
			return '<p class="sin-muted">' . esc_html__( 'Invite tools are available to Primary Users only.', 'social-invite-network' ) . '</p>';
		}
		$url = SIN_Invitations::invite_page_url();
		return '<p class="sin-muted">' . wp_kses_post(
			sprintf(
				/* translators: %s: invite page URL */
				__( 'Profile and invitations are on the <a href="%s">invite page</a>.', 'social-invite-network' ),
				esc_url( $url )
			)
		) . '</p>';
	}
}
