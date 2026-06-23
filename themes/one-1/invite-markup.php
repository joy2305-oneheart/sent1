<?php
/**
 * Invite page markup.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$one_share_uid       = get_current_user_id();
$one_can_share       = function_exists( 'sin_is_network_approved' ) && sin_is_network_approved( $one_share_uid );
$one_invite_link     = one1_get_invite_link( $one_share_uid );
$one_nav_active      = 'invite';
$one_invite_inbox    = array();
$one_invite_flash    = null;
$one_invite_redirect = one1_invite_page_url();
$one_sent_invites    = array();
$one_highlight_id    = isset( $_GET['sin_invite'] ) ? (int) $_GET['sin_invite'] : 0;

if ( class_exists( 'SIN_Invitations' ) ) {
	$one_invite_inbox   = SIN_Invitations::get_inbox( $one_share_uid );
	$one_invite_flash   = SIN_Invitations::get_and_clear_flash( $one_share_uid );
	$one_sent_invites   = SIN_Invitations::get_sent_by_user( $one_share_uid );
}
?>
<div class="sent-share-page one-invite-page">
	<?php require get_stylesheet_directory() . '/inc/share/sent-share-header.php'; ?>

	<div class="sent-share-layout sent-share-layout--app">
		<?php require get_stylesheet_directory() . '/inc/share/sent-share-nav.php'; ?>

		<main class="sent-share-main one-invite-main">
			<?php if ( ! $one_can_share ) : ?>
				<section class="sent-share-notice">
					<h1 class="sent-share-notice__title"><?php esc_html_e( 'Almost there', 'one' ); ?></h1>
					<p class="sent-share-notice__text">
						<?php esc_html_e( 'Only Primary Users can invite others to their circle.', 'one' ); ?>
					</p>
				</section>
			<?php else : ?>
				<div class="one-invite-stack">
					<?php if ( is_array( $one_invite_flash ) && ! empty( $one_invite_flash['message'] ) ) : ?>
						<div class="sent-share-notice sent-share-notice--inline<?php echo ( isset( $one_invite_flash['type'] ) && 'success' === $one_invite_flash['type'] ) ? ' is-success' : ' is-error'; ?>" role="status">
							<p class="sent-share-notice__text"><?php echo esc_html( (string) $one_invite_flash['message'] ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $one_invite_inbox ) ) : ?>
						<section class="one-invite-card one-invite-card--inbox" id="one-invite-inbox" aria-labelledby="one-invite-inbox-title"<?php echo $one_highlight_id > 0 ? ' data-one-invite-highlight="true"' : ''; ?>>
							<h2 id="one-invite-inbox-title" class="one-invite-card__title">
								<span class="material-symbols-outlined" aria-hidden="true">mail</span>
								<?php esc_html_e( 'Pending invitations', 'one' ); ?>
							</h2>
							<p class="one-invite-card__lead"><?php esc_html_e( 'Accept to connect with members who invited you.', 'one' ); ?></p>
							<ul class="one-invite-inbox">
								<?php foreach ( $one_invite_inbox as $row ) : ?>
									<?php
									$iid = isset( $row['invitation_id'] ) ? (int) $row['invitation_id'] : 0;
									$inv = isset( $row['inviter_id'] ) ? get_userdata( (int) $row['inviter_id'] ) : false;
									if ( $iid <= 0 ) {
										continue;
									}
									$highlight = $one_highlight_id === $iid;
									?>
									<li class="one-invite-inbox__item<?php echo $highlight ? ' is-highlighted' : ''; ?>"<?php echo $highlight ? ' id="sin-invite-' . esc_attr( (string) $iid ) . '"' : ''; ?>>
										<span class="one-invite-inbox__from">
											<?php
											printf(
												/* translators: %s: inviter display name */
												esc_html__( 'From %s', 'one' ),
												esc_html( $inv ? $inv->display_name : __( 'Member', 'one' ) )
											);
											?>
										</span>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="one-invite-inbox__accept">
											<?php wp_nonce_field( 'sin_accept_invite', 'sin_accept_invite_nonce' ); ?>
											<input type="hidden" name="action" value="sin_accept_invite" />
											<input type="hidden" name="invitation_id" value="<?php echo esc_attr( (string) $iid ); ?>" />
											<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $one_invite_redirect ); ?>" />
											<button type="submit" class="one-invite-inbox__btn"><?php esc_html_e( 'Accept', 'one' ); ?></button>
										</form>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<header class="one-invite-hero">
						<h1 class="one-invite-hero__title"><?php esc_html_e( 'Grow your circle', 'one' ); ?></h1>
						<p class="one-invite-hero__text">
							<?php esc_html_e( 'Invite family and friends by email or share your personal link. New contacts get a signup invite; people who already have an account receive a link to join your circle.', 'one' ); ?>
						</p>
					</header>

					<div class="one-invite-grid">
						<section class="one-invite-card" aria-labelledby="one-invite-email-title">
							<h2 id="one-invite-email-title" class="one-invite-card__title">
								<span class="material-symbols-outlined" aria-hidden="true">forward_to_inbox</span>
								<?php esc_html_e( 'Send by email', 'one' ); ?>
							</h2>
							<p class="one-invite-card__lead"><?php esc_html_e( 'We will send the right invitation for new or existing accounts.', 'one' ); ?></p>
							<form class="one-invite-form" data-one-invite-form novalidate>
								<label class="one-invite-form__label" for="one-invite-email"><?php esc_html_e( 'Email address', 'one' ); ?></label>
								<div class="one-invite-form__row">
									<input type="email" id="one-invite-email" name="email" class="one-invite-form__input" placeholder="name@example.com" required autocomplete="email" />
									<button type="submit" class="one-invite-form__submit" data-one-invite-submit>
										<?php esc_html_e( 'Send invitation', 'one' ); ?>
									</button>
								</div>
								<p class="one-invite-form__feedback" data-one-invite-feedback hidden role="status"></p>
							</form>
						</section>

						<section class="one-invite-card" aria-labelledby="one-invite-link-title">
							<h2 id="one-invite-link-title" class="one-invite-card__title">
								<span class="material-symbols-outlined" aria-hidden="true">link</span>
								<?php esc_html_e( 'Your invite link', 'one' ); ?>
							</h2>
							<p class="one-invite-card__lead"><?php esc_html_e( 'Copy and share anywhere—text, social, or messaging apps.', 'one' ); ?></p>
							<p class="one-invite-card__hint"><?php esc_html_e( 'New members create an account; existing members can log in to join your circle.', 'one' ); ?></p>
							<div class="one-invite-link-box">
								<input type="text" class="one-invite-link-box__input" id="one-invite-link" value="<?php echo esc_attr( $one_invite_link ); ?>" readonly />
								<button type="button" class="one-invite-link-box__copy" data-one-invite-copy>
									<span class="material-symbols-outlined" aria-hidden="true">content_copy</span>
									<?php esc_html_e( 'Copy', 'one' ); ?>
								</button>
							</div>
						</section>

						<?php if ( ! empty( $one_sent_invites ) ) : ?>
						<section class="one-invite-card one-invite-card--sent" aria-labelledby="one-invite-sent-title">
							<h2 id="one-invite-sent-title" class="one-invite-card__title">
								<span class="material-symbols-outlined" aria-hidden="true">history</span>
								<?php esc_html_e( 'Invitations you sent', 'one' ); ?>
							</h2>
							<p class="one-invite-card__lead"><?php esc_html_e( 'Track who you invited and whether they joined your circle.', 'one' ); ?></p>
							<div class="one-invite-sent-table-wrap">
								<table class="one-invite-sent-table">
									<thead>
										<tr>
											<th scope="col"><?php esc_html_e( 'Invitee', 'one' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Status', 'one' ); ?></th>
											<th scope="col"><?php esc_html_e( 'Date sent', 'one' ); ?></th>
											<th scope="col" class="one-invite-sent-table__actions-col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'one' ); ?></span></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $one_sent_invites as $sent ) : ?>
											<?php
											$invitee_user = get_user_by( 'email', $sent['invitee_email'] ?? '' );
											$status_label = class_exists( 'SIN_Invitations' )
												? SIN_Invitations::get_display_status( $sent['status'] ?? '' )
												: '';
											$is_pending   = in_array( $sent['status'] ?? '', array( 'pending_registration', 'pending_approval' ), true );
											$inv_id       = isset( $sent['id'] ) ? (int) $sent['id'] : 0;
											?>
											<tr data-one-sent-invite-row="<?php echo esc_attr( (string) $inv_id ); ?>">
												<td data-label="<?php esc_attr_e( 'Invitee', 'one' ); ?>">
													<span class="one-invite-sent-table__email"><?php echo esc_html( $sent['invitee_email'] ?? '' ); ?></span>
													<?php if ( $invitee_user ) : ?>
														<span class="one-invite-sent-table__name"><?php echo esc_html( $invitee_user->display_name ); ?></span>
													<?php endif; ?>
												</td>
												<td data-label="<?php esc_attr_e( 'Status', 'one' ); ?>">
													<span class="one-invite-sent-table__status<?php echo $is_pending ? ' is-pending' : ' is-accepted'; ?>">
														<?php echo esc_html( $status_label ); ?>
													</span>
												</td>
												<td data-label="<?php esc_attr_e( 'Date sent', 'one' ); ?>">
													<?php
													echo esc_html(
														mysql2date(
															get_option( 'date_format' ),
															$sent['created_at'] ?? ''
														)
													);
													?>
												</td>
												<td class="one-invite-sent-table__actions" data-label="<?php esc_attr_e( 'Actions', 'one' ); ?>">
													<?php if ( $is_pending && $inv_id > 0 ) : ?>
													<div class="one-invite-sent-menu" data-one-sent-invite-menu>
														<button type="button" class="one-invite-sent-menu__trigger" data-one-sent-invite-menu-toggle aria-expanded="false" aria-haspopup="true" aria-label="<?php esc_attr_e( 'Invitation options', 'one' ); ?>">
															<span class="material-symbols-outlined" aria-hidden="true">more_vert</span>
														</button>
														<div class="one-invite-sent-menu__panel" data-one-sent-invite-menu-panel hidden role="menu">
															<button type="button" class="one-invite-sent-menu__item" role="menuitem" data-one-sent-invite-resend data-invitation-id="<?php echo esc_attr( (string) $inv_id ); ?>">
																<?php esc_html_e( 'Resend invitation', 'one' ); ?>
															</button>
															<button type="button" class="one-invite-sent-menu__item one-invite-sent-menu__item--danger" role="menuitem" data-one-sent-invite-remove data-invitation-id="<?php echo esc_attr( (string) $inv_id ); ?>">
																<?php esc_html_e( 'Remove invitation', 'one' ); ?>
															</button>
														</div>
													</div>
													<?php else : ?>
														<span aria-hidden="true">—</span>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</section>
						<?php endif; ?>

					<?php
					$one_is_pu = function_exists( 'sin_is_pu' ) && sin_is_pu( $one_share_uid );
					if ( $one_is_pu ) :
						$one_friends      = one1_get_circle_friends( $one_share_uid );
						$one_friend_count = count( $one_friends );
						?>
					<section
						class="one-invite-card one-invite-card--full one-friends-manage"
						aria-labelledby="one-friends-manage-title"
						data-one-friends-manage
						data-empty="<?php echo esc_attr__( 'No friends in your circle yet. Send an invite above.', 'one' ); ?>"
						data-joined-label="<?php echo esc_attr__( 'Joined', 'one' ); ?>"
						data-role-label="<?php echo esc_attr__( 'Role', 'one' ); ?>"
						data-edit-label="<?php echo esc_attr__( 'Edit friend details', 'one' ); ?>"
					>
						<h2 id="one-friends-manage-title" class="one-invite-card__title">
							<span class="material-symbols-outlined" aria-hidden="true">group</span>
							<?php esc_html_e( 'Your friends', 'one' ); ?>
							<span class="one-invite-card__count" data-one-friends-count><?php echo esc_html( (string) $one_friend_count ); ?></span>
						</h2>
						<p class="one-invite-card__lead">
							<?php esc_html_e( 'Everyone in your circle. Tap the menu on a friend to add a nickname or notes.', 'one' ); ?>
						</p>
						<ul class="one-friends-manage__list" data-one-friends-list>
							<?php if ( empty( $one_friends ) ) : ?>
								<li class="one-friends-manage__empty"><?php esc_html_e( 'No friends in your circle yet. Send an invite above.', 'one' ); ?></li>
							<?php else : ?>
								<?php foreach ( $one_friends as $one_friend ) : ?>
									<?php one1_render_circle_friend_item( $one_friend ); ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>

						<div class="one-friend-modal" data-one-friend-modal hidden aria-hidden="true">
							<div class="one-friend-modal__backdrop" data-one-friend-modal-close tabindex="-1"></div>
							<div
								class="one-friend-modal__dialog"
								role="dialog"
								aria-modal="true"
								aria-labelledby="one-friend-modal-title"
							>
								<header class="one-friend-modal__header">
									<div class="one-friend-modal__header-main">
										<img class="one-friend-modal__avatar" data-one-friend-modal-avatar src="" alt="" width="48" height="48" hidden />
										<div>
											<h3 id="one-friend-modal-title" class="one-friend-modal__title" data-one-friend-modal-name></h3>
											<p class="one-friend-modal__subtitle" data-one-friend-modal-email></p>
										</div>
									</div>
									<button type="button" class="one-friend-modal__close" data-one-friend-modal-close aria-label="<?php esc_attr_e( 'Close', 'one' ); ?>">
										<span class="material-symbols-outlined" aria-hidden="true">close</span>
									</button>
								</header>

								<dl class="one-friend-modal__stats">
									<div>
										<dt><?php esc_html_e( 'Joined', 'one' ); ?></dt>
										<dd data-one-friend-modal-joined>—</dd>
									</div>
									<div>
										<dt><?php esc_html_e( 'Role', 'one' ); ?></dt>
										<dd data-one-friend-modal-role>—</dd>
									</div>
								</dl>

								<form class="one-friend-modal__form" data-one-friend-modal-form novalidate>
									<input type="hidden" name="friend_id" value="" data-one-friend-modal-id />

									<label class="one-friend-modal__field" for="one-friend-modal-nickname">
										<span><?php esc_html_e( 'Nickname', 'one' ); ?></span>
										<input type="text" id="one-friend-modal-nickname" name="nickname" maxlength="190" autocomplete="off" placeholder="<?php esc_attr_e( 'Optional display name for your circle', 'one' ); ?>" />
									</label>

									<label class="one-friend-modal__field" for="one-friend-modal-notes">
										<span><?php esc_html_e( 'Notes', 'one' ); ?></span>
										<textarea id="one-friend-modal-notes" name="notes" rows="3" placeholder="<?php esc_attr_e( 'Private notes only you can see', 'one' ); ?>"></textarea>
									</label>

									<p class="one-friend-modal__feedback" data-one-friend-modal-feedback hidden role="status"></p>

									<div class="one-friend-modal__actions">
										<button type="button" class="one-friend-modal__cancel" data-one-friend-modal-close>
											<?php esc_html_e( 'Cancel', 'one' ); ?>
										</button>
										<button type="submit" class="one-invite-form__submit">
											<?php esc_html_e( 'Save', 'one' ); ?>
										</button>
									</div>
								</form>
							</div>
						</div>
					</section>
					<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</main>
	</div>

	<?php require get_stylesheet_directory() . '/inc/share/sent-share-mobile-nav.php'; ?>

</div>
