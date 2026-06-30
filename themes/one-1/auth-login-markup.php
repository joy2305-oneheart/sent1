<?php
/**
 * Login page markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$redirect_to = '';
if ( isset( $_GET['redirect_to'] ) ) {
	$redirect_to = one1_sanitize_login_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) );
}

$ref          = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
$invite_token = isset( $_GET['invite_token'] ) ? sanitize_text_field( wp_unslash( $_GET['invite_token'] ) ) : '';
$inviter      = null;
if ( $invite_token !== '' && function_exists( 'one1_resolve_inviter_from_invite_token' ) ) {
	$inviter = one1_resolve_inviter_from_invite_token( $invite_token );
} elseif ( $ref !== '' && function_exists( 'one1_resolve_inviter_from_ref' ) ) {
	$inviter = one1_resolve_inviter_from_ref( $ref );
}
$login_error  = one1_auth_query_message( 'login_error' );
$sosl_error   = one1_auth_query_message( 'sosl_error' );
$reg_success  = isset( $_GET['sin_reg_success'] );
$reset_success = isset( $_GET['reset'] ) && 'success' === $_GET['reset'];
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
			<p class="homie-auth-eyebrow"><?php esc_html_e( 'Welcome back', 'one' ); ?></p>
			<h1 class="homie-auth-title"><?php esc_html_e( 'Log in to Sent One', 'one' ); ?></h1>
			<p class="homie-auth-lead"><?php esc_html_e( 'Sign in to stay close to the journeys you follow.', 'one' ); ?></p>

			<?php if ( $inviter ) : ?>
				<div class="one-join-banner" role="status">
					<span class="material-symbols-outlined" aria-hidden="true">group</span>
					<p>
						<?php
						printf(
							/* translators: %s: inviter display name */
							esc_html__( 'Log in to join %s\'s circle', 'one' ),
							esc_html( $inviter->display_name )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $reset_success ) : ?>
				<div class="homie-auth-alert homie-auth-alert--success" role="status">
					<?php esc_html_e( 'Your password has been updated. You can log in now.', 'one' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $reg_success ) : ?>
				<div class="homie-auth-alert homie-auth-alert--success" role="status">
					<?php esc_html_e( 'Your account was created. You can log in now.', 'one' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $login_error ) : ?>
				<div class="homie-auth-alert homie-auth-alert--error" role="alert"><?php echo esc_html( $login_error ); ?></div>
			<?php endif; ?>

			<?php if ( $sosl_error ) : ?>
				<div class="homie-auth-alert homie-auth-alert--error" role="alert"><?php echo esc_html( $sosl_error ); ?></div>
			<?php endif; ?>

			<?php if ( function_exists( 'sosl_render_buttons' ) ) : ?>
				<?php
				sosl_render_buttons(
					array(
						'context'     => 'login',
						'redirect_to' => $redirect_to,
						'ref'         => $ref,
					)
				);
				?>
				<div class="homie-auth-divider" role="separator" aria-label="<?php esc_attr_e( 'or', 'one' ); ?>">
					<span><?php esc_html_e( 'or', 'one' ); ?></span>
				</div>
			<?php endif; ?>

			<form class="homie-auth-form" method="post" action="<?php echo esc_url( one1_login_url() ); ?>">
				<?php wp_nonce_field( 'one1_login', 'one1_login_nonce' ); ?>
				<?php if ( $redirect_to ) : ?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
				<?php endif; ?>
				<?php if ( $ref ) : ?>
					<input type="hidden" name="invite_code" value="<?php echo esc_attr( $ref ); ?>" />
				<?php endif; ?>
				<?php if ( $invite_token ) : ?>
					<input type="hidden" name="invite_token" value="<?php echo esc_attr( $invite_token ); ?>" />
				<?php endif; ?>

				<p class="homie-auth-field">
					<label for="user_login"><?php esc_html_e( 'Email or username', 'one' ); ?></label>
					<input type="text" name="log" id="user_login" autocomplete="username" required />
				</p>

				<p class="homie-auth-field">
					<label for="user_pass"><?php esc_html_e( 'Password', 'one' ); ?></label>
					<input type="password" name="pwd" id="user_pass" autocomplete="current-password" required />
				</p>

				<p class="homie-auth-field homie-auth-field--row">
					<label class="homie-auth-checkbox">
						<input type="checkbox" name="rememberme" value="forever" />
						<span><?php esc_html_e( 'Remember me', 'one' ); ?></span>
					</label>
					<a class="homie-auth-link" href="<?php echo esc_url( one1_forgot_password_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'one' ); ?></a>
				</p>

				<p class="homie-auth-actions">
					<?php
					one1_button(
						array(
							'label'   => __( 'Log in', 'one' ),
							'type'    => 'submit',
							'variant' => 'primary',
							'skin'    => 'homie',
							'block'   => true,
							'name'    => 'one1_login_submit',
							'value'   => '1',
						)
					);
					?>
				</p>
			</form>
		</div>
	</main>
</div>
