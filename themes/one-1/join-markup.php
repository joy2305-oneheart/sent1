<?php
/**
 * Join landing page markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ref          = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
$invite_token = isset( $_GET['invite_token'] ) ? sanitize_text_field( wp_unslash( $_GET['invite_token'] ) ) : '';
$pu_token     = isset( $_GET['pu_token'] ) ? sanitize_text_field( wp_unslash( $_GET['pu_token'] ) ) : '';
$register_mode = ! empty( $_GET['register'] ) || $pu_token !== '';

if ( $register_mode ) {
	require get_stylesheet_directory() . '/auth-register-markup.php';
	return;
}

$inviter = null;
if ( $invite_token !== '' && function_exists( 'one1_resolve_inviter_from_invite_token' ) ) {
	$inviter = one1_resolve_inviter_from_invite_token( $invite_token );
} elseif ( $ref !== '' ) {
	$inviter = one1_resolve_inviter_from_ref( $ref );
}
$invite_invalid = ( $invite_token !== '' || $ref !== '' ) && ! $inviter;
?>
<div class="homie-homepage homie-auth one-join-page">
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
		<div class="homie-auth-card one-join-card">
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
			<?php elseif ( $invite_invalid ) : ?>
				<div class="homie-auth-alert homie-auth-alert--error" role="alert">
					<?php esc_html_e( 'This invitation link is not valid or has expired.', 'one' ); ?>
				</div>
			<?php endif; ?>

			<p class="homie-auth-eyebrow"><?php esc_html_e( 'Join Sent One', 'one' ); ?></p>
			<h1 class="homie-auth-title"><?php esc_html_e( 'Welcome to the circle', 'one' ); ?></h1>
			<p class="homie-auth-lead">
				<?php esc_html_e( 'Create a new account or log in with an existing one to connect with the person who invited you.', 'one' ); ?>
			</p>

			<div class="one-join-actions">
				<?php
				$create_url = add_query_arg( 'register', '1', one1_join_page_url() );
				if ( $invite_token !== '' ) {
					$create_url = add_query_arg( 'invite_token', $invite_token, $create_url );
				} elseif ( $ref !== '' ) {
					$create_url = add_query_arg( 'ref', $ref, $create_url );
				}
				one1_button(
					array(
						'url'     => $create_url,
						'label'   => __( 'Create account', 'one' ),
						'variant' => 'primary',
						'skin'    => 'homie',
						'block'   => true,
						'icon'          => 'material:person_add',
						'icon_position' => 'before',
					)
				);
				one1_button(
					array(
						'url'     => one1_login_url( '', $ref, $invite_token ),
						'label'   => __( 'Log in', 'one' ),
						'variant' => 'outline',
						'skin'    => 'homie',
						'block'   => true,
						'icon'          => 'material:login',
						'icon_position' => 'before',
					)
				);
				?>
			</div>

			<p class="homie-auth-footer-text">
				<a class="homie-auth-link" href="<?php echo esc_url( one1_login_url() ); ?>"><?php esc_html_e( 'Back to login', 'one' ); ?></a>
			</p>
		</div>
	</main>
</div>
