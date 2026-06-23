<?php
/**
 * Reset password page markup (from email link).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rp_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
$rp_login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
$reset_error = one1_auth_query_message( 'reset_error' );

$invalid_link = ( '' === $rp_key || '' === $rp_login );
$user_check   = null;
if ( ! $invalid_link ) {
	$user_check = check_password_reset_key( $rp_key, $rp_login );
	if ( is_wp_error( $user_check ) ) {
		$invalid_link = true;
		if ( ! $reset_error ) {
			$reset_error = $user_check->get_error_message();
		}
	}
}
?>
<div class="homie-homepage homie-auth">
	<header class="homie-auth-header">
		<a href="<?php echo esc_url( one1_login_url() ); ?>" class="logo-link">
			<svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<path d="M2 3h6a4 4 0 0 1 4 4v14a4 4 0 0 0-4-4H2z" />
				<path d="M22 3h-6a4 4 0 0 0-4 4v14a4 4 0 0 1 4-4h6z" />
			</svg>
			<span class="logo-text">Sent One</span>
		</a>
	</header>

	<main class="homie-auth-main">
		<div class="homie-auth-card">
			<p class="homie-auth-eyebrow"><?php esc_html_e( 'Account recovery', 'one' ); ?></p>
			<h1 class="homie-auth-title"><?php esc_html_e( 'Choose a new password', 'one' ); ?></h1>

			<?php if ( $invalid_link ) : ?>
				<div class="homie-auth-alert homie-auth-alert--error" role="alert">
					<?php
					echo esc_html(
						$reset_error
							? $reset_error
							: __( 'This reset link is invalid or has expired. Please request a new one.', 'one' )
					);
					?>
				</div>
				<p class="homie-auth-footer-text">
					<a class="homie-auth-link" href="<?php echo esc_url( one1_forgot_password_url() ); ?>"><?php esc_html_e( 'Request a new link', 'one' ); ?></a>
				</p>
			<?php else : ?>
				<p class="homie-auth-lead"><?php esc_html_e( 'Enter a new password for your account.', 'one' ); ?></p>

				<?php if ( $reset_error ) : ?>
					<div class="homie-auth-alert homie-auth-alert--error" role="alert"><?php echo esc_html( $reset_error ); ?></div>
				<?php endif; ?>

				<form class="homie-auth-form" method="post" action="<?php echo esc_url( one1_reset_password_url() ); ?>">
					<?php wp_nonce_field( 'one1_reset_password', 'one1_reset_nonce' ); ?>
					<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
					<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>" />

					<p class="homie-auth-field">
						<label for="pass1"><?php esc_html_e( 'New password', 'one' ); ?></label>
						<input type="password" name="pass1" id="pass1" autocomplete="new-password" minlength="8" required />
					</p>

					<p class="homie-auth-field">
						<label for="pass2"><?php esc_html_e( 'Confirm new password', 'one' ); ?></label>
						<input type="password" name="pass2" id="pass2" autocomplete="new-password" minlength="8" required />
					</p>

					<p class="homie-auth-actions">
						<?php
						one1_button(
							array(
								'label'   => __( 'Update password', 'one' ),
								'type'    => 'submit',
								'variant' => 'primary',
								'skin'    => 'homie',
								'block'   => true,
								'name'    => 'one1_reset_submit',
								'value'   => '1',
							)
						);
						?>
					</p>
				</form>
			<?php endif; ?>

			<p class="homie-auth-footer-text">
				<a class="homie-auth-link" href="<?php echo esc_url( one1_login_url() ); ?>"><?php esc_html_e( 'Back to log in', 'one' ); ?></a>
			</p>
		</div>
	</main>
</div>
