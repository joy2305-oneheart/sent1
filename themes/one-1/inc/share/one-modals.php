<?php
/**
 * Shared confirm + public share modals.
 *
 * @package one
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="one-confirm" data-one-confirm hidden aria-hidden="true">
	<div class="one-confirm__backdrop" data-one-confirm-cancel tabindex="-1"></div>
	<div class="one-confirm__dialog" role="alertdialog" aria-modal="true" aria-labelledby="one-confirm-title">
		<div class="one-sheet-handle" aria-hidden="true"></div>
		<h3 id="one-confirm-title" class="one-confirm__title" data-one-confirm-title></h3>
		<p class="one-confirm__message" data-one-confirm-message></p>
		<div class="one-confirm__actions">
			<button type="button" class="one-confirm__btn one-confirm__btn--ghost" data-one-confirm-cancel>
				<?php esc_html_e( 'Cancel', 'one' ); ?>
			</button>
			<button type="button" class="one-confirm__btn one-confirm__btn--primary" data-one-confirm-ok>
				<?php esc_html_e( 'Confirm', 'one' ); ?>
			</button>
		</div>
	</div>
</div>

<div class="one-share-link-modal" data-one-share-link-modal hidden aria-hidden="true">
	<div class="one-share-link-modal__backdrop" data-one-share-link-modal-close tabindex="-1"></div>
	<div class="one-share-link-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="one-share-link-modal-title">
		<div class="one-sheet-handle" aria-hidden="true"></div>
		<header class="one-share-link-modal__header">
			<h3 id="one-share-link-modal-title" class="one-share-link-modal__title"><?php esc_html_e( 'Share publicly', 'one' ); ?></h3>
			<button type="button" class="one-share-link-modal__close" data-one-share-link-modal-close aria-label="<?php esc_attr_e( 'Close', 'one' ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">close</span>
			</button>
		</header>
		<div class="one-share-link-modal__body">
			<p class="one-share-link-modal__lead"><?php esc_html_e( 'Anyone with this link can view this post and donate—no login required. Choose how long the link stays active.', 'one' ); ?></p>
			<label class="one-share-link-modal__field" for="one-share-link-duration">
				<span><?php esc_html_e( 'Link expires after', 'one' ); ?></span>
				<select id="one-share-link-duration" data-one-share-link-duration>
					<option value="3600"><?php esc_html_e( '1 hour', 'one' ); ?></option>
					<option value="21600"><?php esc_html_e( '6 hours', 'one' ); ?></option>
					<option value="86400" selected><?php esc_html_e( '24 hours', 'one' ); ?></option>
					<option value="259200"><?php esc_html_e( '3 days', 'one' ); ?></option>
					<option value="604800"><?php esc_html_e( '7 days', 'one' ); ?></option>
				</select>
			</label>
			<div class="one-share-link-modal__result" data-one-share-link-result hidden>
				<label class="one-share-link-modal__field" for="one-share-link-url">
					<span><?php esc_html_e( 'Share link', 'one' ); ?></span>
					<div class="one-share-link-modal__url-row">
						<input type="text" id="one-share-link-url" class="one-share-link-modal__url" data-one-share-link-url readonly />
						<button type="button" class="one-share-link-modal__copy" data-one-share-link-copy>
							<span class="material-symbols-outlined" aria-hidden="true">content_copy</span>
							<?php esc_html_e( 'Copy', 'one' ); ?>
						</button>
					</div>
				</label>
				<p class="one-share-link-modal__expiry" data-one-share-link-expiry></p>
			</div>
			<p class="one-share-link-modal__feedback" data-one-share-link-feedback hidden role="status"></p>
		</div>
		<footer class="one-share-link-modal__footer">
			<button type="button" class="one-share-link-modal__btn one-share-link-modal__btn--ghost" data-one-share-link-modal-close>
				<?php esc_html_e( 'Close', 'one' ); ?>
			</button>
			<button type="button" class="one-share-link-modal__btn one-share-link-modal__btn--secondary" data-one-share-link-generate>
				<?php esc_html_e( 'Create link', 'one' ); ?>
			</button>
			<button type="button" class="one-share-link-modal__btn one-share-link-modal__btn--primary" data-one-share-link-share>
				<span class="material-symbols-outlined" aria-hidden="true">share</span>
				<?php esc_html_e( 'Share', 'one' ); ?>
			</button>
		</footer>
	</div>
</div>
