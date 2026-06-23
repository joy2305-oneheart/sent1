<?php
/**
 * Forgot password page markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$forgot_error   = one1_auth_query_message( 'forgot_error' );
$email_sent     = isset( $_GET['checkemail'] ) && 'confirm' === $_GET['checkemail'];
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
			<h1 class="homie-auth-title"><?php esc_html_e( 'Reset your password', 'one' ); ?></h1>
			<p class="homie-auth-lead"><?php esc_html_e( 'Enter the email address for your account and we will send you a link to choose a new password.', 'one' ); ?></p>

			<?php if ( $email_sent ) : ?>
				<div class="homie-auth-alert homie-auth-alert--success" role="status">
					<?php esc_html_e( 'Check your email for a link to reset your password. If it does not arrive, check your spam folder.', 'one' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $forgot_error ) : ?>
				<div class="homie-auth-alert homie-auth-alert--error" role="alert"><?php echo esc_html( $forgot_error ); ?></div>
			<?php endif; ?>

			<?php if ( ! $email_sent ) : ?>
			<form class="homie-auth-form" method="post" action="<?php echo esc_url( one1_forgot_password_url() ); ?>">
				<?php wp_nonce_field( 'one1_forgot_password', 'one1_forgot_nonce' ); ?>

				<p class="homie-auth-field">
					<label for="user_login"><?php esc_html_e( 'Email or username', 'one' ); ?></label>
					<input type="text" name="user_login" id="user_login" autocomplete="username" required />
				</p>

				<p class="homie-auth-actions">
					<?php
					one1_button(
						array(
							'label'   => __( 'Send reset link', 'one' ),
							'type'    => 'submit',
							'variant' => 'primary',
							'skin'    => 'homie',
							'block'   => true,
							'name'    => 'one1_forgot_submit',
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
