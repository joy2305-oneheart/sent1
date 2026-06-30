<?php
/**
 * Sign up page markup (uses Social Invite Network registration handler).
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ref          = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
$invite_token = isset( $_GET['invite_token'] ) ? sanitize_text_field( wp_unslash( $_GET['invite_token'] ) ) : '';
$pu_token     = isset( $_GET['pu_token'] ) ? sanitize_text_field( wp_unslash( $_GET['pu_token'] ) ) : '';
$reg_error    = one1_auth_query_message( 'sin_reg_error' );
$sosl_error   = one1_auth_query_message( 'sosl_error' );
$action       = esc_url( get_permalink() );

$signup_allowed = class_exists( 'SIN_Registration' ) && SIN_Registration::signup_is_allowed( $pu_token, $ref );
$signup_ctx     = class_exists( 'SIN_Registration' ) ? SIN_Registration::resolve_signup_context( $pu_token, $ref ) : array( 'type' => 'none' );
$inviter        = ( 'friend' === ( $signup_ctx['type'] ?? '' ) && ! empty( $signup_ctx['inviter'] ) ) ? $signup_ctx['inviter'] : null;
$is_pu_signup   = 'pu' === ( $signup_ctx['type'] ?? '' );
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
			<?php if ( ! $signup_allowed ) : ?>
				<p class="homie-auth-eyebrow"><?php esc_html_e( 'Invitation required', 'one' ); ?></p>
				<h1 class="homie-auth-title"><?php esc_html_e( 'Registration is by invitation only', 'one' ); ?></h1>
				<p class="homie-auth-lead">
					<?php esc_html_e( 'You need a valid Primary User or friend invitation link to create an account.', 'one' ); ?>
				</p>
				<?php if ( 'invalid_pu_token' === ( $signup_ctx['type'] ?? '' ) ) : ?>
					<div class="homie-auth-alert homie-auth-alert--error" role="alert">
						<?php esc_html_e( 'This Primary User invitation is invalid or has expired.', 'one' ); ?>
					</div>
				<?php elseif ( 'invalid_ref' === ( $signup_ctx['type'] ?? '' ) ) : ?>
					<div class="homie-auth-alert homie-auth-alert--error" role="alert">
						<?php esc_html_e( 'This invitation link is not valid or has expired.', 'one' ); ?>
					</div>
				<?php endif; ?>
				<p class="homie-auth-footer-text">
					<?php esc_html_e( 'Already have an account?', 'one' ); ?>
					<a class="homie-auth-link" href="<?php echo esc_url( one1_login_url() ); ?>"><?php esc_html_e( 'Log in', 'one' ); ?></a>
				</p>
			<?php else : ?>
				<p class="homie-auth-eyebrow"><?php esc_html_e( 'Join the circle', 'one' ); ?></p>
				<h1 class="homie-auth-title"><?php esc_html_e( 'Create your Sent One account', 'one' ); ?></h1>
				<?php if ( $is_pu_signup ) : ?>
					<p class="homie-auth-lead"><?php esc_html_e( 'You have been invited to share your journey as a Primary User.', 'one' ); ?></p>
				<?php else : ?>
					<p class="homie-auth-lead"><?php esc_html_e( 'Follow someone\'s journey and stay connected through their updates.', 'one' ); ?></p>
				<?php endif; ?>

				<?php if ( $inviter ) : ?>
					<div class="one-join-banner" role="status">
						<span class="material-symbols-outlined" aria-hidden="true">group</span>
						<p>
							<?php
							printf(
								/* translators: %s: inviter display name */
								esc_html__( 'You\'re joining %s\'s circle', 'one' ),
								esc_html( $inviter->display_name )
							);
							?>
						</p>
					</div>
				<?php endif; ?>

				<?php if ( $reg_error ) : ?>
					<div class="homie-auth-alert homie-auth-alert--error" role="alert"><?php echo esc_html( $reg_error ); ?></div>
				<?php endif; ?>

				<?php if ( $sosl_error ) : ?>
					<div class="homie-auth-alert homie-auth-alert--error" role="alert"><?php echo esc_html( $sosl_error ); ?></div>
				<?php endif; ?>

				<?php if ( function_exists( 'sosl_render_buttons' ) ) : ?>
					<?php
					sosl_render_buttons(
						array(
							'context'     => 'register',
							'redirect_to' => get_permalink(),
							'ref'          => $ref,
							'pu_token'     => $pu_token,
							'invite_token' => $invite_token,
						)
					);
					?>
					<div class="homie-auth-divider" role="separator" aria-label="<?php esc_attr_e( 'or', 'one' ); ?>">
						<span><?php esc_html_e( 'or', 'one' ); ?></span>
					</div>
				<?php endif; ?>

				<form class="homie-auth-form" method="post" action="<?php echo esc_url( $action ); ?>">
					<?php wp_nonce_field( 'sin_register', 'sin_register_nonce' ); ?>
					<input type="hidden" name="invite_code" value="<?php echo esc_attr( $ref ); ?>" />
					<input type="hidden" name="invite_token" value="<?php echo esc_attr( $invite_token ); ?>" />
					<input type="hidden" name="pu_token" value="<?php echo esc_attr( $pu_token ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( get_permalink() ); ?>" />

					<p class="homie-auth-field">
						<label for="sin_name"><?php esc_html_e( 'Name', 'one' ); ?></label>
						<input type="text" name="sin_name" id="sin_name" autocomplete="name" required />
					</p>

					<p class="homie-auth-field">
						<label for="sin_email"><?php esc_html_e( 'Email', 'one' ); ?></label>
						<input type="email" name="sin_email" id="sin_email" autocomplete="email" required />
					</p>

					<p class="homie-auth-field">
						<label for="sin_password"><?php esc_html_e( 'Password', 'one' ); ?></label>
						<input type="password" name="sin_password" id="sin_password" minlength="8" autocomplete="new-password" required />
					</p>

					<p class="homie-auth-field">
						<label for="sin_password_confirm"><?php esc_html_e( 'Confirm password', 'one' ); ?></label>
						<input type="password" name="sin_password_confirm" id="sin_password_confirm" minlength="8" autocomplete="new-password" required />
					</p>

					<p class="homie-auth-actions">
						<?php
						one1_button(
							array(
								'label'   => __( 'Sign up', 'one' ),
								'type'    => 'submit',
								'variant' => 'primary',
								'skin'    => 'homie',
								'block'   => true,
								'name'    => 'sin_register_submit',
								'value'   => '1',
							)
						);
						?>
					</p>
				</form>

				<p class="homie-auth-footer-text">
					<?php esc_html_e( 'Already have an account?', 'one' ); ?>
					<a class="homie-auth-link" href="<?php echo esc_url( one1_login_url( '', $ref, $invite_token ) ); ?>"><?php esc_html_e( 'Log in', 'one' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
	</main>
</div>
